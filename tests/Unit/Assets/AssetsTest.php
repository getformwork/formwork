<?php

namespace Formwork\Tests\Unit\Assets;

use Formwork\Assets\Asset;
use Formwork\Assets\Assets;
use Formwork\Assets\Exceptions\AssetResolutionException;
use Formwork\Tests\TestCase;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Assets::class)]
final class AssetsTest extends TestCase
{
    private string $base;

    private Assets $assets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();

        $this->base = TESTS_TMP_PATH . '/assets-sandbox';
        FileSystem::createDirectory($this->base . '/template/css', recursive: true);
        FileSystem::createDirectory($this->base . '/panel/js', recursive: true);
        FileSystem::write($this->base . '/template/css/site.css', 'body {}');
        FileSystem::write($this->base . '/template/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        FileSystem::write($this->base . '/panel/js/app.js', 'console.log(1);');
        FileSystem::write($this->base . '/secret.txt', 'outside of every namespace');
        FileSystem::write($this->base . '/panel-extra.txt', 'sibling of the panel namespace');

        $this->assets = new Assets();
        $this->assets->setResolutionPaths([
            'template' => ['path' => $this->base . '/template', 'uri' => '/site/assets'],
            'panel'    => ['path' => $this->base . '/panel', 'uri' => '/panel/assets/'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testKeysWithoutNamespaceUseTheTemplateNamespace(): void
    {
        $asset = $this->assets->get('css/site.css');

        $this->assertSame(FileSystem::normalizePath($this->base . '/template/css/site.css'), $asset->path());
        $this->assertSame('/site/assets/css/site.css', $asset->uri());
    }

    public function testNamespacedKeysUseTheirOwnResolutionPath(): void
    {
        $asset = $this->assets->get('@panel/js/app.js');

        $this->assertSame(FileSystem::normalizePath($this->base . '/panel/js/app.js'), $asset->path());
        $this->assertSame('/panel/assets/js/app.js', $asset->uri());
    }

    public function testUrisWithAndWithoutTrailingSlashAreEquivalent(): void
    {
        $this->assertSame('/site/assets/logo.svg', $this->assets->get('logo.svg')->uri());
        $this->assertSame('/panel/assets/js/app.js', $this->assets->get('@panel/js/app.js')->uri());
    }

    public function testAssetsAreCreatedOnceAndShared(): void
    {
        $this->assertSame($this->assets->get('css/site.css'), $this->assets->get('css/site.css'));
        $this->assertTrue($this->assets->has('css/site.css'));
        $this->assertFalse($this->assets->has('logo.svg'));
    }

    public function testAddRegistersAnAssetWithMetadata(): void
    {
        $this->assets->add('css/site.css', ['media' => 'print']);

        $this->assertTrue($this->assets->has('css/site.css'));
        $this->assertSame('print', $this->assets->get('css/site.css')->getMeta('media'));
    }

    public function testAddingTheSameKeyAgainKeepsTheFirstAsset(): void
    {
        $this->assets->add('css/site.css', ['media' => 'print']);
        $this->assets->add('css/site.css', ['media' => 'screen']);

        $this->assertSame('print', $this->assets->get('css/site.css')->getMeta('media'));
    }

    public function testGetDoesNotOverrideMetadataOfAlreadyAddedAssets(): void
    {
        $this->assets->add('css/site.css', ['media' => 'print']);

        $this->assertSame('print', $this->assets->get('css/site.css')->getMeta('media'));
    }

    public function testTypedCollectionsOnlyContainRequestedAssets(): void
    {
        $this->assets->add('css/site.css');
        $this->assets->add('@panel/js/app.js');
        $this->assets->add('logo.svg');

        $this->assertSame(['css/site.css'], $this->assets->stylesheets()->keys());
        $this->assertSame(['@panel/js/app.js'], $this->assets->scripts()->keys());
        $this->assertSame(['logo.svg'], $this->assets->images()->keys());
    }

    public function testUnknownNamespacesAreRejected(): void
    {
        $this->expectException(AssetResolutionException::class);
        $this->expectExceptionMessage('namespace "unknown" not defined');

        $this->assets->get('@unknown/file.css');
    }

    public function testNamespaceWithoutPathIsRejected(): void
    {
        $this->expectException(AssetResolutionException::class);
        $this->expectExceptionMessage('invalid namespaced syntax');

        $this->assets->get('@panel');
    }

    public function testKeysWithoutTemplateNamespaceFailWhenNoneIsDefined(): void
    {
        $this->expectException(AssetResolutionException::class);

        (new Assets())->get('file.css');
    }

    public function testResolutionPathsCanBeExtendedWithoutLosingPreviousOnes(): void
    {
        FileSystem::createDirectory($this->base . '/plugin', recursive: true);
        FileSystem::write($this->base . '/plugin/p.css', 'p {}');

        $this->assets->setResolutionPaths(['plugin:demo' => ['path' => $this->base . '/plugin', 'uri' => '/plugins/demo/assets']]);

        $this->assertSame('/plugins/demo/assets/p.css', $this->assets->get('@plugin:demo/p.css')->uri());
        $this->assertSame('/site/assets/logo.svg', $this->assets->get('logo.svg')->uri());
    }

    public function testMissingFilesInAKnownNamespaceFail(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->assets->get('css/missing.css');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function traversalKeys(): iterable
    {
        yield 'parent of the template namespace' => ['../secret.txt'];
        yield 'nested then parent' => ['css/../../secret.txt'];
        yield 'namespaced parent' => ['@panel/../secret.txt'];
        yield 'sibling sharing the prefix' => ['@panel/../panel-extra.txt'];
        yield 'deep traversal' => ['../../../../../../../../etc/hostname'];
        yield 'backslashes' => ['..\secret.txt'];
        yield 'dot segments mixed with separators' => ['css/./../../secret.txt'];
        yield 'url encoded dots' => ['%2e%2e/secret.txt'];
        yield 'absolute path' => ['/etc/hostname'];
        yield 'null byte' => ["css/site.css\0../../secret.txt"];
    }

    #[DataProvider('traversalKeys')]
    public function testAssetsOutsideTheNamespaceRootCannotBeRequested(string $key): void
    {
        try {
            $asset = $this->assets->get($key);
        } catch (\Throwable) {
            $this->addToAssertionCount(1);
            return;
        }

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertTrue(
            $this->isInside($asset->path(), $this->base . '/template') || $this->isInside($asset->path(), $this->base . '/panel'),
            sprintf('Key "%s" resolved to "%s", outside the namespace roots', addcslashes($key, "\0"), $asset->path())
        );
    }

    private function isInside(string $path, string $root): bool
    {
        $root = FileSystem::normalizePath($root);
        return str_starts_with($path, rtrim($root, '/\\') . DIRECTORY_SEPARATOR);
    }
}
