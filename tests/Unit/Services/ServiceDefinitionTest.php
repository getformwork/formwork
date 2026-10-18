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
        $serviceDefinition = (new Container())->define('service')
            ->parameter('options.enabled', true)
            ->parameter('options.name', 'example');

        $this->assertSame([
            'options' => [
                'enabled' => true,
                'name'    => 'example',
            ],
        ], $serviceDefinition->getParameters());
    }

    public function testParameterReplacementUsesTheLatestValue(): void
    {
        $serviceDefinition = (new Container())->define('service')
            ->parameter('option', 'first')
            ->parameter('option', 'second');

        $this->assertSame(['option' => 'second'], $serviceDefinition->getParameters());
    }

    public function testLoaderAndAliasConfigurationAreFluent(): void
    {
        $container = new Container();
        $serviceDefinition = $container->define('service')
            ->loader(TestServiceLoader::class)
            ->alias('service.alias');

        $this->assertSame($serviceDefinition, $serviceDefinition->loader(TestServiceLoader::class));
        $this->assertSame(TestServiceLoader::class, $serviceDefinition->getLoader());
        $this->assertTrue($container->has('service.alias'));
    }

    public function testNewDefinitionsStartWithoutAObjectOrLoader(): void
    {
        $serviceDefinition = (new Container())->define('service');

        $this->assertNull($serviceDefinition->getObject());
        $this->assertNull($serviceDefinition->getLoader());
        $this->assertSame([], $serviceDefinition->getParameters());
    }

    public function testDirectObjectDefinitionsExposeTheirObject(): void
    {
        $simpleService = new SimpleService();
        $serviceDefinition = (new Container())->define('service', $simpleService);

        $this->assertSame($simpleService, $serviceDefinition->getObject());
    }

    public function testLazyFalseDoesNotResolveTheDefinition(): void
    {
        $container = new Container();
        $serviceDefinition = $container->define(SimpleService::class);

        $this->assertFalse($container->isResolved(SimpleService::class));
        $this->assertSame($serviceDefinition, $serviceDefinition->lazy(false));
        $this->assertFalse($container->isResolved(SimpleService::class));
    }

    public function testLazyTrueLeavesAnUnresolvedDefinitionUnresolved(): void
    {
        $container = new Container();
        $serviceDefinition = $container->define(SimpleService::class);

        $this->assertSame($serviceDefinition, $serviceDefinition->lazy(true));
        $this->assertFalse($container->isResolved(SimpleService::class));
    }

    public function testDefinitionsAreLazyByDefault(): void
    {
        $this->assertTrue((new Container())->define(SimpleService::class)->isLazy());
    }

    public function testLazinessCanBeChangedAndRestored(): void
    {
        $serviceDefinition = (new Container())->define(SimpleService::class);

        $serviceDefinition->lazy(false);
        $this->assertFalse($serviceDefinition->isLazy());

        $serviceDefinition->lazy(true);
        $this->assertTrue($serviceDefinition->isLazy());
    }

    public function testLazinessIsIndependentFromTheAliasesAndParametersOfTheDefinition(): void
    {
        $serviceDefinition = (new Container())->define(SimpleService::class)
            ->alias('alias')
            ->parameter('name', 'value')
            ->lazy(false);

        $this->assertFalse($serviceDefinition->isLazy());
        $this->assertSame(['name' => 'value'], $serviceDefinition->getParameters());
    }

    public function testCallingLazyFalseAfterResolutionDoesNotReplaceTheInstance(): void
    {
        $container = new Container();
        $serviceDefinition = $container->define(SimpleService::class);
        $simpleService = $container->get(SimpleService::class);

        $serviceDefinition->lazy(false);

        $this->assertSame($simpleService, $container->get(SimpleService::class));
    }

    public function testObjectDefinitionsRejectLoaders(): void
    {
        $serviceDefinition = (new Container())->define('service', new SimpleService());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Instantiated object cannot have loaders');
        $serviceDefinition->loader(TestServiceLoader::class);
    }

    public function testAliasesResolveToTheSameInstanceAsTheDefinition(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)->alias('simple');

        $this->assertSame($container->get(SimpleService::class), $container->get('simple'));
    }
}
