<?php

namespace Formwork\Data\Traits;

use Formwork\Data\Attributes\Getter;
use Formwork\Data\Attributes\Setter;
use Formwork\Utils\Str;
use LogicException;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
trait DataAccessors
{
    /**
     * Cached data accessors
     *
     * @var array<class-string, array{
     *     getters: array<string, array{type: 'method'|'property', name: string, export: bool}>,
     *     setters: array<string, array{type: 'method'|'property', name: string}>
     * }>
     */
    private static array $dataAccessors = [];

    /**
     * Get registered data accessors
     *
     * @return array{
     *     getters: array<string, array{type: 'method'|'property', name: string, export: bool}>,
     *     setters: array<string, array{type: 'method'|'property', name: string}>
     * }
     */
    private function dataAccessors(): array
    {
        return self::$dataAccessors[static::class] ??= $this->resolveDataAccessors();
    }

    /**
     * Get registered data getters
     *
     * @return array<string, array{type: 'method'|'property', name: string, export: bool}>
     */
    private function dataGetters(): array
    {
        return $this->dataAccessors()['getters'];
    }

    /**
     * Get registered data setters
     *
     * @return array<string, array{type: 'method'|'property', name: string}>
     */
    private function dataSetters(): array
    {
        return $this->dataAccessors()['setters'];
    }

    /**
     * Resolve data accessors
     *
     * @return array{
     *     getters: array<string, array{type: 'method'|'property', name: string, export: bool}>,
     *     setters: array<string, array{type: 'method'|'property', name: string}>
     * }
     */
    private function resolveDataAccessors(): array
    {
        $reflectionClass = new ReflectionClass($this);

        $accessors = [
            'getters' => [],
            'setters' => [],
        ];

        foreach ($reflectionClass->getProperties() as $property) {
            $name = $property->getName();

            foreach ($property->getAttributes(Getter::class) as $attribute) {
                $attribute = $attribute->newInstance();
                $this->registerDataGetter(
                    $accessors['getters'],
                    $attribute->key ?? $name,
                    'property',
                    $name,
                    $attribute->export
                );
            }

            foreach ($property->getAttributes(Setter::class) as $attribute) {
                $key = $attribute->newInstance()->key ?? $name;
                $this->registerDataSetter(
                    $accessors['setters'],
                    $key,
                    'property',
                    $name
                );
            }
        }

        foreach ($reflectionClass->getMethods() as $method) {
            $name = $method->getName();

            foreach ($method->getAttributes(Getter::class) as $attribute) {
                $attribute = $attribute->newInstance();
                $this->registerDataGetter(
                    $accessors['getters'],
                    $attribute->key ?? $this->resolveDataGetterKey($method),
                    'method',
                    $name,
                    $attribute->export
                );
            }

            foreach ($method->getAttributes(Setter::class) as $attribute) {
                $attribute = $attribute->newInstance();
                $this->registerDataSetter(
                    $accessors['setters'],
                    $attribute->key ?? $this->resolveDataSetterKey($method),
                    'method',
                    $name
                );
            }
        }

        return $accessors;
    }

    /**
     * @param array<string, array{type: 'method'|'property', name: string, export: bool}> $getters
     * @param 'method'|'property'                                                         $type
     */
    private function registerDataGetter(array &$getters, string $key, string $type, string $name, bool $export): void
    {
        if (isset($getters[$key])) {
            throw new LogicException(sprintf('Multiple getters registered for data key "%s".', $key));
        }
        $getters[$key] = ['type' => $type, 'name' => $name, 'export' => $export];
    }

    /**
     * @param array<string, array{type: 'method'|'property', name: string}> $setters
     * @param 'method'|'property'                                           $type
     */
    private function registerDataSetter(array &$setters, string $key, string $type, string $name): void
    {
        if (isset($setters[$key])) {
            throw new LogicException(sprintf('Multiple setters registered for data key "%s".', $key));
        }
        $setters[$key] = ['type' => $type, 'name' => $name];
    }

    /**
     * Resolve a data getter key from a method
     */
    private function resolveDataGetterKey(ReflectionMethod $reflectionMethod): string
    {
        $name = $reflectionMethod->getName();

        foreach (['get', 'is'] as $prefix) {
            $length = strlen($prefix);

            if (Str::startsWith($name, $prefix) && isset($name[$length]) && ctype_upper($name[$length])) {
                return lcfirst(Str::after($name, $prefix));
            }
        }

        return $name;
    }

    /**
     * Resolve a data setter key from a method
     */
    private function resolveDataSetterKey(ReflectionMethod $reflectionMethod): string
    {
        $name = $reflectionMethod->getName();
        $prefix = 'set';
        $length = strlen($prefix);

        if (Str::startsWith($name, $prefix) && isset($name[$length]) && ctype_upper($name[$length])) {
            return lcfirst(Str::after($name, $prefix));
        }

        return $name;
    }
}
