<?php

namespace Formwork\Tests\Unit\Services;

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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Throwable;

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
        $container->define('factory', static fn(): object => 'invalid');

        $this->expectException(\TypeError::class);
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

    public function testLazyFalseResolvesImmediatelyWhileDefaultDefinitionsRemainUnresolved(): void
    {
        $container = new Container();
        $container->define(SimpleService::class)->lazy(false);

        $this->assertTrue($container->isResolved(SimpleService::class));
        $this->assertInstanceOf(SimpleService::class, $container->get(SimpleService::class));
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

        $this->expectException(\TypeError::class);
        $container->get('loaded');
    }

    public function testObjectDefinitionsCannotAlsoHaveLoaders(): void
    {
        $definition = (new Container())->define('service', new SimpleService());

        $this->expectException(\LogicException::class);
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
        yield 'missing class' => ['Formwork\\Tests\\Unit\\Services\\Fixtures\\DoesNotExist'];
    }

    public function testFactoryReturningANonObjectDoesNotMarkTheServiceAsResolved(): void
    {
        $container = new Container();
        $container->define('factory', static fn() => 'not an object');

        try {
            $container->get('factory');
            $this->fail('The invalid factory result should have been rejected.');
        } catch (\TypeError) {
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

    public function testRedefiningAResolvedServiceReplacesTheResolvedInstance(): void
    {
        $container = new Container();

        $first = new SimpleService();
        $second = new SimpleService();

        $container->define('service', $first);

        $this->assertSame($first, $container->get('service'));

        $container->define('service', $second);

        $this->assertSame($second, $container->get('service'));
        $this->assertNotSame($first, $container->get('service'));
    }

    public function testRedefiningAResolvedDefinitionClearsItsPreviousResolution(): void
    {
        $container = new Container();

        $container->define(SimpleService::class);

        $first = $container->get(SimpleService::class);

        $container->define(SimpleService::class);

        $this->assertFalse($container->isResolved(SimpleService::class));

        $second = $container->get(SimpleService::class);

        $this->assertNotSame($first, $second);
    }
}
