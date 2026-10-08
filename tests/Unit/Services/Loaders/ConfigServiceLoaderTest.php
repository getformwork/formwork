<?php

namespace Formwork\Tests\Unit\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Http\Request;
use Formwork\Services\Container;
use Formwork\Services\Loaders\ConfigServiceLoader;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

#[CoversClass(ConfigServiceLoader::class)]
final class ConfigServiceLoaderTest extends TestCase
{
    private const string CACHE_PATH = ROOT_PATH . '/cache/config/';

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
        foreach ($this->plantedFiles as $file) {
            if (FileSystem::exists($file)) {
                FileSystem::delete($file);
            }
        }
        date_default_timezone_set($this->timezone);
        parent::tearDown();
    }

    #[DataProvider('validHostProvider')]
    public function testCacheFileIsNamedAfterTheNormalizedHost(string $host, string $expectedName): void
    {
        $cacheFile = $this->cacheFile($host);

        $this->assertSame(self::CACHE_PATH . $expectedName, $cacheFile);
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
        yield 'backslash traversal' => ['..\\..\\windows\\win'];
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

        if ($cacheFile !== null) {
            $this->assertTrue(Path::isRelativeTo($cacheFile, self::CACHE_PATH));
            $this->assertSame(Path::normalize(self::CACHE_PATH), Path::dirname($cacheFile) . '/');
        }

        $this->addToAssertionCount(1);
    }

    public function testCacheDirectoryIsCreatedWhenMissing(): void
    {
        $this->cacheFile('example.test');

        $this->assertDirectoryExists(self::CACHE_PATH);
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
        $file = self::CACHE_PATH . 'config.uncached.test.php';
        $this->plantedFiles[] = $file;

        $this->load('uncached.test');

        $this->assertFileDoesNotExist($file);
    }

    public function testFreshCacheFileIsUsed(): void
    {
        $this->plantCache('cached.test', 'Pacific/Auckland', time() + 10);

        $config = $this->load('cached.test');

        $this->assertSame('Pacific/Auckland', $config->getString('system.date.timezone'));
        $this->assertSame('Pacific/Auckland', date_default_timezone_get());
    }

    public function testStaleCacheFileIsIgnored(): void
    {
        $this->plantCache('stale.test', 'Pacific/Auckland', time() - 100000);

        $config = $this->load('stale.test');

        $this->assertNotSame('Pacific/Auckland', $config->getString('system.date.timezone'));
    }

    public function testCacheOfAnotherHostIsNotUsed(): void
    {
        $this->plantCache('one.test', 'Pacific/Auckland', time() + 10);

        $config = $this->load('two.test');

        $this->assertNotSame('Pacific/Auckland', $config->getString('system.date.timezone'));
    }

    public function testSessionIsConfiguredFromTheLoadedConfig(): void
    {
        $request = $this->request('uncached.test');

        $config = (new ConfigServiceLoader($request))->load(new Container());

        $this->assertSame($config->getInt('system.session.duration'), $this->sessionDuration($request));
    }

    public function testClearCacheRemovesCachedFilesAndRecreatesTheDirectory(): void
    {
        $this->plantedFiles[] = $file = self::CACHE_PATH . 'config.to-clear.test.php';
        $this->ensureCacheDirectory();
        FileSystem::write($file, '<?php return [];');

        ConfigServiceLoader::clearCache();

        $this->assertFileDoesNotExist($file);
        $this->assertDirectoryExists(self::CACHE_PATH);
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
        $method = new ReflectionMethod(ConfigServiceLoader::class, 'getConfigCacheFile');

        return $method->invoke(new ConfigServiceLoader($this->request($host)));
    }

    private function load(string $host): Config
    {
        return (new ConfigServiceLoader($this->request($host)))->load(new Container());
    }

    private function sessionDuration(Request $request): int
    {
        $session = $request->session();
        $property = new \ReflectionProperty($session, 'duration');

        return (int) $property->getValue($session);
    }

    private function ensureCacheDirectory(): void
    {
        if (!FileSystem::isDirectory(self::CACHE_PATH, assertExists: false)) {
            FileSystem::createDirectory(self::CACHE_PATH, recursive: true);
        }
    }

    private function plantCache(string $host, string $timezone, int $modifiedTime): void
    {
        $this->ensureCacheDirectory();

        $file = self::CACHE_PATH . "config.{$host}.php";
        $this->plantedFiles[] = $file;

        $config = new Config();
        $config->loadFromPath(SYSTEM_PATH . '/config/');
        $config->resolve(['%ROOT_PATH%' => ROOT_PATH, '%SYSTEM_PATH%' => SYSTEM_PATH]);
        $data = $config->toArray();
        $data['system']['date']['timezone'] = $timezone;

        FileSystem::write($file, '<?php return ' . var_export($data, true) . ';');
        touch($file, $modifiedTime);
    }
}
