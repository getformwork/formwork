<?php

namespace Formwork\Tests\Unit\Services;

use Formwork\Services\Container;
use Formwork\Services\ServiceDefinition;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Services\Fixtures\SimpleServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\TestServiceLoaderFixture;
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
            ->loader(TestServiceLoaderFixture::class)
            ->alias('service.alias');

        $this->assertSame($serviceDefinition, $serviceDefinition->loader(TestServiceLoaderFixture::class));
        $this->assertSame(TestServiceLoaderFixture::class, $serviceDefinition->getLoader());
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
        $simpleService = new SimpleServiceFixture();
        $serviceDefinition = (new Container())->define('service', $simpleService);

        $this->assertSame($simpleService, $serviceDefinition->getObject());
    }

    public function testLazyFalseDoesNotResolveTheDefinition(): void
    {
        $container = new Container();
        $serviceDefinition = $container->define(SimpleServiceFixture::class);

        $this->assertFalse($container->isResolved(SimpleServiceFixture::class));
        $this->assertSame($serviceDefinition, $serviceDefinition->lazy(false));
        $this->assertFalse($container->isResolved(SimpleServiceFixture::class));
    }

    public function testLazyTrueLeavesAnUnresolvedDefinitionUnresolved(): void
    {
        $container = new Container();
        $serviceDefinition = $container->define(SimpleServiceFixture::class);

        $this->assertSame($serviceDefinition, $serviceDefinition->lazy(true));
        $this->assertFalse($container->isResolved(SimpleServiceFixture::class));
    }

    public function testDefinitionsAreLazyByDefault(): void
    {
        $this->assertTrue((new Container())->define(SimpleServiceFixture::class)->isLazy());
    }

    public function testLazinessCanBeChangedAndRestored(): void
    {
        $serviceDefinition = (new Container())->define(SimpleServiceFixture::class);

        $serviceDefinition->lazy(false);
        $this->assertFalse($serviceDefinition->isLazy());

        $serviceDefinition->lazy(true);
        $this->assertTrue($serviceDefinition->isLazy());
    }

    public function testLazinessIsIndependentFromTheAliasesAndParametersOfTheDefinition(): void
    {
        $serviceDefinition = (new Container())->define(SimpleServiceFixture::class)
            ->alias('alias')
            ->parameter('name', 'value')
            ->lazy(false);

        $this->assertFalse($serviceDefinition->isLazy());
        $this->assertSame(['name' => 'value'], $serviceDefinition->getParameters());
    }

    public function testCallingLazyFalseAfterResolutionDoesNotReplaceTheInstance(): void
    {
        $container = new Container();
        $serviceDefinition = $container->define(SimpleServiceFixture::class);
        $simpleService = $container->get(SimpleServiceFixture::class);

        $serviceDefinition->lazy(false);

        $this->assertSame($simpleService, $container->get(SimpleServiceFixture::class));
    }

    public function testObjectDefinitionsRejectLoaders(): void
    {
        $serviceDefinition = (new Container())->define('service', new SimpleServiceFixture());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Instantiated object cannot have loaders');
        $serviceDefinition->loader(TestServiceLoaderFixture::class);
    }

    public function testAliasesResolveToTheSameInstanceAsTheDefinition(): void
    {
        $container = new Container();
        $container->define(SimpleServiceFixture::class)->alias('simple');

        $this->assertSame($container->get(SimpleServiceFixture::class), $container->get('simple'));
    }
}
