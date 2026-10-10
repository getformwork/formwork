<?php

namespace Formwork\Tests\Unit\Cms;

use BadMethodCallException;
use ErrorException;
use Formwork\Assets\Assets;
use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Cms\UriGenerator;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Panel\Panel;
use Formwork\Plugins\Plugins;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Services\Exceptions\ServiceNotFoundException;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translations;
use Formwork\Updater\SemVer;
use Formwork\Updater\Updater;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(App::class)]
final class AppTest extends TestCase
{
    public function testVersionIsAValidSemanticVersion(): void
    {
        $this->assertSame(App::VERSION, (string) SemVer::fromString(App::VERSION));
    }

    public function testInstanceIsASingleton(): void
    {
        $this->assertSame(App::instance(), App::instance());
    }

    public function testASecondApplicationCannotBeCreated(): void
    {
        $this->expectException(LogicException::class);

        new App();
    }

    public function testApplicationCannotBeCloned(): void
    {
        $this->expectException(LogicException::class);

        clone App::instance();
    }

    public function testLoadingTwiceDoesNothing(): void
    {
        $app = App::instance();
        $container = (new ReflectionProperty($app, 'container'))->getValue($app);
        $resolved = (new ReflectionProperty($container, 'resolved'))->getValue($container);

        $app->load();

        $this->assertSame(array_keys($resolved), array_keys((new ReflectionProperty($container, 'resolved'))->getValue($container)));
    }

    // Service accessors

    public function testTypedServiceAccessors(): void
    {
        $app = App::instance();

        $this->assertInstanceOf(Config::class, $app->config());
        $this->assertInstanceOf(Router::class, $app->router());
        $this->assertInstanceOf(UriGenerator::class, $app->uri());
        $this->assertInstanceOf(Site::class, $app->site());
        $this->assertInstanceOf(Request::class, $app->request());
        $this->assertInstanceOf(Schemes::class, $app->schemes());
        $this->assertInstanceOf(Translations::class, $app->translations());
        $this->assertInstanceOf(Assets::class, $app->assets());
        $this->assertInstanceOf(Panel::class, $app->panel());
        $this->assertInstanceOf(EventDispatcher::class, $app->events());
        $this->assertInstanceOf(Plugins::class, $app->plugins());
    }

    public function testAccessorsReturnTheSharedServices(): void
    {
        $app = App::instance();

        $this->assertSame($app->config(), $app->getService(Config::class));
        $this->assertSame($app->config(), $app->getService('config'));
        $this->assertSame($app->site(), $app->getService('site'));
        $this->assertSame($app->events(), $app->getService('events'));
    }

    public function testServicesAreAvailableThroughMagicCalls(): void
    {
        $app = App::instance();

        $this->assertSame($app->getService('templates'), $app->templates());
        $this->assertSame($app->getService('users'), $app->users());
        $this->assertSame($app->getService('csrfToken'), $app->csrfToken());
    }

    public function testUnknownMagicCallsAreRejected(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ' . App::class . '::nothingLikeThis()');

        App::instance()->nothingLikeThis();
    }

    public function testMagicCallsOnlyExposeDefinedServices(): void
    {
        $this->expectException(BadMethodCallException::class);

        App::instance()->container();
    }

    public function testServiceAvailability(): void
    {
        $app = App::instance();

        $this->assertTrue($app->hasService(Config::class));
        $this->assertTrue($app->hasService('config'));
        $this->assertFalse($app->hasService('nothing'));
        $this->assertFalse($app->hasService('Nothing\Like\This'));
    }

    public function testUnknownServicesAreRejected(): void
    {
        $this->expectException(ServiceNotFoundException::class);

        App::instance()->getService('nothing');
    }

    public function testTheApplicationAndTheContainerAreServices(): void
    {
        $app = App::instance();

        $this->assertSame($app, $app->getService(App::class));
        $this->assertInstanceOf(Container::class, $app->getService(Container::class));
    }

    /**
     * Updater and Backupper write inside the installation when they are created
     *
     * @return iterable<string, array{string}>
     */
    public static function services(): iterable
    {
        $definitions = [];
        $container = new class extends Container {
            /**
             * @return list<string>
             */
            public function names(): array
            {
                return array_keys($this->defined);
            }
        };
        (require SYSTEM_PATH . '/config/services/services.php')($container);

        foreach ($container->names() as $name) {
            if (in_array($name, [Updater::class, Backupper::class], true)) {
                continue;
            }
            $definitions[$name] = [$name];
        }

        yield from $definitions;
    }

    #[DataProvider('services')]
    public function testEveryDeclaredServiceCanBeResolved(string $name): void
    {
        $this->assertIsObject(App::instance()->getService($name));
    }

    // Error handler

    public function testWarningsAreTurnedIntoExceptions(): void
    {
        $handler = $this->errorHandler();

        try {
            $handler(E_WARNING, 'Something happened', '/path/file.php', 12);
            $this->fail('Warnings must be converted to exceptions.');
        } catch (ErrorException $exception) {
            $this->assertSame('Something happened', $exception->getMessage());
            $this->assertSame(E_WARNING, $exception->getSeverity());
            $this->assertSame('/path/file.php', $exception->getFile());
            $this->assertSame(12, $exception->getLine());
        }
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function severities(): iterable
    {
        yield 'notice' => [E_NOTICE];
        yield 'warning' => [E_WARNING];
        yield 'user warning' => [E_USER_WARNING];
        yield 'user error' => [E_USER_ERROR];
    }

    #[DataProvider('severities')]
    public function testEverySeverityExceptUserDeprecationsIsConverted(int $severity): void
    {
        $this->expectException(ErrorException::class);

        $this->errorHandler()($severity, 'message', 'file.php', 1);
    }

    public function testEngineDeprecationsAreConverted(): void
    {
        $handler = $this->errorHandler();
        $previous = error_reporting(E_ALL);

        try {
            $this->expectException(ErrorException::class);
            $handler(E_DEPRECATED, 'engine deprecation', 'file.php', 1);
        } finally {
            error_reporting($previous);
        }
    }

    public function testUserDeprecationsAreLeftToThePhpHandler(): void
    {
        $this->assertFalse($this->errorHandler()(E_USER_DEPRECATED, 'deprecated', 'file.php', 1));
    }

    public function testSilencedErrorsAreIgnored(): void
    {
        $handler = $this->errorHandler();
        $previous = error_reporting(0);

        try {
            $result = $handler(E_WARNING, 'silenced', 'file.php', 1);
        } finally {
            error_reporting($previous);
        }

        $this->assertFalse($result);
    }

    public function testErrorsOutsideTheErrorReportingLevelAreIgnored(): void
    {
        $handler = $this->errorHandler();
        $previous = error_reporting(E_ALL & ~E_NOTICE);

        try {
            $result = $handler(E_NOTICE, 'ignored', 'file.php', 1);
        } finally {
            error_reporting($previous);
        }

        $this->assertFalse($result);
    }

    public function testErrorsAreNotDisplayed(): void
    {
        $this->assertSame('0', ini_get('display_errors'));
    }

    /**
     * @return callable(int, string, string, int): bool
     */
    private function errorHandler(): callable
    {
        $handler = set_error_handler(static fn(): bool => false);
        restore_error_handler();

        $this->assertIsCallable($handler, 'The application must have installed its error handler');

        return $handler;
    }
}
