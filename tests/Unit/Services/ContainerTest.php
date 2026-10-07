<?php

namespace Formwork\Tests\Unit\Services;

use Formwork\Services\Attributes\Service;
use Formwork\Services\Container;
use Formwork\Services\Exceptions\ContainerException;
use Formwork\Services\Exceptions\ServiceNotFoundException;
use Formwork\Services\Exceptions\ServiceResolutionException;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Services\ServiceLoaderInterface;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

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

interface DependencyContract {}

final class SimpleService {}

final class SharedDependency implements DependencyContract {}

final class DependentService
{
    public function __construct(public SharedDependency $dependency, public string $label) {}
}

final class DependentOnContract
{
    public function __construct(public DependencyContract $dependency) {}
}

final class AttributeDependentService
{
    public function __construct(#[Service('selected')] public SimpleService $service) {}
}

final class FactoryProduct
{
    public function __construct(public SharedDependency $dependency, public string $value) {}
}

final class VariadicService
{
    public array $values;

    public function __construct(string ...$values)
    {
        $this->values = $values;
    }
}

final class NestedDependentService
{
    public function __construct(public DependentOnSharedDependency $dependency) {}
}

final class DependentOnSharedDependency
{
    public function __construct(public SharedDependency $dependency) {}
}

final class AnotherDependentService
{
    public function __construct(public SharedDependency $dependency) {}
}

final class OptionalDependencyService
{
    public function __construct(public ?SharedDependency $dependency = null) {}
}

class LoadedService
{
    public function __construct(public string $name = 'loaded') {}
}

final class ContainerAwareLoader implements ServiceLoaderInterface
{
    public function __construct(private Container $container) {}

    public function load(Container $container): LoadedService
    {
        return new LoadedService($this->container === $container ? 'container-aware' : 'wrong');
    }
}

final class ParameterizedLoader implements ServiceLoaderInterface
{
    public function __construct(private string $label) {}

    public function load(Container $container): LoadedService
    {
        return new LoadedService($this->label);
    }
}

final class DependencyResolvingLoader implements ServiceLoaderInterface
{
    public function load(Container $container): LoadedService
    {
        return new LoadedServiceWithDependency($container->get(SharedDependency::class));
    }
}

final class RecursiveLoader implements ServiceLoaderInterface
{
    public function __construct(private string $other) {}

    public function load(Container $container): LoadedService
    {
        return $container->get($this->other);
    }
}

final class ThrowingLoader implements ServiceLoaderInterface
{
    public function load(Container $container): LoadedService
    {
        throw new RuntimeException('loader failed');
    }
}

final class InvalidReturnLoader implements ServiceLoaderInterface
{
    public function load(Container $container): object
    {
        return 'invalid';
    }
}

final class TestServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public static int $loadCount = 0;

    public static int $resolvedCount = 0;

    public function __construct(private SharedDependency $dependency) {}

    public function load(Container $container): LoadedService
    {
        self::$loadCount++;
        return new LoadedService($this->dependency instanceof SharedDependency ? 'loaded' : 'wrong');
    }

    public function onResolved(object $service, Container $container): void
    {
        self::$resolvedCount++;
    }
}

final class FailingResolutionAwareLoader implements ResolutionAwareServiceLoaderInterface
{
    public static int $loadCount = 0;

    public function load(Container $container): LoadedService
    {
        self::$loadCount++;
        return new LoadedService();
    }

    public function onResolved(object $service, Container $container): void
    {
        throw new RuntimeException('resolution callback failed');
    }
}

final class LoadedServiceWithDependency extends LoadedService
{
    public function __construct(public SharedDependency $dependency)
    {
        parent::__construct('dependency');
    }
}

final class DependentOnMissingService
{
    public function __construct(UnknownDependency $dependency) {}
}

final class ScalarDependentService
{
    public function __construct(string $value) {}
}

final class CircularA
{
    public function __construct(CircularB $dependency) {}
}

final class CircularB
{
    public function __construct(CircularA $dependency) {}
}

final class OptionalStringService
{
    public function __construct(public string $value = 'default') {}
}

final class OptionalIntegerService
{
    public function __construct(public int $value = 7) {}
}
