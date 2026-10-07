<?php

namespace Formwork\Tests\Unit\Config;

use Formwork\Config\Config;
use Formwork\Config\Exceptions\ConfigLoadingException;
use Formwork\Config\Exceptions\ConfigResolutionException;
use Formwork\Config\Exceptions\UnresolvedConfigException;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Config::class)]
final class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    public function testEmptyConfigIsMutableButUnresolved(): void
    {
        $config = new Config();

        $this->assertFalse($config->has('missing'));
        $this->assertTrue($config->hasMultiple([]));

        $this->expectException(UnresolvedConfigException::class);
        $config->toArray();
    }

    public function testHasAndHasMultipleDistinguishPresentNullFromMissingKeys(): void
    {
        $config = new Config(['present' => null, 'nested' => ['value' => false]], resolved: true);

        $this->assertTrue($config->has('present'));
        $this->assertTrue($config->has('nested.value'));
        $this->assertFalse($config->has('nested.missing'));
        $this->assertTrue($config->hasMultiple(['present', 'nested.value']));
        $this->assertFalse($config->hasMultiple(['present', 'nested.missing']));
    }

    public function testGetSupportsNestedPathsDefaultsAndExplicitNullValues(): void
    {
        $config = new Config([
            'site' => [
                'name'     => 'Formwork',
                'nullable' => null,
            ],
        ], resolved: true);

        $this->assertSame('Formwork', $config->get('site.name'));
        $this->assertNull($config->get('site.nullable', 'fallback'));
        $this->assertSame('fallback', $config->get('site.missing', 'fallback'));
        $this->assertSame('fallback', $config->get('missing', 'fallback'));
    }

    public function testGetMultipleReturnsRequestedKeysWithTheSharedDefault(): void
    {
        $config = new Config(['site' => ['name' => 'Formwork']], resolved: true);

        $this->assertSame([
            'site.name'    => 'Formwork',
            'site.missing' => null,
        ], $config->getMultiple(['site.name', 'site.missing']));
        $this->assertSame([
            'site.name'    => 'Formwork',
            'site.missing' => 'fallback',
        ], $config->getMultiple(['site.name', 'site.missing'], 'fallback'));
    }

    #[DataProvider('typedGetterProvider')]
    public function testTypedGettersReturnTheirDeclaredTypes(string $getter, string $key, mixed $value): void
    {
        $config = new Config([$key => $value], resolved: true);

        $this->assertSame($value, $config->{$getter}($key));
    }

    public static function typedGetterProvider(): iterable
    {
        yield 'string' => ['getString', 'name', 'Formwork'];
        yield 'boolean' => ['getBool', 'enabled', true];
        yield 'integer' => ['getInt', 'attempts', 3];
        yield 'float' => ['getFloat', 'ratio', 0.5];
        yield 'array' => ['getArray', 'paths', ['site' => '/site']];
    }

    #[DataProvider('invalidTypedGetterProvider')]
    public function testTypedGettersRejectValuesOfTheWrongType(string $getter, string $key, mixed $value, string $type): void
    {
        $config = new Config([$key => $value], resolved: true);

        $this->expectException(UnexpectedValueException::class);
        $article = in_array($type, ['integer', 'array'], true) ? 'an' : 'a';
        $this->expectExceptionMessage(sprintf('Config value for key "%s" is not %s %s', $key, $article, $type));
        $config->{$getter}($key);
    }

    public static function invalidTypedGetterProvider(): iterable
    {
        yield 'string rejects null' => ['getString', 'name', null, 'string'];
        yield 'boolean rejects integer' => ['getBool', 'enabled', 1, 'boolean'];
        yield 'integer rejects numeric string' => ['getInt', 'attempts', '3', 'integer'];
        yield 'float rejects integer' => ['getFloat', 'ratio', 1, 'float'];
        yield 'array rejects string' => ['getArray', 'paths', '/site', 'array'];
    }

    public function testTypedGettersAcceptTypedDefaultsForMissingValues(): void
    {
        $config = new Config(resolved: true);

        $this->assertSame('default', $config->getString('name', 'default'));
        $this->assertFalse($config->getBool('enabled', false));
        $this->assertSame(7, $config->getInt('attempts', 7));
        $this->assertSame(0.25, $config->getFloat('ratio', 0.25));
        $this->assertSame(['site'], $config->getArray('paths', ['site']));
    }

    public function testSetAndSetMultipleCreateAndOverrideNestedValues(): void
    {
        $config = new Config(['system' => ['debug' => false]], resolved: true);

        $config->set('system.debug', true);
        $config->set('system.paths.cache', '/cache');
        $config->setMultiple(['system.name' => 'Formwork', 'nullable' => null]);

        $this->assertTrue($config->getBool('system.debug'));
        $this->assertSame('/cache', $config->getString('system.paths.cache'));
        $this->assertSame('Formwork', $config->getString('system.name'));
        $this->assertTrue($config->has('nullable'));
        $this->assertNull($config->get('nullable'));
    }

    public function testToArrayReturnsTheResolvedNestedConfiguration(): void
    {
        $data = ['system' => ['paths' => ['site' => '/site']], 'enabled' => true];
        $config = new Config($data, resolved: true);

        $this->assertSame($data, $config->toArray());
    }

    public function testGetAndToArrayRejectReadsBeforeResolution(): void
    {
        $config = new Config(['name' => 'Formwork']);

        $this->expectException(UnresolvedConfigException::class);
        $config->get('name');
    }

    public function testResolveSubstitutesVariablesAndReferencesInNestedValues(): void
    {
        $config = new Config([
            'paths' => [
                'root'  => '${%ROOT_PATH%}',
                'site'  => '${paths.root}/site',
                'cache' => '${CACHE_PATH}/cache',
            ],
        ]);

        $config->resolve([
            '%ROOT_PATH%' => '/project',
            'CACHE_PATH'  => '/tmp',
        ]);

        $this->assertTrue($config->has('paths.site'));
        $this->assertSame('/project', $config->get('paths.root'));
        $this->assertSame('/project/site', $config->get('paths.site'));
        $this->assertSame('/tmp/cache', $config->get('paths.cache'));
        $this->assertSame('/project/site', $config->toArray()['paths']['site']);
    }

    public function testResolveRejectsAnUndefinedReference(): void
    {
        $config = new Config(['path' => '${missing.path}']);

        $this->expectException(ConfigResolutionException::class);
        $this->expectExceptionMessage('undefined key or variable "missing.path"');
        $config->resolve();
    }

    public function testResolveRejectsAReferenceToANonStringValue(): void
    {
        $config = new Config(['port' => 8080, 'url' => 'http://localhost:${port}']);

        $this->expectException(ConfigResolutionException::class);
        $this->expectExceptionMessage('non-string "port"');
        $config->resolve();
    }

    public function testLoadFileLoadsYamlIntoACamelCaseKey(): void
    {
        $path = TESTS_TMP_PATH . '/site-name.yaml';
        FileSystem::write($path, "name: Formwork\noptions:\n  enabled: true\n");
        $config = new Config(resolved: true);

        $config->loadFile($path);

        $this->assertSame('Formwork', $config->getString('siteName.name'));
        $this->assertTrue($config->getBool('siteName.options.enabled'));
    }

    public function testLoadFileLoadsPhpAndMergesWithExistingConfiguration(): void
    {
        $path = TESTS_TMP_PATH . '/settings.php';
        FileSystem::write($path, "<?php return ['enabled' => true, 'nested' => ['loaded' => 'yes']];\n");
        $config = new Config([
            'settings' => [
                'existing' => 'kept',
                'nested'   => ['original' => 'kept'],
            ],
        ], resolved: true);

        $config->loadFile($path);

        $this->assertTrue($config->getBool('settings.enabled'));
        $this->assertSame('kept', $config->getString('settings.existing'));
        $this->assertSame('kept', $config->getString('settings.nested.original'));
        $this->assertSame('yes', $config->getString('settings.nested.loaded'));
    }

    public function testLoadFileSupportsAPrefixForPluginConfiguration(): void
    {
        $path = TESTS_TMP_PATH . '/example.yaml';
        FileSystem::write($path, "enabled: true\n");
        $config = new Config(resolved: true);

        $config->loadFile($path, 'plugins');

        $this->assertTrue($config->getBool('plugins.example.enabled'));
    }

    public function testLoadFromPathLoadsEachSupportedConfigurationFile(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/first.yaml', "name: First\n");
        FileSystem::write(TESTS_TMP_PATH . '/second.php', "<?php return ['enabled' => false];\n");
        $config = new Config(resolved: true);

        $config->loadFromPath(TESTS_TMP_PATH);

        $this->assertSame('First', $config->getString('first.name'));
        $this->assertFalse($config->getBool('second.enabled'));
    }

    public function testLoadFileRejectsMissingFiles(): void
    {
        $path = TESTS_TMP_PATH . '/missing.yaml';

        $this->expectException(ConfigLoadingException::class);
        $this->expectExceptionMessage(sprintf('Config file "%s" does not exist', $path));
        (new Config())->loadFile($path);
    }

    public function testLoadFileRejectsUnsupportedExtensions(): void
    {
        $path = TESTS_TMP_PATH . '/settings.json';
        FileSystem::write($path, '{}');

        $this->expectException(ConfigLoadingException::class);
        $this->expectExceptionMessage('Unsupported config file type "json"');
        (new Config())->loadFile($path);
    }

    public function testFromArrayReconstructsAResolvedConfig(): void
    {
        $config = Config::fromArray([
            'config' => ['system' => ['enabled' => true]],
        ]);

        $this->assertTrue($config->getBool('system.enabled'));
        $this->assertSame(['system' => ['enabled' => true]], $config->toArray());
    }
}
