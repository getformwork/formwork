<?php

namespace Formwork\Tests\Unit\Assets;

use Formwork\Assets\Asset;
use Formwork\Assets\AssetCollection;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AssetCollection::class)]
final class AssetCollectionTest extends TestCase
{
    private AssetCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        FileSystem::createDirectory(TESTS_TMP_PATH . '/assets', recursive: true);

        $this->collection = new AssetCollection();
        $this->collection->set('style', $this->asset('style.css', 'body {}'));
        $this->collection->set('app', $this->asset('app.js', 'console.log(1);'));
        $this->collection->set('logo', $this->asset('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'));
        $this->collection->set('photo', $this->asset('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', true)));
        $this->collection->set('notes', $this->asset('notes.txt', 'plain'));
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testStylesheets(): void
    {
        $this->assertSame(['style'], $this->collection->stylesheets()->keys());
    }

    public function testScripts(): void
    {
        $this->assertSame(['app'], $this->collection->scripts()->keys());
    }

    public function testImages(): void
    {
        $this->assertSame(['logo', 'photo'], $this->collection->images()->keys());
    }

    public function testFiltersReturnNewCollectionsAndLeaveTheOriginalUntouched(): void
    {
        $stylesheets = $this->collection->stylesheets();

        $this->assertNotSame($this->collection, $stylesheets);
        $this->assertCount(5, $this->collection);
    }

    public function testEmptyCollectionYieldsEmptyFilters(): void
    {
        $empty = new AssetCollection();

        $this->assertCount(0, $empty->stylesheets());
        $this->assertCount(0, $empty->scripts());
        $this->assertCount(0, $empty->images());
    }

    public function testOnlyAssetsCanBeAdded(): void
    {
        $this->expectException(LogicException::class);

        $this->collection->set('invalid', 'not an asset');
    }

    private function asset(string $name, string $content): Asset
    {
        FileSystem::write(TESTS_TMP_PATH . '/assets/' . $name, $content);
        return new Asset(TESTS_TMP_PATH . '/assets/' . $name, '/assets/' . $name);
    }
}
