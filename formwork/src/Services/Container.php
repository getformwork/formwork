<?php

namespace Formwork\Services;

use Closure;
use Exception;
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

    protected array $resolved;

    protected array $aliases;

    private array $resolveStack = [];

    public function define(string $name, ?object $object = null): ServiceDefinition
    {
        return $this->defined[$name] = new ServiceDefinition($name, $object, $this);
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
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

    public function call(Closure $closure, array $parameters = [])
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
     * @template T
     *
     * @param class-string<T>|string $name
     *
     * @return object|T
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

    public function resolve(string $name)
    {
        if (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            return $this->resolve($alias);
        }

        if (in_array($name, $this->resolveStack, true)) {
            throw new Exception(sprintf('Already resolving "%s". Resolution stack: "%s"', $name, implode('", "', $this->resolveStack)));
        }

        $this->resolveStack[] = $name;

        if (!$this->has($name)) {
            throw new Exception();
        }

        $definition = $this->defined[$name];

        $parameters = $definition->getParameters();

        foreach ($parameters as &$param) {
            if ($param instanceof Closure) {
                $param = $this->call($param);
            }
        }

        $object = $definition->getObject();

        $loader = $definition->getLoader();

        if ($loader !== null) {
            if ($object !== null) {
                throw new Exception('Instantiated object cannot have loaders');
            }

            if (!is_subclass_of($loader, ServiceLoaderInterface::class)) {
                throw new Exception('Invalid loader');
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

    private function buildArguments(ReflectionFunctionAbstract $method, array $parameters = []): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $arguments[] = $parameters[$name];
                continue;
            }

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isOptional()) {
                    continue;
                }
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new LogicException(sprintf('Cannot instantiate argument $%s', $name));
            }

            $arguments[] = $this->get($type->getName());
        }

        return $arguments;
    }
}
