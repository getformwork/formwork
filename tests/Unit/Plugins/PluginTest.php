<?php

namespace Formwork\Tests\Unit\Plugins;

use Formwork\Cms\App;
use Formwork\Plugins\Exceptions\PluginInitializationException;
use Formwork\Plugins\Plugin;
use Formwork\Services\Container;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Plugins\Fixtures\BuildsPlugins;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Plugin::class)]
final class PluginTest extends TestCase
{
    use BuildsPlugins;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPlugins();
    }

    protected function tearDown(): void
    {
        $this->tearDownPlugins();
        parent::tearDown();
    }

    // Identity

    public function testIdentityIsDerivedFromTheDirectoryName(): void
    {
        $plugin = $this->makePlugin('identity-demo');

        $this->assertSame('identity-demo', $plugin->id());
        $this->assertSame('identityDemo', $plugin->name());
        $this->assertSame('plugin:identity-demo', $plugin->namespace());
        $this->assertSame($this->pluginsPath . '/identity-demo', $plugin->path());
    }

    public function testTrailingSlashesInThePathDoNotChangeTheId(): void
    {
        $plugin = new Plugin($this->pluginsPath . '/slash-demo/', new Container(), App::instance());

        $this->assertSame('slash-demo', $plugin->id());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'reserved id' => ['plugin'];
        yield 'uppercase' => ['Demo'];
        yield 'underscore' => ['my_plugin'];
        yield 'dot' => ['my.plugin'];
        yield 'space' => ['my plugin'];
        yield 'parent directory' => ['..'];
        yield 'current directory' => ['.'];
        yield 'unicode' => ['plügin'];
        yield 'trailing newline' => ["demo\n"];
    }

    #[DataProvider('invalidIds')]
    public function testInvalidIdsAreRejected(string $id): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Plugin('/some/path/' . $id, new Container(), App::instance());
    }

    // Manifest

    public function testManifestIsReadFromPluginYaml(): void
    {
        $plugin = $this->makePlugin('manifest-demo', '', ['plugin.yaml' => "title: Demo\nversion: '2.0.0'\nconfig:\n  greeting: hi\n"]);

        $this->assertSame('Demo', $plugin->manifest()->title());
        $this->assertSame('2.0.0', $plugin->manifest()->version());
        $this->assertSame(['greeting' => 'hi'], $plugin->manifest()->config());
    }

    public function testManifestIsLoadedOnce(): void
    {
        $plugin = $this->makePlugin('manifest-once', '', ['plugin.yaml' => "title: First\n"]);

        $this->assertSame($plugin->manifest(), $plugin->manifest());
    }

    public function testMissingManifestProducesAnEmptyOne(): void
    {
        $plugin = $this->makePlugin('manifest-missing');

        $this->assertNull($plugin->manifest()->title());
        $this->assertSame([], $plugin->manifest()->config());
    }

    public function testInvalidManifestPropertiesAreRejected(): void
    {
        $plugin = $this->makePlugin('manifest-invalid', '', ['plugin.yaml' => "unknown: yes\n"]);

        $this->expectException(UnexpectedValueException::class);

        $plugin->manifest();
    }

    // Event listeners

    public function testEventListenersAreDiscoveredFromPublicOnMethods(): void
    {
        $plugin = $this->makePlugin('listeners-demo', <<<'PHP'
                public function onPageLoaded(): void {}
                public function onResponseBeforeSend(): void {}
                protected function onProtected(): void {}
                private function onPrivate(): void {}
                public function notAListener(): void {}
            PHP);

        $this->assertSame([
            'pageLoaded'         => 'onPageLoaded',
            'responseBeforeSend' => 'onResponseBeforeSend',
        ], $plugin->getEventListeners());
    }

    public function testPluginsWithoutListenersRegisterNone(): void
    {
        $this->assertSame([], $this->makePlugin('listeners-none')->getEventListeners());
    }

    public function testRegularMethodsStartingWithOnAreNotTreatedAsListeners(): void
    {
        $plugin = $this->makePlugin('listeners-prefix', <<<'PHP'
                public function onlineUsers(): array { return []; }
                public function onion(): void {}
            PHP);

        $this->assertSame([], $plugin->getEventListeners(), 'Only "on" followed by an uppercase letter should register listeners.');
    }

    // Serialization and state

    public function testToArray(): void
    {
        $plugin = $this->makePlugin('array-demo', '', ['plugin.yaml' => "title: Demo\n"]);

        $array = $plugin->toArray();

        $this->assertSame('array-demo', $array['id']);
        $this->assertSame('arrayDemo', $array['name']);
        $this->assertSame($plugin->path(), $array['path']);
        $this->assertSame('Demo', $array['manifest']['title']);
        $this->assertFalse($array['enabled']);
        $this->assertFalse($array['initialized']);
    }

    public function testAutoloadIsOptional(): void
    {
        $this->assertNull($this->makePlugin('autoload-demo')->autoload());
    }

    public function testPluginsAreDisabledByDefault(): void
    {
        $this->assertFalse($this->makePlugin('disabled-demo')->isEnabled());
    }

    public function testEnabledStateIsReadFromTheConfigUnderTheCamelCaseName(): void
    {
        $plugin = $this->makePlugin('enabled-demo');
        App::instance()->config()->set('plugins.enabledDemo.enabled', true);

        $this->assertTrue($plugin->isEnabled());
    }

    // Initialization

    public function testInitializationMarksThePluginAsInitialized(): void
    {
        $plugin = $this->makePlugin('init-demo');

        $this->assertFalse($plugin->isInitialized());
        $plugin->initialize();
        $this->assertTrue($plugin->isInitialized());
    }

    public function testInitializationIsIdempotent(): void
    {
        $plugin = $this->makePlugin('init-twice', <<<'PHP'
                public int $services = 0;

                protected function loadServices(\Formwork\Services\Container $container): void
                {
                    ++$this->services;
                }
            PHP);

        $plugin->initialize();
        $plugin->initialize();

        $this->assertSame(1, $plugin->services);
    }

    public function testManifestConfigIsLoadedAsDefaults(): void
    {
        $plugin = $this->makePlugin('configdemo', '', ['plugin.yaml' => "config:\n  greeting: hi\n  nested:\n    a: 1\n"]);

        $plugin->initialize();

        $this->assertSame('hi', App::instance()->config()->get('plugins.configdemo.greeting'));
        $this->assertSame(1, App::instance()->config()->get('plugins.configdemo.nested.a'));
    }

    public function testUserConfigWinsOverManifestDefaults(): void
    {
        $plugin = $this->makePlugin('configoverride', '', ['plugin.yaml' => "config:\n  greeting: default\n  other: kept\n"]);
        App::instance()->config()->set('plugins.configoverride.greeting', 'custom');

        $plugin->initialize();

        $this->assertSame('custom', App::instance()->config()->get('plugins.configoverride.greeting'));
        $this->assertSame('kept', App::instance()->config()->get('plugins.configoverride.other'));
    }

    public function testManifestConfigIsStoredWhereIsEnabledAndTheSettingsPanelLookForIt(): void
    {
        $plugin = $this->makePlugin('config-hyphen', '', ['plugin.yaml' => "config:\n  greeting: hi\n"]);
        $plugin->initialize();

        $this->assertSame('hi', App::instance()->config()->get("plugins.{$plugin->name()}.greeting"), 'Defaults must live under the same key as "enabled"');
    }

    public function testInitializationFailuresAreWrapped(): void
    {
        $plugin = $this->makePlugin('init-broken', '', ['plugin.yaml' => "unknown: yes\n"]);

        try {
            $plugin->initialize();
            $this->fail('Initialization should have failed.');
        } catch (PluginInitializationException $exception) {
            $this->assertStringContainsString('initBroken', $exception->getMessage());
            $this->assertInstanceOf(UnexpectedValueException::class, $exception->getPrevious());
        }

        $this->assertFalse($plugin->isInitialized());
    }

    public function testAssetsAreRegisteredWhenTheDirectoryExists(): void
    {
        $plugin = $this->makePlugin('assets-demo', '', ['assets/css/style.css' => 'body {}']);

        $plugin->initialize();

        $asset = App::instance()->assets()->get('@plugin:assets-demo/css/style.css');
        $this->assertStringEndsWith('/plugins/assets-demo/assets/css/style.css', $asset->uri());
        $this->assertSame('body {}', $asset->content());
    }

    // Scheme

    public function testSchemeFallsBackToTheBasePluginScheme(): void
    {
        $plugin = $this->makePlugin('scheme-default');

        $this->assertSame('plugins.plugin', $plugin->scheme()->id());
        $this->assertSame($plugin->scheme(), $plugin->scheme());
    }

    public function testSchemeIsLoadedFromThePluginAndAlwaysExtendsTheBaseScheme(): void
    {
        $plugin = $this->makePlugin('scheme-custom', '', ['schemes/plugins/scheme-custom.yaml' => "title: Custom\nfields:\n  greeting:\n    type: text\n"]);

        $scheme = $plugin->scheme();

        $this->assertSame('plugins.scheme-custom', $scheme->id());
        $this->assertContains('greeting', $scheme->fields()->keys());
        $this->assertContains('enabled', $scheme->fields()->keys(), 'The base plugin scheme provides the "enabled" field');
    }
}
