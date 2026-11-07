<?php

namespace Formwork\Tests\Unit\Services\Fixtures;

use Formwork\Services\Container;
use Formwork\Services\DefinitionAwareServiceLoaderInterface;

final class DefinitionAwareLoaderFixture implements DefinitionAwareServiceLoaderInterface
{
    /**
     * Containers the loader has been defined in
     *
     * @var list<Container>
     */
    public static array $defined = [];

    public static int $instances = 0;

    public function __construct()
    {
        ++self::$instances;
    }

    public static function onDefined(Container $container): void
    {
        self::$defined[] = $container;
    }

    public function load(Container $container): object
    {
        return new SimpleServiceFixture();
    }
}
