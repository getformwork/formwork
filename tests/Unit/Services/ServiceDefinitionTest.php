<?php

namespace Formwork\Tests\Unit\Services;

use Formwork\Services\Container;
use Formwork\Services\ServiceDefinition;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Services\Fixtures\SimpleService;
use Formwork\Tests\Unit\Services\Fixtures\TestServiceLoader;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ServiceDefinition::class)]
final class ServiceDefinitionTest extends TestCase
{
    public function testParametersCanBeAddedAndRead(): void
    {
        $definition = (new Container())->define('service')
            ->parameter('options.enabled', true)
            ->parameter('options.name', 'example');

        $this->assertSame([
            'options' => [
                'enabled' => true,
                'name'    => 'example',
            ],
        ], $definition->getParameters());
    }

    public function testParameterReplacementUsesTheLatestValue(): void
    {
        $definition = (new Container())->define('service')
            ->parameter('option', 'first')
            ->parameter('option', 'second');

        $this->assertSame(['option' => 'second'], $definition->getParameters());
    }

    public function testLoaderAndAliasConfigurationAreFluent(): void
    {
        $container = new Container();
        $definition = $container->define('service')
            ->loader(TestServiceLoader::class)
            ->alias('service.alias');

        $this->assertSame($definition, $definition->loader(TestServiceLoader::class));
        $this->assertSame(TestServiceLoader::class, $definition->getLoader());
        $this->assertTrue($container->has('service.alias'));
    }

    public function testNewDefinitionsStartWithoutAObjectOrLoader(): void
    {
        $definition = (new Container())->define('service');

        $this->assertNull($definition->getObject());
        $this->assertNull($definition->getLoader());
        $this->assertSame([], $definition->getParameters());
    }

    public function testDirectObjectDefinitionsExposeTheirObject(): void
    {
        $object = new SimpleService();
        $definition = (new Container())->define('service', $object);

        $this->assertSame($object, $definition->getObject());
    }

    public function testLazyFalseEagerlyResolvesTheDefinition(): void
    {
        $container = new Container();
        $definition = $container->define(SimpleService::class);

        $this->assertFalse($container->isResolved(SimpleService::class));
        $this->assertSame($definition, $definition->lazy(false));
        $this->assertTrue($container->isResolved(SimpleService::class));
    }

    public function testLazyTrueLeavesAnUnresolvedDefinitionUnresolved(): void
    {
        $container = new Container();
        $definition = $container->define(SimpleService::class);

        $this->assertSame($definition, $definition->lazy(true));
        $this->assertFalse($container->isResolved(SimpleService::class));
    }

    public function testCallingLazyFalseAfterResolutionDoesNotReplaceTheInstance(): void
    {
        $container = new Container();
        $definition = $container->define(SimpleService::class);
        $resolved = $container->get(SimpleService::class);

        $definition->lazy(false);

        $this->assertSame($resolved, $container->get(SimpleService::class));
    }

    public function testObjectDefinitionsRejectLoaders(): void
    {
        $definition = (new Container())->define('service', new SimpleService());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Instantiated object cannot have loaders');
        $definition->loader(TestServiceLoader::class);
    }

    public function testAliasesResolveToTheSameInstanceAsTheDefinition(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)->alias('simple');

        $this->assertSame($container->get(SimpleService::class), $container->get('simple'));
    }
}
