<?php

namespace Formwork\Services;

use Closure;
use Formwork\Services\Exceptions\ContainerException;
use Formwork\Services\Exceptions\ServiceNotFoundException;
use Formwork\Services\Exceptions\ServiceResolutionException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use Throwable;

class Container implements ContainerInterface
{
    /**
     * Defined services
     *
     * @var array<string, ServiceDefinition>
     */
    protected array $defined;

    /**
     * Resolved services
     *
     * @var array<string, object>
     */
    protected array $resolved;

    /**
     * Service aliases
     *
     * @var array<string, string>
     */
    protected array $aliases;

    /**
     * Stack of services being resolved
     *
     * @var list<string>
     */
    private array $resolveStack = [];

    /**
     * Define a new service
     */
    public function define(string $name, ?object $object = null): ServiceDefinition
    {
        return $this->defined[$name] = new ServiceDefinition($name, $object, $this);
    }

    /**
     * Build a new instance of a class, resolving its dependencies and passing static parameters
     *
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $parameters
     *
     * @return T
     */
    public function build(string $class, array $parameters = []): object
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = $this->buildArguments($constructor, $parameters);

        return new $class(...$arguments);
    }

    /**
     * Call a closure, resolving its dependencies and passing static parameters
     *
     * @param array<string, mixed> $parameters
     */
    public function call(Closure $closure, array $parameters = []): mixed
    {
        $arguments = $this->buildArguments(new ReflectionFunction($closure), $parameters);

        return $closure(...$arguments);
    }

    /**
     * Alias a service to another service
     */
    public function alias(string $alias, string $target): void
    {
        if ($alias === $target) {
            throw new ContainerException(sprintf('Cannot alias "%s" to itself', $target));
        }
        $this->aliases[$alias] = $target;
    }

    /**
     * Get a service instance, resolving it if needed
     *
     * @template T of object
     *
     * @param class-string<T>|string $name
     *
     * @return ($name is class-string<T> ? T : object)
     */
    public function get(string $name): object
    {
        if (!$this->has($name)) {
            throw new ServiceNotFoundException(sprintf('Instance of "%s" not found', $name));
        }
        if (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            return $this->get($alias);
        }

        return $this->resolved[$name] ??= $this->resolve($name);
    }

    /**
     * Return whether a service is defined
     */
    public function has(string $name): bool
    {
        if (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            return $this->has($alias);
        }

        return isset($this->defined[$name]);
    }

    /**
     * Return whether a service is resolved
     */
    public function isResolved(string $name): bool
    {
        if (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            return $this->isResolved($alias);
        }

        return isset($this->resolved[$name]);
    }

    /**
     * Resolve a service
     */
    public function resolve(string $name): object
    {
        if (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            return $this->resolve($alias);
        }

        /**
         * @var class-string $name
         */

        if (in_array($name, $this->resolveStack, true)) {
            throw new ServiceResolutionException(sprintf('Already resolving "%s". Resolution stack: "%s"', $name, implode('", "', $this->resolveStack)));
        }

        $this->resolveStack[] = $name;

        try {
            if (!$this->has($name)) {
                throw new ServiceResolutionException(sprintf('Trying to resolve undefined service "%s"', $name));
            }

            $definition = $this->defined[$name];

            $parameters = $definition->getParameters();

            foreach ($parameters as &$parameter) {
                if ($parameter instanceof Closure) {
                    $parameter = $this->call($parameter);
                }
            }

            $object = $definition->getObject();

            $loader = $definition->getLoader();

            if ($loader !== null) {
                if ($object !== null) {
                    throw new ServiceResolutionException('Instantiated object cannot have loaders');
                }

                if (!is_subclass_of($loader, ServiceLoaderInterface::class)) {
                    throw new ServiceResolutionException('Invalid loader');
                }

                /**
                 * @var ServiceLoaderInterface
                 */
                $loaderInstance = $this->build($loader, $parameters);

                $service = $loaderInstance->load($this);
            } elseif ($object === null) {
                $service = $this->build($name, $parameters);
            } elseif ($object instanceof Closure) {
                $service = $this->call($object, $parameters);
            } else {
                $service = $object;
            }

            $this->resolved[$name] = $service;
        } finally {
            array_pop($this->resolveStack);
        }

        try {
            if (isset($loaderInstance) && $loaderInstance instanceof ResolutionAwareServiceLoaderInterface) {
                $loaderInstance->onResolved($service, $this);
            }
        } catch (Throwable $throwable) {
            unset($this->resolved[$name]);
            throw $throwable;
        }

        return $service;
    }

    /**
     * Build arguments for a function or method
     *
     * @param array<string, mixed> $parameters
     *
     * @return list<mixed>
     */
    private function buildArguments(ReflectionFunctionAbstract $reflectionFunctionAbstract, array $parameters = []): array
    {
        $arguments = [];

        foreach ($reflectionFunctionAbstract->getParameters() as $reflectionParameter) {
            $type = $reflectionParameter->getType();
            $name = $reflectionParameter->getName();

            if (array_key_exists($name, $parameters)) {
                if ($reflectionParameter->isVariadic()) {
                    if (!is_array($parameters[$name])) {
                        throw new ContainerException(sprintf('The parameter value for the variadic argument $%s must be an array', $name));
                    }
                    $arguments = [...$arguments, ...array_values($parameters[$name])];
                    continue;
                }

                $arguments[] = $parameters[$name];
                continue;
            }

            // Resolve class/interface if defined in the container
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $this->has($type->getName())) {
                $arguments[] = $this->get($type->getName());
                continue;
            }

            if ($reflectionParameter->isDefaultValueAvailable()) {
                $arguments[] = $reflectionParameter->getDefaultValue();
                continue;
            }

            // Skip optional parameters without default value (e.g., variadic parameters)
            if ($reflectionParameter->isOptional()) {
                continue;
            }

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                throw new ServiceNotFoundException(sprintf('Cannot instantiate argument $%s: no container definition satisfies the parameter type "%s" and no default value is available', $name, $type->getName()));
            }

            throw new ContainerException(sprintf('Cannot instantiate argument $%s: no default value is available', $name));
        }

        return $arguments;
    }
}
