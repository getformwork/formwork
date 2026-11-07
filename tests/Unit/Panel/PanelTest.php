<?php

namespace Formwork\Tests\Unit\Panel;

use Formwork\Assets\Assets;
use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Http\Request;
use Formwork\Panel\Events\PanelNavigationLoadedEvent;
use Formwork\Panel\Modals\Modals;
use Formwork\Panel\Navigation\NavigationItem;
use Formwork\Panel\Panel;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\Unit\Panel\Fixtures\PanelRequestFixture;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Translations\Translations;
use Formwork\Users\ColorScheme;
use Formwork\Users\User;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;
use ValueError;

#[CoversClass(Panel::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PanelTest extends TestCase
{
    private ?Request $request = null;

    protected function tearDown(): void
    {
        if ($this->request?->session()->isStarted()) {
            $this->request->session()->save();
        }

        parent::tearDown();
    }

    public function testCsrfTokenNameIsStable(): void
    {
        $this->assertSame('panel', $this->panel()->getCsrfTokenName());
    }

    #[DataProvider('panelRootProvider')]
    public function testPanelRootIsNormalizedWithSlashes(string $root, string $expected): void
    {
        $this->assertSame($expected, $this->panel(root: $root)->panelRoot());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function panelRootProvider(): iterable
    {
        yield 'with surrounding slashes' => ['/panel/', '/panel/'];
        yield 'without trailing slash' => ['/panel', '/panel/'];
        yield 'nested' => ['/admin/panel', '/admin/panel/'];
        yield 'with duplicated slashes' => ['/panel//', '/panel/'];
    }

    public function testPanelUriIsRelativeToTheRequestRoot(): void
    {
        $panel = $this->panel(server: ['SCRIPT_NAME' => '/sub/index.php']);

        $this->assertSame('/sub/panel/', $panel->panelUri());
    }

    public function testUrisAreBuiltFromRoutes(): void
    {
        $panel = $this->panel();

        $this->assertSame('/panel/', $panel->uri());
        $this->assertSame('/panel/pages/', $panel->uri('/pages/'));
        $this->assertSame('/panel/pages/', $panel->uri('pages/'));
    }

    public function testRouteIsRelativeToThePanelRoot(): void
    {
        $this->assertSame('/pages/new/', $this->panel(server: ['REQUEST_URI' => '/panel/pages/new/'])->route());
    }

    public function testRouteOfThePanelIndexIsTheRoot(): void
    {
        $this->assertSame('/', $this->panel(server: ['REQUEST_URI' => '/panel/'])->route());
    }

    public function testRouteOutsideThePanelRootIsRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->panel(server: ['REQUEST_URI' => '/other/pages/'])->route();
    }

    public function testRouteOfASiblingPathWithTheSamePrefixIsRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->panel(server: ['REQUEST_URI' => '/panelist/pages/'])->route();
    }

    public function testNobodyIsLoggedInWithoutAPreviousSession(): void
    {
        $this->assertFalse($this->panel(user: $this->createStub(User::class))->isLoggedIn());
    }

    public function testUserIsLoggedInWithAPreviousSessionAndAnAuthenticatedUser(): void
    {
        $this->assertTrue($this->panel(user: $this->createStub(User::class), previousSession: true)->isLoggedIn());
    }

    public function testUserIsNotLoggedInWithAPreviousSessionButNoAuthenticatedUser(): void
    {
        $this->assertFalse($this->panel(previousSession: true)->isLoggedIn());
    }

    public function testLoggedInUserIsReturned(): void
    {
        $user = $this->createStub(User::class);

        $this->assertSame($user, $this->panel(user: $user)->user());
    }

    public function testUserIsRequired(): void
    {
        $this->expectException(UserNotLoggedException::class);

        $this->panel()->user();
    }

    public function testNotificationsAreEmptyByDefault(): void
    {
        $this->assertSame([], $this->panel()->notifications());
    }

    public function testNotificationsAreReturnedWithTheirTypeAndRemoved(): void
    {
        $panel = $this->panel();

        $panel->notify('Saved', 'success');
        $panel->notify('Careful', 'warning');
        $panel->notify('Failed', 'error');

        $notifications = $panel->notifications();

        $this->assertCount(3, $notifications);
        $this->assertSame(['Saved', 'success'], [$notifications[0]['text'], $notifications[0]['type']]);
        $this->assertSame(['Careful', 'warning'], [$notifications[1]['text'], $notifications[1]['type']]);
        $this->assertSame(['Failed', 'error'], [$notifications[2]['text'], $notifications[2]['type']]);
        $this->assertSame([], $panel->notifications());
    }

    public function testNotificationsOfTheSameTypeAreKept(): void
    {
        $panel = $this->panel();

        $panel->notify('Image uploaded', 'success');
        $panel->notify('Profile saved', 'success');

        $this->assertSame(['Image uploaded', 'Profile saved'], array_column($panel->notifications(), 'text'));
    }

    public function testNotificationsDefaultToTheInfoType(): void
    {
        $panel = $this->panel();

        $panel->notify('Hello');

        $this->assertSame('info', $panel->notifications()[0]['type']);
    }

    public function testNotificationsHaveAnInterval(): void
    {
        $panel = $this->panel();

        $panel->notify('Hello');

        $this->assertGreaterThan(0, $panel->notifications()[0]['interval']);
    }

    public function testInvalidNotificationTypesAreRejected(): void
    {
        $this->expectException(ValueError::class);

        $this->panel()->notify('Hello', 'bogus');
    }

    public function testModalsAreProvided(): void
    {
        $modals = $this->createStub(Modals::class);

        $this->assertSame($modals, $this->panel(modals: $modals)->modals());
    }

    public function testAssetsAccessIsDeprecated(): void
    {
        $assets = $this->createStub(Assets::class);
        $panel = $this->panel(assets: $assets);

        $result = null;
        $messages = $this->captureDeprecations(function () use ($panel, &$result): void {
            $result = $panel->assets();
        });

        $this->assertSame($assets, $result);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('deprecated', $messages[0]);
    }

    #[DataProvider('colorSchemeProvider')]
    public function testColorSchemeFollowsTheConfiguration(string $configured, ColorScheme $expected, string $compatible): void
    {
        $panel = $this->panel(system: ['panel' => ['colorScheme' => $configured]]);

        $this->assertSame($expected, $panel->colorScheme());
        $this->assertSame($compatible, $panel->compatibleColorSchemes());
    }

    /**
     * @return iterable<string, array{string, ColorScheme, string}>
     */
    public static function colorSchemeProvider(): iterable
    {
        yield 'light' => ['light', ColorScheme::Light, 'light'];
        yield 'dark' => ['dark', ColorScheme::Dark, 'dark'];
    }

    public function testAutomaticColorSchemeUsesThePreferenceCookie(): void
    {
        $panel = $this->panel(system: ['panel' => ['colorScheme' => 'auto']], cookies: ['formwork_preferred_color_scheme' => 'dark']);

        $this->assertSame(ColorScheme::Dark, $panel->colorScheme());
        $this->assertSame('light dark', $panel->compatibleColorSchemes());
    }

    public function testAutomaticColorSchemeWithoutACookieStaysAutomatic(): void
    {
        $panel = $this->panel(system: ['panel' => ['colorScheme' => 'auto']]);

        $this->assertSame(ColorScheme::Auto, $panel->colorScheme());
    }

    #[DataProvider('invalidCookieProvider')]
    public function testInvalidColorSchemeCookiesAreIgnored(string $value): void
    {
        $panel = $this->panel(system: ['panel' => ['colorScheme' => 'auto']], cookies: ['formwork_preferred_color_scheme' => $value]);

        $this->assertSame(ColorScheme::Auto, $panel->colorScheme());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCookieProvider(): iterable
    {
        yield 'unknown scheme' => ['sepia'];
        yield 'empty' => [''];
        yield 'different case' => ['DARK'];
    }

    public function testUserColorSchemeTakesPrecedenceWhenLoggedIn(): void
    {
        $user = $this->createStub(User::class);
        $user->method('colorScheme')->willReturn(ColorScheme::Dark);

        $panel = $this->panel(system: ['panel' => ['colorScheme' => 'light']], user: $user, previousSession: true);

        $this->assertSame(ColorScheme::Dark, $panel->colorScheme());
        $this->assertSame('dark', $panel->compatibleColorSchemes());
    }

    public function testAvailableTranslationsAreListedFromThePanelTranslations(): void
    {
        $translations = $this->panel()->availableTranslations();

        $this->assertArrayHasKey('en', $translations);
        $this->assertSame('English (en)', $translations['en']);
        $this->assertSame(array_keys($translations), array_values(array_unique(array_keys($translations))));

        $sorted = $translations;
        ksort($sorted);

        $this->assertSame($sorted, $translations);
    }

    public function testNavigationIsBuiltFromTheConfigurationAndDispatchedToListeners(): void
    {
        $events = new EventDispatcher();
        $dispatched = null;
        $events->on('panelNavigationLoaded', function (PanelNavigationLoadedEvent $event) use (&$dispatched): void {
            $dispatched = $event->navigation();
        });

        $navigationFile = TESTS_TMP_PATH . '/navigation.php';
        FileSystem::write($navigationFile, "<?php\nreturn fn(\\Formwork\\Translations\\Translation \$translation) => ['home' => ['label' => 'Home', 'uri' => '/home/']];\n");

        $panel = $this->panel(system: ['panel' => ['config' => ['navigation' => $navigationFile]]], events: $events);

        $navigation = $panel->navigation();

        $this->assertSame($navigation, $dispatched);
        $this->assertInstanceOf(NavigationItem::class, $home = $navigation->get('home'));
        $this->assertSame('Home', $home->label());
        $this->assertSame($navigation, $panel->navigation());
    }

    /**
     * @param array<string, mixed>  $system
     * @param array<string, string> $server
     * @param array<string, string> $cookies
     */
    private function panel(
        string $root = '/panel/',
        array $system = [],
        array $server = [],
        array $cookies = [],
        ?User $user = null,
        ?Modals $modals = null,
        ?Assets $assets = null,
        ?EventDispatcher $events = null,
        bool $previousSession = false,
    ): Panel {
        $app = App::instance();

        $config = new Config(['system' => array_replace_recursive($app->config()->get('system'), ['panel' => ['root' => $root]], $system)], resolved: true);

        $sessionPath = TESTS_TMP_PATH . '/sessions';
        if (!FileSystem::isDirectory($sessionPath, assertExists: false)) {
            FileSystem::createDirectory($sessionPath, recursive: true);
        }

        $request = new PanelRequestFixture([], [], $cookies, [], [
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'SCRIPT_NAME'    => '/index.php',
            ...$server,
        ]);
        $request->session()->setPath($sessionPath);
        $request->previousSession = $previousSession;

        $this->request = $request;

        $translations = new Translations($config);
        $translations->loadFromPath(TestInstallation::systemPath() . '/translations');
        $translations->loadFromPath(TestInstallation::rootPath() . '/panel/translations');
        $translations->setCurrent('en');

        $users = $this->createStub(Users::class);
        $users->method('loggedIn')->willReturn($user);

        $container = new Container();
        $container->define(App::class, $app);
        $container->define(Site::class, $this->createStub(Site::class));

        return new Panel(
            $container,
            $config,
            $request,
            $users,
            $modals ?? $this->createStub(Modals::class),
            $translations,
            $assets ?? $this->createStub(Assets::class),
            $events ?? new EventDispatcher(),
        );
    }
}
