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
use Formwork\Fields\Dynamic\DynamicFieldValue;
use Formwork\Http\Request;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Panel;
use Formwork\Plugins\Plugins;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Services\Exceptions\ServiceNotFoundException;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\UsesApplication;
use Formwork\Translations\Translations;
use Formwork\Updater\SemVer;
use Formwork\Updater\Updater;
use LogicException;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(App::class)]
#[UsesApplication]
final class AppTest extends TestCase
{
    /**
     * @var (callable(int, string, string, int): bool)|null
     */
    private static $errorHandler;

    private int $errorReporting;

    protected function setUp(): void
    {
        // PHPUnit narrows the error reporting level while its own handler is active
        $this->errorReporting = error_reporting(E_ALL);
    }

    protected function tearDown(): void
    {
        error_reporting($this->errorReporting);
    }

    public function testVersionIsAValidSemanticVersion(): void
    {
        $this->assertSame(App::VERSION, (string) SemVer::fromString(App::VERSION));
    }

    public function testInstanceIsShared(): void
    {
        $this->assertSame(App::instance(), App::instance());
    }

    public function testOnlyOneApplicationCanBeCreated(): void
    {
        $this->expectException(LogicException::class);

        new App();
    }

    public function testRootPathIsTheOneOfTheTestInstallation(): void
    {
        $rootPath = App::instance()->rootPath();

        $this->assertStringStartsWith(sys_get_temp_dir() . '/formwork-tests-', $rootPath);
        $this->assertDirectoryExists($rootPath . '/site');
        $this->assertSame($rootPath . '/site/pages', App::instance()->config()->getString('system.pages.path'));
    }

    public function testSystemPathIsTheDirectoryOfFormwork(): void
    {
        $this->assertSame(dirname(__DIR__, 3) . '/formwork', App::instance()->systemPath());
        $this->assertFileExists(App::instance()->systemPath() . '/config/services/services.php');
    }

    public function testLoadingTwiceDoesNothing(): void
    {
        $app = App::instance();
        $container = (new ReflectionProperty($app, 'container'))->getValue($app);
        $resolved = (new ReflectionProperty($container, 'resolved'))->getValue($container);

        $app->load();

        $this->assertSame(array_keys($resolved), array_keys((new ReflectionProperty($container, 'resolved'))->getValue($container)));
    }

    public function testUnloadingStartsTheNextLoadFromScratch(): void
    {
        $app = App::instance();
        $site = $app->site();

        try {
            $app->unload();
            $app->load();

            $this->assertNotSame($site, $app->site());
            $this->assertSame($app, $app->site()->get('app'));
        } finally {
            $this->reload();
        }
    }

    public function testDynamicFieldVariablesBelongToTheLastLoad(): void
    {
        $app = App::instance();

        try {
            $app->unload();
            $app->load();
            $app->schemes();

            $this->assertSame($app->site(), (DynamicFieldValue::$varsLoader)()['site']);
        } finally {
            $this->reload();
        }
    }

    #[BackupGlobals(true)]
    public function testRequestsAreBuiltFromTheGlobalsOfEachLoad(): void
    {
        $app = App::instance();

        try {
            $this->setRequestGlobals('/about/');
            $app->unload();
            $app->load();

            $response = $app->router()->dispatch();

            $this->assertSame(ResponseStatus::OK, $response->status());
            $this->assertStringContainsString('About content', $response->content());
        } finally {
            $this->reload();
        }
    }

    public function testLoadingDoesNotInstallTheErrorHandler(): void
    {
        $before = set_error_handler(static fn(): bool => false);
        restore_error_handler();

        try {
            App::instance()->unload();
            App::instance()->load();
        } finally {
            $this->reload();
        }

        $after = set_error_handler(static fn(): bool => false);
        restore_error_handler();

        $this->assertSame($before, $after);
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
        (require TestInstallation::systemPath() . '/config/services/services.php')($container);

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
        $display = ini_set('display_errors', '1');

        try {
            $this->loadApplicationWithErrorHandler();

            $this->assertSame('0', ini_get('display_errors'));
        } finally {
            ini_set('display_errors', (string) $display);
        }
    }

    /**
     * @return callable(int, string, string, int): bool
     */
    private function errorHandler(): callable
    {
        self::$errorHandler ??= $this->loadApplicationWithErrorHandler();

        return self::$errorHandler;
    }

    /**
     * Install the error handler of the application, leaving the handlers as they were found
     *
     * @return callable(int, string, string, int): bool
     */
    private function loadApplicationWithErrorHandler(): callable
    {
        $before = set_error_handler(static fn(): bool => false);
        restore_error_handler();

        // The error handler is installed when the application runs
        (new ReflectionMethod(App::class, 'loadErrorHandler'))->invoke(App::instance());

        $handler = set_error_handler(static fn(): bool => false);
        restore_error_handler();

        restore_error_handler();

        $this->assertNotSame($before, $handler, 'The application must have installed its error handler');
        $this->assertIsCallable($handler);

        return $handler;
    }

    /**
     * Load again the application of the tests from the current globals
     */
    private function reload(): void
    {
        App::instance()->unload();
        App::instance()->load();
    }

    private function setRequestGlobals(string $uri): void
    {
        $_SERVER = [
            ...$_SERVER,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => $uri,
            'SCRIPT_NAME'    => '/index.php',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'HTTP_HOST'      => 'localhost',
        ];
        $_GET = $_POST = $_COOKIE = $_FILES = [];
    }
}
