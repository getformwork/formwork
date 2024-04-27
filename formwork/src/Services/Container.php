<?php

namespace Formwork\Services;

use Closure;
use Formwork\Services\Exceptions\ServiceResolutionException;
use LogicException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

class Container
{
    /**
     * @var array<string, ServiceDefinition>
     */
    protected array $defined;

    /**
     * @var array<string, object>
     */
    protected array $resolved;

    /**
     * @var array<string, string>
     */
    protected array $aliases;

    /**
     * @var list<string>
     */
    private array $resolveStack = [];

    public function define(string $name, ?object $object = null): ServiceDefinition
    {
        return $this->defined[$name] = new ServiceDefinition($name, $object, $this);
    }

    /**
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
     * @param array<string, mixed> $parameters
     */
    public function call(Closure $closure, array $parameters = []): mixed
    {
        $arguments = $this->buildArguments(new ReflectionFunction($closure), $parameters);

        return $closure(...$arguments);
    }

    public function alias(string $alias, string $target): void
    {
        if ($alias === $target) {
            throw new LogicException(sprintf('Cannot alias "%s" to itself', $target));
        }
        $this->aliases[$alias] = $target;
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|string $name
     *
     * @return ($name is class-string<T> ? T : object)
     */
    public function get(string $name): object
    {
        if (!$this->has($name)) {
            throw new LogicException(sprintf('Instance of "%s" not found', $name));
        }
        if (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            return $this->get($alias);
        }

        return $this->resolved[$name] ??= $this->resolve($name);
    }

    public function has(string $name): bool
    {
        if (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            return $this->has($alias);
        }

        return isset($this->defined[$name]);
    }

    public function isResolved(string $name): bool
    {
        if (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            return $this->isResolved($alias);
        }

        return isset($this->resolved[$name]);
    }

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

        array_pop($this->resolveStack);

        if (isset($loaderInstance) && $loaderInstance instanceof ResolutionAwareServiceLoaderInterface) {
            $loaderInstance->onResolved($service, $this);
        }

        return $service;
    }

    /**
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
                $arguments[] = $parameters[$name];
                continue;
            }

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($reflectionParameter->isOptional()) {
                    continue;
                }
                if ($reflectionParameter->isDefaultValueAvailable()) {
                    $arguments[] = $reflectionParameter->getDefaultValue();
                    continue;
                }

                throw new LogicException(sprintf('Cannot instantiate argument $%s', $name));
            }

            $arguments[] = $this->get($type->getName());
        }

        return $arguments;
    }
}
