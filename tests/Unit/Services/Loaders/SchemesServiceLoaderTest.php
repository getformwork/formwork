<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Closure;
use Formwork\Config\Config;
use Formwork\Fields\Dynamic\DynamicFieldValue;
use Formwork\Fields\FieldFactory;
use Formwork\Schemes\SchemeFactory;
use Formwork\Schemes\Schemes;
use Formwork\Services\Container;
use Formwork\Services\Loaders\SchemesServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(SchemesServiceLoader::class)]
final class SchemesServiceLoaderTest extends TestCase
{
    private ?Closure $originalLoader = null;

    /**
     * @var array<string, mixed>
     */
    private array $originalVars = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->originalLoader = (new ReflectionProperty(DynamicFieldValue::class, 'varsLoader'))->isInitialized() ? DynamicFieldValue::$varsLoader : null;
        $this->originalVars = (new ReflectionProperty(DynamicFieldValue::class, 'vars'))->getValue();

        FileSystem::createDirectory(TESTS_TMP_PATH . '/schemes/system/pages', recursive: true);
        FileSystem::createDirectory(TESTS_TMP_PATH . '/schemes/site', recursive: true);
        FileSystem::write(TESTS_TMP_PATH . '/schemes/system/pages/page.yaml', "title: System page\n");
        FileSystem::write(TESTS_TMP_PATH . '/schemes/system/default.yaml', "title: Default\n");
        FileSystem::write(TESTS_TMP_PATH . '/dynamic-vars.php', "<?php\nreturn fn() => ['answer' => 42];\n");
    }

    protected function tearDown(): void
    {
        if ($this->originalLoader !== null) {
            DynamicFieldValue::$varsLoader = $this->originalLoader;
        }
        (new ReflectionProperty(DynamicFieldValue::class, 'vars'))->setValue(null, $this->originalVars);
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testSystemSchemesAreLoaded(): void
    {
        $schemes = $this->resolve();

        $this->assertTrue($schemes->has('pages.page'));
        $this->assertTrue($schemes->has('default'));
        $this->assertSame('System page', $schemes->get('pages.page')->title());
    }

    public function testSiteSchemesOverrideSystemOnes(): void
    {
        FileSystem::createDirectory(TESTS_TMP_PATH . '/schemes/site/pages');
        FileSystem::write(TESTS_TMP_PATH . '/schemes/site/pages/page.yaml', "title: Site page\n");
        FileSystem::write(TESTS_TMP_PATH . '/schemes/site/pages/post.yaml', "title: Post\n");

        $schemes = $this->resolve();

        $this->assertSame('Site page', $schemes->get('pages.page')->title());
        $this->assertSame('Post', $schemes->get('pages.post')->title());
        $this->assertSame('Default', $schemes->get('default')->title());
    }

    public function testDynamicFieldVariablesAreLoadedLazilyFromTheConfiguredFile(): void
    {
        $this->resolve();
        (new ReflectionProperty(DynamicFieldValue::class, 'vars'))->setValue(null, []);

        $this->assertSame(['answer' => 42], (DynamicFieldValue::$varsLoader)());
    }

    private function resolve(): Schemes
    {
        $config = new Config(['system' => [
            'schemes'      => ['paths' => ['system' => TESTS_TMP_PATH . '/schemes/system', 'site' => TESTS_TMP_PATH . '/schemes/site']],
            'fields'       => ['path' => TESTS_TMP_PATH . '/fields', 'dynamic' => ['vars' => ['file' => TESTS_TMP_PATH . '/dynamic-vars.php']]],
            'translations' => ['fallback' => 'en'],
        ]], resolved: true);

        $container = new Container();
        $container->define(Container::class, $container);
        $container->define(Config::class, $config);
        $container->define(Translations::class, new Translations($config));
        $container->define(Schemes::class)->loader(SchemesServiceLoader::class);

        $schemes = $container->get(Schemes::class);

        $this->assertTrue($container->has(SchemeFactory::class));
        $this->assertTrue($container->has(FieldFactory::class));

        return $schemes;
    }
}
