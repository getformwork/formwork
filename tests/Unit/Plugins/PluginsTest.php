<?php

namespace Formwork\Tests\Unit\Plugins;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Events\Event;
use Formwork\Events\EventDispatcher;
use Formwork\Events\ListenerProvider;
use Formwork\Plugins\Events\PluginsInitializedEvent;
use Formwork\Plugins\Exceptions\PluginInitializationException;
use Formwork\Plugins\Plugin;
use Formwork\Plugins\PluginFactory;
use Formwork\Plugins\Plugins;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Plugins\Fixtures\BuildsPlugins;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(Plugins::class)]
final class PluginsTest extends TestCase
{
    use BuildsPlugins;

    private Config $config;

    private EventDispatcher $dispatcher;

    private Plugins $plugins;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPlugins();

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(App::class, App::instance());

        $this->config = new Config(['plugins' => []], resolved: true);
        $this->dispatcher = new EventDispatcher(new ListenerProvider());
        $this->plugins = new Plugins($this->config, new PluginFactory($container), $this->dispatcher);
    }

    protected function tearDown(): void
    {
        $this->tearDownPlugins();
        parent::tearDown();
    }

    public function testPluginsAreLoadedByName(): void
    {
        $this->plugins->load('loadOne', $this->makePluginDirectory('load-one'));

        $this->assertInstanceOf(Plugin::class, $this->plugins->get('loadOne'));
        $this->assertSame(['loadOne'], $this->plugins->keys());
    }

    public function testLoadingFromAPathUsesCamelCasedDirectoryNames(): void
    {
        $this->makePluginDirectory('path-alpha');
        $this->makePluginDirectory('path-beta');

        $this->plugins->loadFromPath($this->pluginsPath);

        $this->assertEqualsCanonicalizing(['pathAlpha', 'pathBeta'], $this->plugins->keys());
    }

    public function testLoadingFromAnEmptyPathLoadsNothing(): void
    {
        $this->plugins->loadFromPath($this->pluginsPath);

        $this->assertCount(0, $this->plugins);
    }

    public function testInvalidPluginsAreReportedWhenLoading(): void
    {
        $this->expectException(RuntimeException::class);

        $this->plugins->load('missing', $this->pluginsPath . '/missing');
    }

    public function testInitializingAnUnknownPluginIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid plugin "ghost"');

        $this->plugins->initialize('ghost');
    }

    public function testInitializationRunsThePluginAndRegistersItsListeners(): void
    {
        $this->plugins->load('listenA', $this->makePluginDirectory('listen-a', <<<'PHP'
                public static array $received = [];

                public function onCustomEvent(\Formwork\Events\Event $event): void
                {
                    self::$received[] = $event->name();
                }
            PHP));

        $this->plugins->initialize('listenA');
        $this->dispatcher->dispatch(new Event('customEvent', []));

        $plugin = $this->plugins->get('listenA');
        $this->assertTrue($plugin->isInitialized());
        $this->assertSame(['customEvent'], $plugin::$received);
    }

    public function testInitializingAPluginTwiceDoesNotRegisterItsListenersTwice(): void
    {
        $this->plugins->load('listenB', $this->makePluginDirectory('listen-b', <<<'PHP'
                public static int $calls = 0;

                public function onCustomEvent(\Formwork\Events\Event $event): void
                {
                    ++self::$calls;
                }
            PHP));

        $this->plugins->initialize('listenB');
        $this->plugins->initialize('listenB');
        $this->dispatcher->dispatch(new Event('customEvent', []));

        $this->assertSame(1, $this->plugins->get('listenB')::$calls);
    }

    public function testPluginInitializationErrorsPropagate(): void
    {
        $this->plugins->load('brokenInit', $this->makePluginDirectory('broken-init', '', ['plugin.yaml' => "unknown: true\n"]));

        $this->expectException(PluginInitializationException::class);

        $this->plugins->initialize('brokenInit');
    }

    public function testAutoloadFailuresAreWrapped(): void
    {
        $this->plugins->load('badAutoload', $this->makePluginDirectory('bad-autoload', <<<'PHP'
                public function autoload(): ?\Composer\Autoload\ClassLoader
                {
                    throw new \RuntimeException('cannot autoload');
                }
            PHP));

        try {
            $this->plugins->initialize('badAutoload');
            $this->fail('Autoload failure should be reported.');
        } catch (PluginInitializationException $exception) {
            $this->assertStringContainsString('Failed autoload for plugin "badAutoload"', $exception->getMessage());
            $this->assertSame('cannot autoload', $exception->getPrevious()?->getMessage());
        }
    }

    public function testOnlyEnabledPluginsAreInitialized(): void
    {
        $this->plugins->load('enabledOne', $this->makePluginDirectory('enabledone'));
        $this->plugins->load('disabledOne', $this->makePluginDirectory('disabledone'));
        $this->plugins->load('implicitOne', $this->makePluginDirectory('implicitone'));
        $this->config->set('plugins.enabledOne.enabled', true);
        $this->config->set('plugins.disabledOne.enabled', false);

        $this->plugins->initializeEnabled();

        $this->assertTrue($this->plugins->get('enabledOne')->isInitialized());
        $this->assertFalse($this->plugins->get('disabledOne')->isInitialized());
        $this->assertFalse($this->plugins->get('implicitOne')->isInitialized());
    }

    public function testInitializedEventIsAlwaysDispatched(): void
    {
        $received = null;
        $this->dispatcher->on('pluginsInitialized', function (PluginsInitializedEvent $event) use (&$received): void {
            $received = $event->plugins();
        });

        $this->plugins->initializeEnabled();

        $this->assertSame($this->plugins, $received);
    }
}
