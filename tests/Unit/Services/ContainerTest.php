<?php

namespace Formwork\Tests\Unit\Services;

use Closure;
use Formwork\Services\Container;
use Formwork\Services\Exceptions\ContainerException;
use Formwork\Services\Exceptions\ServiceNotFoundException;
use Formwork\Services\Exceptions\ServiceResolutionException;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Services\Fixtures\AbstractService;
use Formwork\Tests\Unit\Services\Fixtures\AnotherDependentService;
use Formwork\Tests\Unit\Services\Fixtures\ArrayOptionsService;
use Formwork\Tests\Unit\Services\Fixtures\AttributeDependentService;
use Formwork\Tests\Unit\Services\Fixtures\CircularA;
use Formwork\Tests\Unit\Services\Fixtures\CircularB;
use Formwork\Tests\Unit\Services\Fixtures\ContainerAwareLoader;
use Formwork\Tests\Unit\Services\Fixtures\DependencyContract;
use Formwork\Tests\Unit\Services\Fixtures\DependencyResolvingLoader;
use Formwork\Tests\Unit\Services\Fixtures\DependentOnContract;
use Formwork\Tests\Unit\Services\Fixtures\DependentOnMissingService;
use Formwork\Tests\Unit\Services\Fixtures\DependentOnSharedDependency;
use Formwork\Tests\Unit\Services\Fixtures\DependentService;
use Formwork\Tests\Unit\Services\Fixtures\FactoryProduct;
use Formwork\Tests\Unit\Services\Fixtures\FailingResolutionAwareLoader;
use Formwork\Tests\Unit\Services\Fixtures\InvalidReturnLoader;
use Formwork\Tests\Unit\Services\Fixtures\LoadedService;
use Formwork\Tests\Unit\Services\Fixtures\NestedDependentService;
use Formwork\Tests\Unit\Services\Fixtures\OptionalAttributeDependentService;
use Formwork\Tests\Unit\Services\Fixtures\OptionalDependencyService;
use Formwork\Tests\Unit\Services\Fixtures\OptionalIntegerService;
use Formwork\Tests\Unit\Services\Fixtures\OptionalStringService;
use Formwork\Tests\Unit\Services\Fixtures\ParameterizedLoader;
use Formwork\Tests\Unit\Services\Fixtures\PrivateConstructorService;
use Formwork\Tests\Unit\Services\Fixtures\RecursiveLoader;
use Formwork\Tests\Unit\Services\Fixtures\ScalarDependentService;
use Formwork\Tests\Unit\Services\Fixtures\SharedDependency;
use Formwork\Tests\Unit\Services\Fixtures\SimpleService;
use Formwork\Tests\Unit\Services\Fixtures\TestServiceLoader;
use Formwork\Tests\Unit\Services\Fixtures\ThrowingLoader;
use Formwork\Tests\Unit\Services\Fixtures\VariadicService;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Throwable;
use TypeError;

#[CoversClass(Container::class)]
final class ContainerTest extends TestCase
{
    protected function setUp(): void
    {
        TestServiceLoader::$loadCount = 0;
        TestServiceLoader::$resolvedCount = 0;
        FailingResolutionAwareLoader::$loadCount = 0;
    }

    public function testDefineExposesAServiceDefinitionWithoutResolvingIt(): void
    {
        $container = new Container();
        $definition = $container->define(SimpleService::class);

        $this->assertSame(SimpleService::class, $definition->getName());
        $this->assertNull($definition->getObject());
        $this->assertFalse($container->isResolved(SimpleService::class));
        $this->assertTrue($container->has(SimpleService::class));
    }

    public function testGetBuildsAndSharesAClassService(): void
    {
        $container = new Container();
        $container->define(SimpleService::class);

        $first = $container->get(SimpleService::class);
        $second = $container->get(SimpleService::class);

        $this->assertInstanceOf(SimpleService::class, $first);
        $this->assertSame($first, $second);
        $this->assertTrue($container->isResolved(SimpleService::class));
    }

    public function testDirectObjectsAreReturnedAndShared(): void
    {
        $container = new Container();
        $service = new SimpleService();
        $container->define('configured', $service);

        $this->assertSame($service, $container->get('configured'));
        $this->assertSame($service, $container->get('configured'));
    }

    public function testBuildInjectsRegisteredDependenciesAndStaticParameters(): void
    {
        $container = new Container();
        $dependency = new SharedDependency();
        $container->define(SharedDependency::class, $dependency);
        $container->define(DependentService::class)
            ->parameter('label', 'configured');

        $service = $container->get(DependentService::class);

        $this->assertSame($dependency, $service->dependency);
        $this->assertSame('configured', $service->label);
    }

    public function testDependenciesCanBeResolvedThroughAnInterfaceAlias(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class)->alias(DependencyContract::class);
        $container->define(DependentOnContract::class);

        $service = $container->get(DependentOnContract::class);

        $this->assertInstanceOf(SharedDependency::class, $service->dependency);
        $this->assertSame($container->get(DependencyContract::class), $service->dependency);
    }

    public function testServiceAttributeSelectsAConfiguredIdentifier(): void
    {
        $container = new Container();
        $selected = new SimpleService();
        $container->define('selected', $selected);
        $container->define(AttributeDependentService::class);

        $service = $container->get(AttributeDependentService::class);

        $this->assertSame($selected, $service->service);
    }

    public function testServiceAttributeFallsBackToTheDefaultWhenTheIdentifierIsNotDefined(): void
    {
        $container = new Container();
        $container->define(OptionalAttributeDependentService::class);

        $this->assertNull($container->get(OptionalAttributeDependentService::class)->service);
    }

    public function testServiceAttributeTakesPrecedenceOverTheDefaultWhenTheIdentifierIsDefined(): void
    {
        $container = new Container();
        $selected = new SimpleService();
        $container->define('selected', $selected);
        $container->define(OptionalAttributeDependentService::class);

        $this->assertSame($selected, $container->get(OptionalAttributeDependentService::class)->service);
    }

    public function testServiceAttributeWithoutDefaultReportsTheMissingIdentifier(): void
    {
        $container = new Container();
        $container->define(AttributeDependentService::class);

        try {
            $container->get(AttributeDependentService::class);
            $this->fail('The undefined service should have been reported.');
        } catch (ServiceNotFoundException $exception) {
            $this->assertStringContainsString('"selected"', $exception->getMessage());
        }
    }

    public function testClosuresCanBeUsedAsFactoriesAndAreShared(): void
    {
        $container = new Container();
        $dependency = new SharedDependency();
        $container->define(SharedDependency::class, $dependency);
        $container->define('factory', static function (SharedDependency $dependency, string $value): FactoryProduct {
            return new FactoryProduct($dependency, $value);
        })->parameter('value', 'factory-value');

        $first = $container->get('factory');
        $second = $container->get('factory');

        $this->assertInstanceOf(FactoryProduct::class, $first);
        $this->assertSame($dependency, $first->dependency);
        $this->assertSame('factory-value', $first->value);
        $this->assertSame($first, $second);
    }

    public function testFactoryExceptionsArePropagated(): void
    {
        $container = new Container();
        $container->define('factory', static function (): object {
            throw new RuntimeException('factory failed');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('factory failed');
        $container->get('factory');
    }

    public function testFactoryReturningAnInvalidValueFailsAtTheFactoryBoundary(): void
    {
        $container = new Container();
        $container->define('factory', static fn() => 'invalid');

        $this->expectException(ContainerExceptionInterface::class);
        $container->get('factory');
    }

    public function testBuildAndCallResolveArgumentsAndHonorExplicitParameters(): void
    {
        $container = new Container();
        $dependency = new SharedDependency();
        $container->define(SharedDependency::class, $dependency);

        $built = $container->build(DependentService::class, ['label' => 'built']);
        $called = $container->call(static fn(SharedDependency $dependency, string $label): FactoryProduct => new FactoryProduct($dependency, $label), ['label' => 'called']);

        $this->assertSame($dependency, $built->dependency);
        $this->assertSame('built', $built->label);
        $this->assertSame($dependency, $called->dependency);
        $this->assertSame('called', $called->value);
    }

    public function testBuildExplicitArgumentsOverrideRegisteredDependencies(): void
    {
        $container = new Container();
        $registered = new SharedDependency();
        $explicit = new SharedDependency();
        $container->define(SharedDependency::class, $registered);

        $service = $container->build(DependentService::class, [
            'dependency' => $explicit,
            'label'      => 'explicit',
        ]);

        $this->assertSame($explicit, $service->dependency);
        $this->assertNotSame($registered, $service->dependency);
    }

    public function testCallExplicitArgumentsOverrideRegisteredDependencies(): void
    {
        $container = new Container();
        $registered = new SharedDependency();
        $explicit = new SharedDependency();
        $container->define(SharedDependency::class, $registered);

        $result = $container->call(
            static fn(SharedDependency $dependency): SharedDependency => $dependency,
            ['dependency' => $explicit],
        );

        $this->assertSame($explicit, $result);
    }

    public function testBuildAndCallSupportVariadicNamedArguments(): void
    {
        $container = new Container();

        $built = $container->build(VariadicService::class, ['values' => ['a', 'b']]);
        $called = $container->call(static fn(string ...$values): array => $values, ['values' => ['x', 'y']]);

        $this->assertSame(['a', 'b'], $built->values);
        $this->assertSame(['x', 'y'], $called);
    }

    public function testVariadicArgumentsMustBeArrays(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $container->call(static fn(string ...$values): array => $values, ['values' => 'invalid']);
    }

    public function testVariadicArgumentsAreAlwaysPassedByPosition(): void
    {
        $container = new Container();

        $built = $container->build(VariadicService::class, ['values' => ['first' => 'a', 'second' => 'b']]);

        $this->assertSame(['a', 'b'], $built->values);
    }

    public function testVariadicParametersAreOptional(): void
    {
        $container = new Container();

        $this->assertSame([], $container->build(VariadicService::class)->values);
        $this->assertSame([], $container->call(static fn(string ...$values): array => $values));
    }

    public function testParameterClosuresAreEvaluatedBeforeFactoryResolution(): void
    {
        $container = new Container();
        $dependency = new SharedDependency();
        $container->define('factory', static fn(SharedDependency $dependency): FactoryProduct => new FactoryProduct($dependency, 'closure'))
            ->parameter('dependency', static fn(): SharedDependency => $dependency);

        $service = $container->get('factory');

        $this->assertSame($dependency, $service->dependency);
        $this->assertSame('closure', $service->value);
    }

    public function testParameterClosuresInsideNestedParametersAreEvaluated(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsService::class)
            ->parameter('options.lazy', static fn(): string => 'evaluated')
            ->parameter('options.plain', 2);

        $this->assertSame(['lazy' => 'evaluated', 'plain' => 2], $container->get(ArrayOptionsService::class)->options);
    }

    public function testDottedParameterNamesBuildNestedArrays(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsService::class)
            ->parameter('options.a.b', 1)
            ->parameter('options.a.c', 2);

        $this->assertSame(['a' => ['b' => 1, 'c' => 2]], $container->get(ArrayOptionsService::class)->options);
    }

    public function testResolutionStackIsClearedWhenAParameterClosureFails(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsService::class)
            ->parameter('options', static function (): never {
                throw new RuntimeException('parameter failed');
            });

        foreach ([1, 2] as $attempt) {
            try {
                $container->get(ArrayOptionsService::class);
                $this->fail('The parameter closure exception should have propagated.');
            } catch (RuntimeException $exception) {
                $this->assertSame('parameter failed', $exception->getMessage(), "Attempt $attempt");
            }
        }
    }

    public function testLazyFalseDoesNotResolveImmediately(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)->lazy(false);

        $this->assertFalse($container->isResolved(SimpleService::class));
        $this->assertInstanceOf(SimpleService::class, $container->get(SimpleService::class));
    }

    public function testEagerServicesAreResolvedExplicitly(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)->lazy(false);
        $container->define(SharedDependency::class);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved(SimpleService::class));
        $this->assertFalse($container->isResolved(SharedDependency::class));
    }

    public function testResolvingEagerServicesOnAnEmptyContainerDoesNothing(): void
    {
        $container = new Container();

        $container->resolveEagerServices();

        $this->assertFalse($container->has(SimpleService::class));
    }

    public function testResolvingEagerServicesTwiceKeepsTheSharedInstances(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)->lazy(false);

        $container->resolveEagerServices();
        $first = $container->get(SimpleService::class);
        $container->resolveEagerServices();

        $this->assertSame($first, $container->get(SimpleService::class));
    }

    public function testEagerServicesAlreadyResolvedAreNotResolvedAgain(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class, new SharedDependency());
        $container->define('loaded')->loader(TestServiceLoader::class)->lazy(false);
        $container->get('loaded');

        $container->resolveEagerServices();

        $this->assertSame(1, TestServiceLoader::$loadCount);
        $this->assertSame(1, TestServiceLoader::$resolvedCount);
    }

    public function testEagerServicesUseTheConfigurationDefinedAfterLazyFalse(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsService::class)
            ->lazy(false)
            ->parameter('options', ['configured' => true]);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved(ArrayOptionsService::class));
        $this->assertSame(['configured' => true], $container->get(ArrayOptionsService::class)->options);
    }

    public function testEagerLoaderServicesAreLoadedOnce(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class, new SharedDependency());
        $container->define('loaded')->loader(TestServiceLoader::class)->lazy(false);

        $container->resolveEagerServices();
        $container->resolveEagerServices();

        $this->assertInstanceOf(LoadedService::class, $container->get('loaded'));
        $this->assertSame(1, TestServiceLoader::$loadCount);
        $this->assertSame(1, TestServiceLoader::$resolvedCount);
    }

    public function testEagerServicesResolveTheirLazyDependencies(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class);
        $container->define(DependentOnSharedDependency::class)->lazy(false);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved(SharedDependency::class));
    }

    public function testEagerServicesCanBeReachedThroughTheirAliases(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)->alias('simple')->lazy(false);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved('simple'));
    }

    public function testEagerServicesDefinedWhileResolvingEagerServicesAreResolvedToo(): void
    {
        $container = new Container();
        $container->define('first', static function () use ($container): SimpleService {
            $container->define('second', static fn(): SharedDependency => new SharedDependency())->lazy(false);
            return new SimpleService();
        })->lazy(false);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved('second'));
    }

    public function testFailingEagerServicesPropagateTheirException(): void
    {
        $container = new Container();
        $container->define('failing', static function (): never {
            throw new RuntimeException('eager failure');
        })->lazy(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('eager failure');
        $container->resolveEagerServices();
    }

    public function testFailingEagerServicesAreNotMarkedAsResolved(): void
    {
        $container = new Container();
        $container->define('failing', static function (): never {
            throw new RuntimeException('eager failure');
        })->lazy(false);

        try {
            $container->resolveEagerServices();
        } catch (RuntimeException) {
        }

        $this->assertFalse($container->isResolved('failing'));
    }

    public function testParametersSetAfterLazyFalseAreApplied(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsService::class)
            ->lazy(false)
            ->parameter('options', ['configured' => true]);

        $this->assertSame(['configured' => true], $container->get(ArrayOptionsService::class)->options);
    }

    public function testLoaderSetAfterLazyFalseIsApplied(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class, new SharedDependency());
        $container->define('loaded')
            ->lazy(false)
            ->loader(TestServiceLoader::class);

        $this->assertInstanceOf(LoadedService::class, $container->get('loaded'));
        $this->assertSame(1, TestServiceLoader::$loadCount);
    }

    public function testLazyFalseServicesAreResolvedOnlyOnce(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class, new SharedDependency());
        $container->define('loaded')
            ->loader(TestServiceLoader::class)
            ->lazy(false);

        $first = $container->get('loaded');

        $this->assertSame($first, $container->get('loaded'));
        $this->assertSame(1, TestServiceLoader::$loadCount);
        $this->assertSame(1, TestServiceLoader::$resolvedCount);
    }

    public function testAliasesResolveToTheTargetAndPreserveItsIdentity(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)
            ->alias('first')
            ->alias('second');

        $service = $container->get('first');

        $this->assertSame($service, $container->get('second'));
        $this->assertTrue($container->has('first'));
        $this->assertFalse($container->has('unknown'));
        $this->assertTrue($container->isResolved('second'));
    }

    public function testCircularAliasesAreRejected(): void
    {
        $container = new Container();
        $container->alias('one', 'two');

        $this->expectException(ContainerException::class);
        $container->alias('two', 'one');
    }

    public function testRejectedAliasesLeaveExistingAliasesUntouched(): void
    {
        $container = new Container();
        $container->alias('a', 'b');
        $container->alias('b', 'c');

        try {
            $container->alias('c', 'a');
            $this->fail('The circular alias should have been rejected.');
        } catch (ContainerException) {
        }

        $service = new SimpleService();
        $container->define('c', $service);

        $this->assertSame($service, $container->get('a'));
        $this->assertFalse($container->has('d'));
    }

    public function testAliasToAnUndefinedServiceIsNotAvailableUntilTheTargetIsDefined(): void
    {
        $container = new Container();
        $container->alias('alias', 'target');

        $this->assertFalse($container->has('alias'));
        $this->assertFalse($container->isResolved('alias'));

        try {
            $container->get('alias');
            $this->fail('The undefined target should have been reported.');
        } catch (ServiceNotFoundException $exception) {
            $this->assertStringContainsString('"target"', $exception->getMessage());
        }

        $service = new SimpleService();
        $container->define('target', $service);

        $this->assertTrue($container->has('alias'));
        $this->assertSame($service, $container->get('alias'));
    }

    public function testAliasToItselfIsRejected(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $container->alias('same', 'same');
    }

    public function testAliasCannotShadowADefinedService(): void
    {
        $container = new Container();
        $own = new SimpleService();
        $container->define('name', $own);
        $container->define('target', new SimpleService());

        try {
            $container->alias('name', 'target');
            $this->fail('The alias would hide the defined service.');
        } catch (ContainerException $exception) {
            $this->assertStringContainsString('name', $exception->getMessage());
        }

        $this->assertSame($own, $container->get('name'));
    }

    public function testDefinitionAliasCannotShadowADefinedService(): void
    {
        $container = new Container();
        $own = new SimpleService();
        $container->define('name', $own);

        try {
            $container->define('target', new SimpleService())->alias('name');
            $this->fail('The alias would hide the defined service.');
        } catch (ContainerException) {
        }

        $this->assertSame($own, $container->get('name'));
    }

    public function testInterfaceAliasCanBeDefinedBeforeItsTargetAndRepointedLater(): void
    {
        $container = new Container();
        $container->alias(DependencyContract::class, 'first');
        $container->define('first', new SimpleService());
        $container->define('second', new SimpleService());

        $this->assertSame($container->get('first'), $container->get(DependencyContract::class));

        $container->alias(DependencyContract::class, 'second');

        $this->assertSame($container->get('second'), $container->get(DependencyContract::class));
    }

    public function testDefiningAServiceWithTheNameOfAnAliasMakesTheNewDefinitionReachable(): void
    {
        $container = new Container();
        $container->define('target', new SimpleService());
        $container->alias('name', 'target');

        $own = new SimpleService();
        $container->define('name', $own);

        $this->assertSame($own, $container->get('name'));
    }

    public function testLoaderIsConstructedWithDependenciesAndCalledOnlyOnce(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class, new SharedDependency());
        $container->define('loaded')->loader(TestServiceLoader::class);
        $container->define(TestServiceLoader::class);

        $first = $container->get('loaded');
        $second = $container->get('loaded');

        $this->assertInstanceOf(LoadedService::class, $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, TestServiceLoader::$loadCount);
        $this->assertSame(1, TestServiceLoader::$resolvedCount);
    }

    public function testResolvingAnAlreadyResolvedServiceKeepsTheSharedInstance(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(TestServiceLoader::class);
        $container->define(SharedDependency::class, new SharedDependency());

        $first = $container->get('loaded');
        $second = $container->resolve('loaded');

        $this->assertSame($first, $second);
        $this->assertSame($first, $container->get('loaded'));
        $this->assertSame(1, TestServiceLoader::$loadCount);
        $this->assertSame(1, TestServiceLoader::$resolvedCount);
    }

    public function testAliasesResolveLoaderBackedServicesToTheSharedInstance(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class, new SharedDependency());
        $container->define('loaded')->loader(TestServiceLoader::class)->alias('loaded.alias');
        $container->define(TestServiceLoader::class);

        $this->assertSame($container->get('loaded'), $container->get('loaded.alias'));
        $this->assertSame(1, TestServiceLoader::$loadCount);
    }

    public function testLoaderCanReturnAServiceThatDependsOnTheContainer(): void
    {
        $container = new Container();
        $container->define(Container::class, $container);
        $container->define('loaded')->loader(ContainerAwareLoader::class);
        $container->define(ContainerAwareLoader::class);

        $service = $container->get('loaded');

        $this->assertSame('container-aware', $service->name);
    }

    public function testLoaderReceivesExplicitConstructorParameters(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(ParameterizedLoader::class)->parameter('label', 'configured');

        $service = $container->get('loaded');

        $this->assertSame('configured', $service->name);
    }

    public function testLoaderCanResolveAnotherRegisteredService(): void
    {
        $container = new Container();
        $dependency = new SharedDependency();
        $container->define(SharedDependency::class, $dependency);
        $container->define('loaded')->loader(DependencyResolvingLoader::class);
        $container->define(DependencyResolvingLoader::class);

        $service = $container->get('loaded');

        $this->assertSame($dependency, $service->dependency);
    }

    public function testRecursiveLoaderResolutionIsRejected(): void
    {
        $container = new Container();
        $container->define('first')->loader(RecursiveLoader::class)->parameter('other', 'second');
        $container->define('second')->loader(RecursiveLoader::class)->parameter('other', 'first');

        $this->expectException(ServiceResolutionException::class);
        $container->get('first');
    }

    public function testLoaderExceptionsArePropagatedAndTheServiceRemainsUnresolved(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(ThrowingLoader::class);

        try {
            $container->get('loaded');
            $this->fail('The loader should have thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('loader failed', $exception->getMessage());
        }

        $this->assertTrue($container->has('loaded'));
        $this->assertFalse($container->isResolved('loaded'));
    }

    public function testLoaderReturningAnInvalidValueFailsAtTheLoaderBoundary(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(InvalidReturnLoader::class);

        $this->expectException(TypeError::class);
        $container->get('loaded');
    }

    public function testObjectDefinitionsCannotAlsoHaveLoaders(): void
    {
        $definition = (new Container())->define('service', new SimpleService());

        $this->expectException(LogicException::class);
        $definition->loader(TestServiceLoader::class);
    }

    public function testInvalidLoaderIsRejectedDuringResolution(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(SimpleService::class);

        $this->expectException(ServiceResolutionException::class);
        $this->expectExceptionMessage('Invalid loader');
        $container->get('loaded');
    }

    public function testResolutionAwareLoaderFailureDoesNotLeaveAResolvedService(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(FailingResolutionAwareLoader::class);
        $container->define(FailingResolutionAwareLoader::class);

        $this->expectException(RuntimeException::class);
        $container->get('loaded');
    }

    public function testResolutionAwareLoaderFailureCanBeRetried(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(FailingResolutionAwareLoader::class);
        $container->define(FailingResolutionAwareLoader::class);

        try {
            $container->get('loaded');
        } catch (RuntimeException) {
        }

        $this->assertFalse($container->isResolved('loaded'));
        $this->assertSame(1, FailingResolutionAwareLoader::$loadCount);
    }

    public function testUnknownServicesHaveThePsrNotFoundException(): void
    {
        $container = new Container();

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('missing');
        $container->get('missing');
    }

    public function testResolveRejectsUndefinedServices(): void
    {
        $container = new Container();

        $this->expectException(ServiceResolutionException::class);
        $container->resolve('missing');
    }

    #[DataProvider('uninstantiableClassProvider')]
    public function testClassesThatCannotBeInstantiatedAreReportedAsContainerErrors(string $class): void
    {
        $container = new Container();
        $container->define($class);

        try {
            $container->get($class);
            $this->fail('The class cannot be instantiated.');
        } catch (Throwable $throwable) {
            $this->assertInstanceOf(ContainerExceptionInterface::class, $throwable, $throwable::class . ': ' . $throwable->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function uninstantiableClassProvider(): iterable
    {
        yield 'interface' => [DependencyContract::class];
        yield 'abstract class' => [AbstractService::class];
        yield 'private constructor' => [PrivateConstructorService::class];
        yield 'identifier that is not a class' => ['config'];
        yield 'missing class' => ['Formwork\Tests\Unit\Services\Fixtures\DoesNotExist'];
    }

    public function testFactoryReturningANonObjectDoesNotMarkTheServiceAsResolved(): void
    {
        $container = new Container();
        $container->define('factory', static fn() => 'not an object');

        try {
            $container->get('factory');
            $this->fail('The invalid factory result should have been rejected.');
        } catch (ContainerExceptionInterface) {
        }

        $this->assertFalse($container->isResolved('factory'));
    }

    public function testMissingClassDependenciesAreReportedAsNotFound(): void
    {
        $container = new Container();
        $container->define(DependentOnMissingService::class);

        $this->expectException(ServiceNotFoundException::class);
        $container->get(DependentOnMissingService::class);
    }

    public function testNestedDependenciesAndSharedDependenciesPreserveIdentity(): void
    {
        $container = new Container();
        $container->define(SharedDependency::class);
        $container->define(DependentOnSharedDependency::class);
        $container->define(NestedDependentService::class);
        $container->define(AnotherDependentService::class);

        $nested = $container->get(NestedDependentService::class);
        $another = $container->get(AnotherDependentService::class);

        $this->assertSame($nested->dependency->dependency, $another->dependency);
    }

    public function testOptionalMissingDependenciesUseTheirDefault(): void
    {
        $container = new Container();
        $container->define(OptionalDependencyService::class);

        $this->assertNull($container->get(OptionalDependencyService::class)->dependency);
    }

    public function testFailedResolutionDoesNotMakeAServiceResolved(): void
    {
        $container = new Container();
        $container->define(DependentOnMissingService::class);

        try {
            $container->get(DependentOnMissingService::class);
            $this->fail('The dependency should have been missing.');
        } catch (ServiceNotFoundException) {
        }

        $this->assertTrue($container->has(DependentOnMissingService::class));
        $this->assertFalse($container->isResolved(DependentOnMissingService::class));
    }

    public function testDefiningTheSameIdentifierReplacesAnUnresolvedDefinition(): void
    {
        $container = new Container();
        $first = new SimpleService();
        $second = new SimpleService();
        $container->define('service', $first);
        $container->define('service', $second);

        $this->assertSame($second, $container->get('service'));
    }

    public function testAliasCanBeRepointedAndCanTargetAnotherAlias(): void
    {
        $container = new Container();
        $container->define('first', new SimpleService());
        $container->define('second', new SimpleService());
        $container->alias('stable', 'first');
        $container->alias('indirect', 'stable');

        $this->assertSame($container->get('first'), $container->get('indirect'));

        $container->alias('stable', 'second');

        $this->assertSame($container->get('second'), $container->get('stable'));
        $this->assertNotSame($container->get('first'), $container->get('stable'));
    }

    public function testMissingScalarArgumentsAreContainerErrors(): void
    {
        $container = new Container();
        $container->define(ScalarDependentService::class);

        $this->expectException(ContainerException::class);
        $container->get(ScalarDependentService::class);
    }

    public function testCircularDependenciesAreReportedAsResolutionErrors(): void
    {
        $container = new Container();
        $container->define(CircularA::class);
        $container->define(CircularB::class);

        $this->expectException(ServiceResolutionException::class);
        $container->get(CircularA::class);
    }

    #[DataProvider('defaultParameterProvider')]
    public function testOptionalParametersUseTheirDefaults(string $class, mixed $expected): void
    {
        $container = new Container();
        $container->define($class);

        $service = $container->get($class);

        $this->assertSame($expected, $service->value);
    }

    public static function defaultParameterProvider(): iterable
    {
        yield 'string' => [OptionalStringService::class, 'default'];
        yield 'integer' => [OptionalIntegerService::class, 7];
    }

    /**
     * @param Closure(Container): void $define
     */
    #[DataProvider('resolvedDefinitionProvider')]
    public function testResolvedServicesCannotBeRedefined(string $name, Closure $define): void
    {
        $container = new Container();
        $define($container);
        $original = $container->get($name);

        try {
            $container->define($name, new SimpleService());
            $this->fail('Redefining a resolved service should have been rejected.');
        } catch (ContainerException $exception) {
            $this->assertStringContainsString($name, $exception->getMessage());
        }

        $this->assertTrue($container->isResolved($name));
        $this->assertSame($original, $container->get($name));
    }

    /**
     * @return iterable<string, array{string, Closure(Container): void}>
     */
    public static function resolvedDefinitionProvider(): iterable
    {
        yield 'object' => ['service', static function (Container $container): void {
            $container->define('service', new SimpleService());
        }];
        yield 'class' => [SimpleService::class, static function (Container $container): void {
            $container->define(SimpleService::class);
        }];
        yield 'factory' => ['service', static function (Container $container): void {
            $container->define('service', static fn(): SimpleService => new SimpleService());
        }];
        yield 'loader' => ['service', static function (Container $container): void {
            $container->define(SharedDependency::class, new SharedDependency());
            $container->define('service')->loader(TestServiceLoader::class);
        }];
    }

    public function testRejectedRedefinitionKeepsTheExistingDefinitionAndAliases(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)->alias('alias');
        $service = $container->get('alias');

        try {
            $container->define(SimpleService::class)->alias('other');
            $this->fail('Redefining a resolved service should have been rejected.');
        } catch (ContainerException) {
        }

        $this->assertSame($service, $container->get(SimpleService::class));
        $this->assertSame($service, $container->get('alias'));
        $this->assertFalse($container->has('other'));
    }

    public function testRedefiningAnUnresolvedServiceKeepsItsAliasesPointingToTheNewDefinition(): void
    {
        $container = new Container();
        $container->define('service', new SimpleService())->alias('alias');

        $replacement = new SimpleService();
        $container->define('service', $replacement);

        $this->assertSame($replacement, $container->get('alias'));
        $this->assertSame($replacement, $container->get('service'));
    }

    public function testServicesResolvedThroughAnAliasCannotBeRedefined(): void
    {
        $container = new Container();
        $container->define('service', new SimpleService())->alias('alias');
        $container->get('alias');

        $this->expectException(ContainerException::class);
        $container->define('service', new SimpleService());
    }

    public function testDefiningAnAliasNameDoesNotRequireTheAliasedServiceToBeUnresolved(): void
    {
        $container = new Container();
        $container->define('target', new SimpleService());
        $container->alias('name', 'target');
        $target = $container->get('target');

        $own = new SimpleService();
        $container->define('name', $own);

        $this->assertSame($own, $container->get('name'));
        $this->assertSame($target, $container->get('target'));
    }
}
