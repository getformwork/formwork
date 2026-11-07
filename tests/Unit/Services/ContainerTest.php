<?php

namespace Formwork\Tests\Unit\Services;

use Closure;
use Formwork\Services\Container;
use Formwork\Services\Exceptions\ContainerException;
use Formwork\Services\Exceptions\ServiceNotFoundException;
use Formwork\Services\Exceptions\ServiceResolutionException;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Services\Fixtures\AbstractServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\AnotherDependentServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\ArrayOptionsServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\AttributeDependentServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\CircularAFixture;
use Formwork\Tests\Unit\Services\Fixtures\CircularBFixture;
use Formwork\Tests\Unit\Services\Fixtures\ContainerAwareLoaderFixture;
use Formwork\Tests\Unit\Services\Fixtures\DependencyContractFixture;
use Formwork\Tests\Unit\Services\Fixtures\DependencyResolvingLoaderFixture;
use Formwork\Tests\Unit\Services\Fixtures\DependentOnContractFixture;
use Formwork\Tests\Unit\Services\Fixtures\DependentOnMissingServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\DependentOnSharedDependencyFixture;
use Formwork\Tests\Unit\Services\Fixtures\DependentServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\FactoryProductFixture;
use Formwork\Tests\Unit\Services\Fixtures\FailingResolutionAwareLoaderFixture;
use Formwork\Tests\Unit\Services\Fixtures\InvalidReturnLoaderFixture;
use Formwork\Tests\Unit\Services\Fixtures\LoadedServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\NestedDependentServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\OptionalAttributeDependentServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\OptionalDependencyServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\OptionalIntegerServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\OptionalStringServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\ParameterizedLoaderFixture;
use Formwork\Tests\Unit\Services\Fixtures\PrivateConstructorServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\RecursiveLoaderFixture;
use Formwork\Tests\Unit\Services\Fixtures\ScalarDependentServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\SharedDependencyFixture;
use Formwork\Tests\Unit\Services\Fixtures\SimpleServiceFixture;
use Formwork\Tests\Unit\Services\Fixtures\TestServiceLoaderFixture;
use Formwork\Tests\Unit\Services\Fixtures\ThrowingLoaderFixture;
use Formwork\Tests\Unit\Services\Fixtures\VariadicServiceFixture;
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
        parent::setUp();
        TestServiceLoaderFixture::$loadCount = 0;
        TestServiceLoaderFixture::$resolvedCount = 0;
        FailingResolutionAwareLoaderFixture::$loadCount = 0;
    }

    public function testDefineExposesAServiceDefinitionWithoutResolvingIt(): void
    {
        $container = new Container();
        $serviceDefinition = $container->define(SimpleServiceFixture::class);

        $this->assertSame(SimpleServiceFixture::class, $serviceDefinition->getName());
        $this->assertNull($serviceDefinition->getObject());
        $this->assertFalse($container->isResolved(SimpleServiceFixture::class));
        $this->assertTrue($container->has(SimpleServiceFixture::class));
    }

    public function testGetBuildsAndSharesAClassService(): void
    {
        $container = new Container();
        $container->define(SimpleServiceFixture::class);

        $simpleService = $container->get(SimpleServiceFixture::class);
        $second = $container->get(SimpleServiceFixture::class);

        $this->assertInstanceOf(SimpleServiceFixture::class, $simpleService);
        $this->assertSame($simpleService, $second);
        $this->assertTrue($container->isResolved(SimpleServiceFixture::class));
    }

    public function testDirectObjectsAreReturnedAndShared(): void
    {
        $container = new Container();
        $simpleService = new SimpleServiceFixture();
        $container->define('configured', $simpleService);

        $this->assertSame($simpleService, $container->get('configured'));
        $this->assertSame($simpleService, $container->get('configured'));
    }

    public function testBuildInjectsRegisteredDependenciesAndStaticParameters(): void
    {
        $container = new Container();
        $sharedDependency = new SharedDependencyFixture();
        $container->define(SharedDependencyFixture::class, $sharedDependency);
        $container->define(DependentServiceFixture::class)
            ->parameter('label', 'configured');

        $dependentService = $container->get(DependentServiceFixture::class);

        $this->assertSame($sharedDependency, $dependentService->dependency);
        $this->assertSame('configured', $dependentService->label);
    }

    public function testDependenciesCanBeResolvedThroughAnInterfaceAlias(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class)->alias(DependencyContractFixture::class);
        $container->define(DependentOnContractFixture::class);

        $dependentOnContract = $container->get(DependentOnContractFixture::class);

        $this->assertInstanceOf(SharedDependencyFixture::class, $dependentOnContract->dependency);
        $this->assertSame($container->get(DependencyContractFixture::class), $dependentOnContract->dependency);
    }

    public function testServiceAttributeSelectsAConfiguredIdentifier(): void
    {
        $container = new Container();
        $simpleService = new SimpleServiceFixture();
        $container->define('selected', $simpleService);
        $container->define(AttributeDependentServiceFixture::class);

        $attributeDependentService = $container->get(AttributeDependentServiceFixture::class);

        $this->assertSame($simpleService, $attributeDependentService->service);
    }

    public function testServiceAttributeFallsBackToTheDefaultWhenTheIdentifierIsNotDefined(): void
    {
        $container = new Container();
        $container->define(OptionalAttributeDependentServiceFixture::class);

        $this->assertNull($container->get(OptionalAttributeDependentServiceFixture::class)->service);
    }

    public function testServiceAttributeTakesPrecedenceOverTheDefaultWhenTheIdentifierIsDefined(): void
    {
        $container = new Container();
        $simpleService = new SimpleServiceFixture();
        $container->define('selected', $simpleService);
        $container->define(OptionalAttributeDependentServiceFixture::class);

        $this->assertSame($simpleService, $container->get(OptionalAttributeDependentServiceFixture::class)->service);
    }

    public function testServiceAttributeWithoutDefaultReportsTheMissingIdentifier(): void
    {
        $container = new Container();
        $container->define(AttributeDependentServiceFixture::class);

        $serviceNotFoundException = $this->captureException(ServiceNotFoundException::class, static fn() => $container->get(AttributeDependentServiceFixture::class));
        $this->assertStringContainsString('"selected"', $serviceNotFoundException->getMessage());
    }

    public function testClosuresCanBeUsedAsFactoriesAndAreShared(): void
    {
        $container = new Container();
        $sharedDependency = new SharedDependencyFixture();
        $container->define(SharedDependencyFixture::class, $sharedDependency);
        $container->define('factory', static function (SharedDependencyFixture $sharedDependency, string $value): FactoryProductFixture {
            return new FactoryProductFixture($sharedDependency, $value);
        })->parameter('value', 'factory-value');

        $first = $container->get('factory');
        $second = $container->get('factory');

        $this->assertInstanceOf(FactoryProductFixture::class, $first);
        $this->assertSame($sharedDependency, $first->dependency);
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
        $sharedDependency = new SharedDependencyFixture();
        $container->define(SharedDependencyFixture::class, $sharedDependency);

        $dependentService = $container->build(DependentServiceFixture::class, ['label' => 'built']);
        $called = $container->call(static fn(SharedDependencyFixture $sharedDependency, string $label): FactoryProductFixture => new FactoryProductFixture($sharedDependency, $label), ['label' => 'called']);

        $this->assertSame($sharedDependency, $dependentService->dependency);
        $this->assertSame('built', $dependentService->label);
        $this->assertSame($sharedDependency, $called->dependency);
        $this->assertSame('called', $called->value);
    }

    public function testBuildExplicitArgumentsOverrideRegisteredDependencies(): void
    {
        $container = new Container();
        $registered = new SharedDependencyFixture();
        $explicit = new SharedDependencyFixture();
        $container->define(SharedDependencyFixture::class, $registered);

        $dependentService = $container->build(DependentServiceFixture::class, [
            'dependency' => $explicit,
            'label'      => 'explicit',
        ]);

        $this->assertSame($explicit, $dependentService->dependency);
        $this->assertNotSame($registered, $dependentService->dependency);
    }

    public function testCallExplicitArgumentsOverrideRegisteredDependencies(): void
    {
        $container = new Container();
        $registered = new SharedDependencyFixture();
        $explicit = new SharedDependencyFixture();
        $container->define(SharedDependencyFixture::class, $registered);

        $result = $container->call(
            static fn(SharedDependencyFixture $sharedDependency): SharedDependencyFixture => $sharedDependency,
            ['sharedDependency' => $explicit],
        );

        $this->assertSame($explicit, $result);
    }

    public function testBuildAndCallSupportVariadicNamedArguments(): void
    {
        $container = new Container();

        $variadicService = $container->build(VariadicServiceFixture::class, ['values' => ['a', 'b']]);
        $called = $container->call(static fn(string ...$values): array => $values, ['values' => ['x', 'y']]);

        $this->assertSame(['a', 'b'], $variadicService->values);
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

        $variadicService = $container->build(VariadicServiceFixture::class, ['values' => ['first' => 'a', 'second' => 'b']]);

        $this->assertSame(['a', 'b'], $variadicService->values);
    }

    public function testVariadicParametersAreOptional(): void
    {
        $container = new Container();

        $this->assertSame([], $container->build(VariadicServiceFixture::class)->values);
        $this->assertSame([], $container->call(static fn(string ...$values): array => $values));
    }

    public function testParameterClosuresAreEvaluatedBeforeFactoryResolution(): void
    {
        $container = new Container();
        $sharedDependency = new SharedDependencyFixture();
        $container->define('factory', static fn(SharedDependencyFixture $sharedDependency): FactoryProductFixture => new FactoryProductFixture($sharedDependency, 'closure'))
            ->parameter('sharedDependency', static fn(): SharedDependencyFixture => $sharedDependency);

        $service = $container->get('factory');
        $this->assertInstanceOf(FactoryProductFixture::class, $service);

        $this->assertSame($sharedDependency, $service->dependency);
        $this->assertSame('closure', $service->value);
    }

    public function testParameterClosuresInsideNestedParametersAreEvaluated(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsServiceFixture::class)
            ->parameter('options.lazy', static fn(): string => 'evaluated')
            ->parameter('options.plain', 2);

        $this->assertSame(['lazy' => 'evaluated', 'plain' => 2], $container->get(ArrayOptionsServiceFixture::class)->options);
    }

    public function testDottedParameterNamesBuildNestedArrays(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsServiceFixture::class)
            ->parameter('options.a.b', 1)
            ->parameter('options.a.c', 2);

        $this->assertSame(['a' => ['b' => 1, 'c' => 2]], $container->get(ArrayOptionsServiceFixture::class)->options);
    }

    public function testResolutionStackIsClearedWhenAParameterClosureFails(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsServiceFixture::class)
            ->parameter('options', static function (): never {
                throw new RuntimeException('parameter failed');
            });

        foreach ([1, 2] as $attempt) {
            $exception = $this->captureException(RuntimeException::class, static fn() => $container->get(ArrayOptionsServiceFixture::class));
            $this->assertSame('parameter failed', $exception->getMessage(), "Attempt $attempt");
        }
    }

    public function testLazyFalseDoesNotResolveImmediately(): void
    {
        $container = new Container();
        $container->define(SimpleServiceFixture::class)->lazy(false);

        $this->assertFalse($container->isResolved(SimpleServiceFixture::class));
        $this->assertInstanceOf(SimpleServiceFixture::class, $container->get(SimpleServiceFixture::class));
    }

    public function testEagerServicesAreResolvedExplicitly(): void
    {
        $container = new Container();
        $container->define(SimpleServiceFixture::class)->lazy(false);
        $container->define(SharedDependencyFixture::class);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved(SimpleServiceFixture::class));
        $this->assertFalse($container->isResolved(SharedDependencyFixture::class));
    }

    public function testResolvingEagerServicesOnAnEmptyContainerDoesNothing(): void
    {
        $container = new Container();

        $container->resolveEagerServices();

        $this->assertFalse($container->has(SimpleServiceFixture::class));
    }

    public function testResolvingEagerServicesTwiceKeepsTheSharedInstances(): void
    {
        $container = new Container();
        $container->define(SimpleServiceFixture::class)->lazy(false);

        $container->resolveEagerServices();
        $simpleService = $container->get(SimpleServiceFixture::class);
        $container->resolveEagerServices();

        $this->assertSame($simpleService, $container->get(SimpleServiceFixture::class));
    }

    public function testEagerServicesAlreadyResolvedAreNotResolvedAgain(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class, new SharedDependencyFixture());
        $container->define('loaded')->loader(TestServiceLoaderFixture::class)->lazy(false);
        $container->get('loaded');

        $container->resolveEagerServices();

        $this->assertSame(1, TestServiceLoaderFixture::$loadCount);
        $this->assertSame(1, TestServiceLoaderFixture::$resolvedCount);
    }

    public function testEagerServicesUseTheConfigurationDefinedAfterLazyFalse(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsServiceFixture::class)
            ->lazy(false)
            ->parameter('options', ['configured' => true]);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved(ArrayOptionsServiceFixture::class));
        $this->assertSame(['configured' => true], $container->get(ArrayOptionsServiceFixture::class)->options);
    }

    public function testEagerLoaderServicesAreLoadedOnce(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class, new SharedDependencyFixture());
        $container->define('loaded')->loader(TestServiceLoaderFixture::class)->lazy(false);

        $container->resolveEagerServices();
        $container->resolveEagerServices();

        $this->assertInstanceOf(LoadedServiceFixture::class, $container->get('loaded'));
        $this->assertSame(1, TestServiceLoaderFixture::$loadCount);
        $this->assertSame(1, TestServiceLoaderFixture::$resolvedCount);
    }

    public function testEagerServicesResolveTheirLazyDependencies(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class);
        $container->define(DependentOnSharedDependencyFixture::class)->lazy(false);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved(SharedDependencyFixture::class));
    }

    public function testEagerServicesCanBeReachedThroughTheirAliases(): void
    {
        $container = new Container();
        $container->define(SimpleServiceFixture::class)->alias('simple')->lazy(false);

        $container->resolveEagerServices();

        $this->assertTrue($container->isResolved('simple'));
    }

    public function testEagerServicesDefinedWhileResolvingEagerServicesAreResolvedToo(): void
    {
        $container = new Container();
        $container->define('first', static function () use ($container): SimpleServiceFixture {
            $container->define('second', static fn(): SharedDependencyFixture => new SharedDependencyFixture())->lazy(false);
            return new SimpleServiceFixture();
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

        $this->captureException(RuntimeException::class, static fn() => $container->resolveEagerServices());

        $this->assertFalse($container->isResolved('failing'));
    }

    public function testParametersSetAfterLazyFalseAreApplied(): void
    {
        $container = new Container();
        $container->define(ArrayOptionsServiceFixture::class)
            ->lazy(false)
            ->parameter('options', ['configured' => true]);

        $this->assertSame(['configured' => true], $container->get(ArrayOptionsServiceFixture::class)->options);
    }

    public function testLoaderSetAfterLazyFalseIsApplied(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class, new SharedDependencyFixture());
        $container->define('loaded')
            ->lazy(false)
            ->loader(TestServiceLoaderFixture::class);

        $this->assertInstanceOf(LoadedServiceFixture::class, $container->get('loaded'));
        $this->assertSame(1, TestServiceLoaderFixture::$loadCount);
    }

    public function testLazyFalseServicesAreResolvedOnlyOnce(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class, new SharedDependencyFixture());
        $container->define('loaded')
            ->loader(TestServiceLoaderFixture::class)
            ->lazy(false);

        $first = $container->get('loaded');

        $this->assertSame($first, $container->get('loaded'));
        $this->assertSame(1, TestServiceLoaderFixture::$loadCount);
        $this->assertSame(1, TestServiceLoaderFixture::$resolvedCount);
    }

    public function testAliasesResolveToTheTargetAndPreserveItsIdentity(): void
    {
        $container = new Container();
        $container->define(SimpleServiceFixture::class)
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

        $this->captureException(ContainerException::class, static fn() => $container->alias('c', 'a'));

        $simpleService = new SimpleServiceFixture();
        $container->define('c', $simpleService);

        $this->assertSame($simpleService, $container->get('a'));
        $this->assertFalse($container->has('d'));
    }

    public function testAliasToAnUndefinedServiceIsNotAvailableUntilTheTargetIsDefined(): void
    {
        $container = new Container();
        $container->alias('alias', 'target');

        $this->assertFalse($container->has('alias'));
        $this->assertFalse($container->isResolved('alias'));

        $serviceNotFoundException = $this->captureException(ServiceNotFoundException::class, static fn() => $container->get('alias'));
        $this->assertStringContainsString('"target"', $serviceNotFoundException->getMessage());

        $simpleService = new SimpleServiceFixture();
        $container->define('target', $simpleService);

        $this->assertTrue($container->has('alias'));
        $this->assertSame($simpleService, $container->get('alias'));
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
        $simpleService = new SimpleServiceFixture();
        $container->define('name', $simpleService);
        $container->define('target', new SimpleServiceFixture());

        $containerException = $this->captureException(ContainerException::class, static fn() => $container->alias('name', 'target'));
        $this->assertStringContainsString('name', $containerException->getMessage());

        $this->assertSame($simpleService, $container->get('name'));
    }

    public function testDefinitionAliasCannotShadowADefinedService(): void
    {
        $container = new Container();
        $simpleService = new SimpleServiceFixture();
        $container->define('name', $simpleService);

        $this->captureException(ContainerException::class, static fn() => $container->define('target', new SimpleServiceFixture())->alias('name'));

        $this->assertSame($simpleService, $container->get('name'));
    }

    public function testInterfaceAliasCanBeDefinedBeforeItsTargetAndRepointedLater(): void
    {
        $container = new Container();
        $container->alias(DependencyContractFixture::class, 'first');
        $container->define('first', new SimpleServiceFixture());
        $container->define('second', new SimpleServiceFixture());

        $this->assertSame($container->get('first'), $container->get(DependencyContractFixture::class));

        $container->alias(DependencyContractFixture::class, 'second');

        $this->assertSame($container->get('second'), $container->get(DependencyContractFixture::class));
    }

    public function testDefiningAServiceWithTheNameOfAnAliasMakesTheNewDefinitionReachable(): void
    {
        $container = new Container();
        $container->define('target', new SimpleServiceFixture());
        $container->alias('name', 'target');

        $simpleService = new SimpleServiceFixture();
        $container->define('name', $simpleService);

        $this->assertSame($simpleService, $container->get('name'));
    }

    public function testLoaderIsConstructedWithDependenciesAndCalledOnlyOnce(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class, new SharedDependencyFixture());
        $container->define('loaded')->loader(TestServiceLoaderFixture::class);
        $container->define(TestServiceLoaderFixture::class);

        $first = $container->get('loaded');
        $second = $container->get('loaded');

        $this->assertInstanceOf(LoadedServiceFixture::class, $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, TestServiceLoaderFixture::$loadCount);
        $this->assertSame(1, TestServiceLoaderFixture::$resolvedCount);
    }

    public function testResolvingAnAlreadyResolvedServiceKeepsTheSharedInstance(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(TestServiceLoaderFixture::class);
        $container->define(SharedDependencyFixture::class, new SharedDependencyFixture());

        $first = $container->get('loaded');
        $second = $container->resolve('loaded');

        $this->assertSame($first, $second);
        $this->assertSame($first, $container->get('loaded'));
        $this->assertSame(1, TestServiceLoaderFixture::$loadCount);
        $this->assertSame(1, TestServiceLoaderFixture::$resolvedCount);
    }

    public function testAliasesResolveLoaderBackedServicesToTheSharedInstance(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class, new SharedDependencyFixture());
        $container->define('loaded')->loader(TestServiceLoaderFixture::class)->alias('loaded.alias');
        $container->define(TestServiceLoaderFixture::class);

        $this->assertSame($container->get('loaded'), $container->get('loaded.alias'));
        $this->assertSame(1, TestServiceLoaderFixture::$loadCount);
    }

    public function testLoaderCanReturnAServiceThatDependsOnTheContainer(): void
    {
        $container = new Container();
        $container->define(Container::class, $container);
        $container->define('loaded')->loader(ContainerAwareLoaderFixture::class);
        $container->define(ContainerAwareLoaderFixture::class);

        $service = $container->get('loaded');
        $this->assertInstanceOf(LoadedServiceFixture::class, $service);

        $this->assertSame('container-aware', $service->name);
    }

    public function testLoaderReceivesExplicitConstructorParameters(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(ParameterizedLoaderFixture::class)->parameter('label', 'configured');

        $service = $container->get('loaded');
        $this->assertInstanceOf(LoadedServiceFixture::class, $service);

        $this->assertSame('configured', $service->name);
    }

    public function testLoaderCanResolveAnotherRegisteredService(): void
    {
        $container = new Container();
        $sharedDependency = new SharedDependencyFixture();
        $container->define(SharedDependencyFixture::class, $sharedDependency);
        $container->define('loaded')->loader(DependencyResolvingLoaderFixture::class);
        $container->define(DependencyResolvingLoaderFixture::class);

        $service = $container->get('loaded');

        $this->assertSame($sharedDependency, get_object_vars($service)['dependency']);
    }

    public function testRecursiveLoaderResolutionIsRejected(): void
    {
        $container = new Container();
        $container->define('first')->loader(RecursiveLoaderFixture::class)->parameter('other', 'second');
        $container->define('second')->loader(RecursiveLoaderFixture::class)->parameter('other', 'first');

        $this->expectException(ServiceResolutionException::class);
        $container->get('first');
    }

    public function testLoaderExceptionsArePropagatedAndTheServiceRemainsUnresolved(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(ThrowingLoaderFixture::class);

        $runtimeException = $this->captureException(RuntimeException::class, static fn() => $container->get('loaded'));
        $this->assertSame('loader failed', $runtimeException->getMessage());

        $this->assertTrue($container->has('loaded'));
        $this->assertFalse($container->isResolved('loaded'));
    }

    public function testLoaderReturningAnInvalidValueFailsAtTheLoaderBoundary(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(InvalidReturnLoaderFixture::class);

        $this->expectException(TypeError::class);
        $container->get('loaded');
    }

    public function testObjectDefinitionsCannotAlsoHaveLoaders(): void
    {
        $serviceDefinition = (new Container())->define('service', new SimpleServiceFixture());

        $this->expectException(LogicException::class);
        $serviceDefinition->loader(TestServiceLoaderFixture::class);
    }

    public function testInvalidLoaderIsRejectedDuringResolution(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(SimpleServiceFixture::class);

        $this->expectException(ServiceResolutionException::class);
        $this->expectExceptionMessage('Invalid loader');
        $container->get('loaded');
    }

    public function testResolutionAwareLoaderFailureDoesNotLeaveAResolvedService(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(FailingResolutionAwareLoaderFixture::class);
        $container->define(FailingResolutionAwareLoaderFixture::class);

        $this->expectException(RuntimeException::class);
        $container->get('loaded');
    }

    public function testResolutionAwareLoaderFailureCanBeRetried(): void
    {
        $container = new Container();
        $container->define('loaded')->loader(FailingResolutionAwareLoaderFixture::class);
        $container->define(FailingResolutionAwareLoaderFixture::class);

        $this->captureException(RuntimeException::class, static fn() => $container->get('loaded'));

        $this->assertFalse($container->isResolved('loaded'));
        $this->assertSame(1, FailingResolutionAwareLoaderFixture::$loadCount);
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
        yield 'interface' => [DependencyContractFixture::class];
        yield 'abstract class' => [AbstractServiceFixture::class];
        yield 'private constructor' => [PrivateConstructorServiceFixture::class];
        yield 'identifier that is not a class' => ['config'];
        yield 'missing class' => ['Formwork\Tests\Unit\Services\Fixtures\DoesNotExist'];
    }

    public function testFactoryReturningANonObjectDoesNotMarkTheServiceAsResolved(): void
    {
        $container = new Container();
        $container->define('factory', static fn() => 'not an object');

        $this->captureException(ContainerExceptionInterface::class, static fn() => $container->get('factory'));

        $this->assertFalse($container->isResolved('factory'));
    }

    public function testMissingClassDependenciesAreReportedAsNotFound(): void
    {
        $container = new Container();
        $container->define(DependentOnMissingServiceFixture::class);

        $this->expectException(ServiceNotFoundException::class);
        $container->get(DependentOnMissingServiceFixture::class);
    }

    public function testNestedDependenciesAndSharedDependenciesPreserveIdentity(): void
    {
        $container = new Container();
        $container->define(SharedDependencyFixture::class);
        $container->define(DependentOnSharedDependencyFixture::class);
        $container->define(NestedDependentServiceFixture::class);
        $container->define(AnotherDependentServiceFixture::class);

        $nestedDependentService = $container->get(NestedDependentServiceFixture::class);
        $anotherDependentService = $container->get(AnotherDependentServiceFixture::class);

        $this->assertSame($nestedDependentService->dependency->dependency, $anotherDependentService->dependency);
    }

    public function testOptionalMissingDependenciesUseTheirDefault(): void
    {
        $container = new Container();
        $container->define(OptionalDependencyServiceFixture::class);

        $this->assertNull($container->get(OptionalDependencyServiceFixture::class)->dependency);
    }

    public function testFailedResolutionDoesNotMakeAServiceResolved(): void
    {
        $container = new Container();
        $container->define(DependentOnMissingServiceFixture::class);

        $this->captureException(ServiceNotFoundException::class, static fn() => $container->get(DependentOnMissingServiceFixture::class));

        $this->assertTrue($container->has(DependentOnMissingServiceFixture::class));
        $this->assertFalse($container->isResolved(DependentOnMissingServiceFixture::class));
    }

    public function testDefiningTheSameIdentifierReplacesAnUnresolvedDefinition(): void
    {
        $container = new Container();
        $first = new SimpleServiceFixture();
        $second = new SimpleServiceFixture();
        $container->define('service', $first);
        $container->define('service', $second);

        $this->assertSame($second, $container->get('service'));
    }

    public function testAliasCanBeRepointedAndCanTargetAnotherAlias(): void
    {
        $container = new Container();
        $container->define('first', new SimpleServiceFixture());
        $container->define('second', new SimpleServiceFixture());
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
        $container->define(ScalarDependentServiceFixture::class);

        $this->expectException(ContainerException::class);
        $container->get(ScalarDependentServiceFixture::class);
    }

    public function testCircularDependenciesAreReportedAsResolutionErrors(): void
    {
        $container = new Container();
        $container->define(CircularAFixture::class);
        $container->define(CircularBFixture::class);

        $this->expectException(ServiceResolutionException::class);
        $container->get(CircularAFixture::class);
    }

    #[DataProvider('defaultParameterProvider')]
    public function testOptionalParametersUseTheirDefaults(string $class, mixed $expected): void
    {
        $container = new Container();
        $container->define($class);

        $service = $container->get($class);

        $this->assertSame($expected, get_object_vars($service)['value']);
    }

    /**
     * @return iterable<string, array{class-string, mixed}>
     */
    public static function defaultParameterProvider(): iterable
    {
        yield 'string' => [OptionalStringServiceFixture::class, 'default'];
        yield 'integer' => [OptionalIntegerServiceFixture::class, 7];
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

        $containerException = $this->captureException(ContainerException::class, static fn() => $container->define($name, new SimpleServiceFixture()));
        $this->assertStringContainsString($name, $containerException->getMessage());

        $this->assertTrue($container->isResolved($name));
        $this->assertSame($original, $container->get($name));
    }

    /**
     * @return iterable<string, array{string, Closure(Container): void}>
     */
    public static function resolvedDefinitionProvider(): iterable
    {
        yield 'object' => ['service', static function (Container $container): void {
            $container->define('service', new SimpleServiceFixture());
        }];
        yield 'class' => [SimpleServiceFixture::class, static function (Container $container): void {
            $container->define(SimpleServiceFixture::class);
        }];
        yield 'factory' => ['service', static function (Container $container): void {
            $container->define('service', static fn(): SimpleServiceFixture => new SimpleServiceFixture());
        }];
        yield 'loader' => ['service', static function (Container $container): void {
            $container->define(SharedDependencyFixture::class, new SharedDependencyFixture());
            $container->define('service')->loader(TestServiceLoaderFixture::class);
        }];
    }

    public function testRejectedRedefinitionKeepsTheExistingDefinitionAndAliases(): void
    {
        $container = new Container();
        $container->define(SimpleServiceFixture::class)->alias('alias');
        $service = $container->get('alias');

        $this->captureException(ContainerException::class, static fn() => $container->define(SimpleServiceFixture::class)->alias('other'));

        $this->assertSame($service, $container->get(SimpleServiceFixture::class));
        $this->assertSame($service, $container->get('alias'));
        $this->assertFalse($container->has('other'));
    }

    public function testRedefiningAnUnresolvedServiceKeepsItsAliasesPointingToTheNewDefinition(): void
    {
        $container = new Container();
        $container->define('service', new SimpleServiceFixture())->alias('alias');

        $simpleService = new SimpleServiceFixture();
        $container->define('service', $simpleService);

        $this->assertSame($simpleService, $container->get('alias'));
        $this->assertSame($simpleService, $container->get('service'));
    }

    public function testServicesResolvedThroughAnAliasCannotBeRedefined(): void
    {
        $container = new Container();
        $container->define('service', new SimpleServiceFixture())->alias('alias');
        $container->get('alias');

        $this->expectException(ContainerException::class);
        $container->define('service', new SimpleServiceFixture());
    }

    public function testDefiningAnAliasNameDoesNotRequireTheAliasedServiceToBeUnresolved(): void
    {
        $container = new Container();
        $container->define('target', new SimpleServiceFixture());
        $container->alias('name', 'target');
        $target = $container->get('target');

        $simpleService = new SimpleServiceFixture();
        $container->define('name', $simpleService);

        $this->assertSame($simpleService, $container->get('name'));
        $this->assertSame($target, $container->get('target'));
    }
}
