<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Services\Container;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(ConfigServiceLoader::class)]
#[UsesApplication]
final class ConfigServiceLoaderTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $plantedFiles = [];

    private string $timezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        foreach ($this->plantedFiles as $plantedFile) {
            if (FileSystem::exists($plantedFile)) {
                FileSystem::delete($plantedFile);
            }
        }
        date_default_timezone_set($this->timezone);
        parent::tearDown();
    }

    #[DataProvider('validHostProvider')]
    public function testCacheFileIsNamedAfterTheNormalizedHost(string $host, string $expectedName): void
    {
        $cacheFile = $this->cacheFile($host);

        $this->assertSame($this->cachePath() . $expectedName, $cacheFile);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validHostProvider(): iterable
    {
        yield 'hostname' => ['example.test', 'config.example.test.php'];
        yield 'uppercase hostname' => ['EXAMPLE.test', 'config.example.test.php'];
        yield 'hostname with a port' => ['example.test:8080', 'config.example.test.php'];
        yield 'subdomain' => ['sub.example.test', 'config.sub.example.test.php'];
        yield 'localhost' => ['localhost', 'config.localhost.php'];
        yield 'IPv4 address' => ['192.0.2.1', 'config.192.0.2.1.php'];
        yield 'hyphenated hostname' => ['my-site.example.test', 'config.my-site.example.test.php'];
    }

    #[DataProvider('hostileHostProvider')]
    public function testHostileHostsNeverProduceACacheFile(string $host): void
    {
        $this->assertNull($this->cacheFile($host));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostileHostProvider(): iterable
    {
        yield 'parent directory' => ['..'];
        yield 'current directory' => ['.'];
        yield 'relative traversal' => ['../../nonexistent/secret'];
        yield 'deep traversal' => ['../../../../../../../tmp/payload'];
        yield 'traversal inside the name' => ['host/../../x'];
        yield 'backslash traversal' => ['..\..\windows\win'];
        yield 'encoded traversal' => ['%2e%2e%2f%2e%2e%2fetc'];
        yield 'absolute path' => ['/nonexistent/secret'];
        yield 'subdirectory' => ['a/b'];
        yield 'null byte' => ["example.test\0.php"];
        yield 'newline' => ["example.test\nsecond"];
        yield 'whitespace' => ['exa mple.test'];
        yield 'query string' => ['example.test?x=1'];
        yield 'empty' => [''];
    }

    #[DataProvider('hostileHostProvider')]
    public function testCacheFileAlwaysStaysInsideTheCacheDirectory(string $host): void
    {
        $cacheFile = $this->cacheFile($host);

        if ($cacheFile === null) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertTrue(Path::isRelativeTo($cacheFile, $this->cachePath()));
        $this->assertSame(Path::normalize($this->cachePath()), Path::dirname($cacheFile) . '/');
    }

    public function testCacheDirectoryIsCreatedWhenMissing(): void
    {
        $this->cacheFile('example.test');

        $this->assertDirectoryExists($this->cachePath());
    }

    public function testConfigIsLoadedFromFilesWhenNoCacheFileExists(): void
    {
        $config = $this->load('uncached.test');

        $this->assertTrue($config->has('system'));
        $this->assertTrue($config->has('site'));
        $this->assertSame(date_default_timezone_get(), $config->getString('system.date.timezone'));
    }

    public function testConfigIsNotCachedWhenRunningFromTheCommandLine(): void
    {
        $file = $this->cachePath() . 'config.uncached.test.php';
        $this->plantedFiles[] = $file;

        $this->load('uncached.test');

        $this->assertFileDoesNotExist($file);
    }

    public function testFreshCacheFileIsUsed(): void
    {
        $this->createCache('cached.test', 'Pacific/Auckland', time() + 10);

        $config = $this->load('cached.test');

        $this->assertSame('Pacific/Auckland', $config->getString('system.date.timezone'));
        $this->assertSame('Pacific/Auckland', date_default_timezone_get());
    }

    public function testStaleCacheFileIsIgnored(): void
    {
        /** @todo use something like `time() - 100000` instead of `1` after the cache paths are not hardcoded anymore in the loader */
        $this->createCache('stale.test', 'Pacific/Auckland', 1);

        $config = $this->load('stale.test');

        $this->assertNotSame('Pacific/Auckland', $config->getString('system.date.timezone'));
    }

    public function testCacheOfAnotherHostIsNotUsed(): void
    {
        $this->createCache('one.test', 'Pacific/Auckland', time() + 10);

        $config = $this->load('two.test');

        $this->assertNotSame('Pacific/Auckland', $config->getString('system.date.timezone'));
    }

    public function testDeprecatedCacheOptionsReplaceThePagesCacheOptions(): void
    {
        $this->createCache('deprecated.test', 'UTC', time() + 10, ['cache' => ['enabled' => true, 'path' => '/old/path', 'time' => 120]]);

        $deprecations = $this->captureDeprecations(function () use (&$config): void {
            $config = $this->load('deprecated.test');
        });

        $this->assertSame([
            'The "system.cache.enabled" configuration option is deprecated since Formwork 2.4.0. Use "system.cache.pages.enabled" instead.',
            'The "system.cache.path" configuration option is deprecated since Formwork 2.4.0. Use "system.cache.pages.path" instead.',
            'The "system.cache.time" configuration option is deprecated since Formwork 2.4.0. Use "system.cache.pages.ttl" instead.',
        ], $deprecations);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertFalse($config->has('system.cache.enabled'));
        $this->assertFalse($config->has('system.cache.path'));
        $this->assertFalse($config->has('system.cache.time'));
        $this->assertTrue($config->getBool('system.cache.pages.enabled'));
        $this->assertSame('/old/path', $config->getString('system.cache.pages.path'));
        $this->assertSame(120, $config->getInt('system.cache.pages.ttl'));
        $this->assertSame('files', $config->getString('system.cache.pages.handler'));
    }

    public function testDeprecatedCacheNamespacesAreMigratedToThePagesCache(): void
    {
        $this->createCache('namespaces.test', 'UTC', time() + 10, ['cache' => ['namespaces' => ['pages' => ['handler' => 'array']]]]);

        $deprecations = $this->captureDeprecations(function () use (&$config): void {
            $config = $this->load('namespaces.test');
        });

        $this->assertSame([
            'The "system.cache.namespaces" configuration option is deprecated since Formwork 2.4.0. Use "system.cache.pages.handler" instead.',
        ], $deprecations);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertFalse($config->has('system.cache.namespaces'));
        $this->assertSame('array', $config->getString('system.cache.pages.handler'));
    }

    public function testConfigWithoutDeprecatedOptionsTriggersNoDeprecations(): void
    {
        $deprecations = $this->captureDeprecations(function (): void {
            $this->load('uncached.test');
        });

        $this->assertSame([], $deprecations);
    }

    public function testSessionIsConfiguredFromTheLoadedConfig(): void
    {
        $request = $this->request('uncached.test');

        $config = (new ConfigServiceLoader($request, App::instance()))->load(new Container());

        $this->assertSame($config->getInt('system.session.duration'), $this->sessionDuration($request));
    }

    public function testClearCacheRemovesCachedFilesAndRecreatesTheDirectory(): void
    {
        $this->plantedFiles[] = $file = $this->cachePath() . 'config.to-clear.test.php';
        $this->ensureCacheDirectory();
        FileSystem::write($file, '<?php return [];');

        ConfigServiceLoader::clearCache(App::instance()->rootPath());

        $this->assertFileDoesNotExist($file);
        $this->assertDirectoryExists($this->cachePath());
    }

    private function request(string $host): Request
    {
        return new Request([], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'HTTP_HOST'      => $host,
        ]);
    }

    private function cacheFile(string $host): ?string
    {
        $reflectionMethod = new ReflectionMethod(ConfigServiceLoader::class, 'getConfigCacheFile');

        return $reflectionMethod->invoke(new ConfigServiceLoader($this->request($host), App::instance()));
    }

    private function load(string $host): Config
    {
        return (new ConfigServiceLoader($this->request($host), App::instance()))->load(new Container());
    }

    private function sessionDuration(Request $request): int
    {
        $session = $request->session();
        $reflectionProperty = new ReflectionProperty($session, 'duration');

        return (int) $reflectionProperty->getValue($session);
    }

    private function ensureCacheDirectory(): void
    {
        if (!FileSystem::isDirectory($this->cachePath(), assertExists: false)) {
            FileSystem::createDirectory($this->cachePath(), recursive: true);
        }
    }

    /**
     * @param array<string, mixed> $system
     */
    private function createCache(string $host, string $timezone, int $modifiedTime, array $system = []): void
    {
        $this->ensureCacheDirectory();

        $file = $this->cachePath() . "config.{$host}.php";
        $this->plantedFiles[] = $file;

        $config = new Config();
        $config->loadFromPath(App::instance()->systemPath() . '/config/');
        $config->resolve([
            '%ROOT_PATH%'   => App::instance()->rootPath(),
            '%SYSTEM_PATH%' => App::instance()->systemPath(),
        ]);
        $data = $config->toArray();
        $data['system']['date']['timezone'] = $timezone;
        $data['system'] = array_replace_recursive($data['system'], $system);

        FileSystem::write($file, '<?php return ' . var_export($data, true) . ';');
        touch($file, $modifiedTime);
    }

    private function cachePath(): string
    {
        return App::instance()->rootPath() . '/cache/config/';
    }
}
