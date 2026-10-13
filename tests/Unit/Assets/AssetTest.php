<?php

namespace Formwork\Tests\Unit\Assets;

use Formwork\Assets\Asset;
use Formwork\Assets\Exceptions\AssetNotFoundException;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Asset::class)]
final class AssetTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->path = TESTS_TMP_PATH . '/assets/style.css';
        FileSystem::createDirectory(dirname($this->path), recursive: true);
        FileSystem::write($this->path, "body { color: red; }\n");
        touch($this->path, 1_700_000_000);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testMissingFilesAreRejected(): void
    {
        $this->expectException(AssetNotFoundException::class);

        new Asset(TESTS_TMP_PATH . '/assets/missing.css', '/assets/missing.css');
    }

    public function testDirectoriesAreNotAssets(): void
    {
        $this->expectException(AssetNotFoundException::class);

        new Asset(TESTS_TMP_PATH . '/assets', '/assets');
    }

    public function testPathIsNormalized(): void
    {
        $asset = new Asset(TESTS_TMP_PATH . '/assets/../assets/./style.css', '/assets/style.css');

        $this->assertSame(FileSystem::normalizePath($this->path), $asset->path());
    }

    public function testUriIsNormalized(): void
    {
        $this->assertSame('/assets/style.css', (new Asset($this->path, '/assets//style.css'))->uri());
    }

    public function testVersionIsTheHexModificationTime(): void
    {
        $asset = new Asset($this->path, '/assets/style.css');

        $this->assertSame(dechex(1_700_000_000), $asset->version());
        $this->assertSame('/assets/style.css?v=' . dechex(1_700_000_000), $asset->uri(includeVersion: true));
    }

    public function testVersionIsFixedOnceComputed(): void
    {
        $asset = new Asset($this->path, '/assets/style.css');
        $first = $asset->version();

        touch($this->path, 1_800_000_000);

        $this->assertSame($first, $asset->version());
    }

    public function testIntegrityHashIsASha256SubresourceIntegrityValue(): void
    {
        $asset = new Asset($this->path, '/assets/style.css');

        $expected = 'sha256-' . base64_encode(hash('sha256', "body { color: red; }\n", true));

        $this->assertSame($expected, $asset->integrityHash());
        $this->assertMatchesRegularExpression('/^sha256-[A-Za-z0-9+\/]{43}=$/', $asset->integrityHash());
    }

    public function testMimeType(): void
    {
        $this->assertSame('text/css', (new Asset($this->path, '/assets/style.css'))->mimeType());
    }

    public function testContentIsReadFromDisk(): void
    {
        $asset = new Asset($this->path, '/assets/style.css');

        $this->assertSame("body { color: red; }\n", $asset->content());

        FileSystem::write($this->path, 'changed');
        $this->assertSame('changed', $asset->content(), 'Content is not cached');
    }

    public function testBase64DataUri(): void
    {
        $asset = new Asset($this->path, '/assets/style.css');

        $this->assertSame('data:text/css;base64,' . base64_encode("body { color: red; }\n"), $asset->toBase64());
    }

    public function testMetadata(): void
    {
        $asset = new Asset($this->path, '/assets/style.css', ['async' => true, 'media' => null]);

        $this->assertTrue($asset->getMeta('async'));
        $this->assertNull($asset->getMeta('missing'));
        $this->assertSame('default', $asset->getMeta('missing', 'default'));
        $this->assertSame('default', $asset->getMeta('media', 'default'), 'A null value counts as missing');
    }
}
