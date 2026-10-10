<?php

namespace Formwork\Tests\Unit\Schemes;

use Formwork\Schemes\Scheme;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Schemes\Fixtures\BuildsSchemes;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Schemes::class)]
final class SchemesTest extends TestCase
{
    use BuildsSchemes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchemes();
    }

    protected function tearDown(): void
    {
        $this->tearDownSchemes();
        parent::tearDown();
    }

    public function testNothingIsAvailableBeforeLoading(): void
    {
        $this->assertFalse($this->schemes->has('page'));
        $this->assertSame([], $this->schemes->getAll());
    }

    public function testLoadedSchemesAreAvailableAndParsedLazily(): void
    {
        $this->writeScheme('page', "title: Page\n");

        $this->assertTrue($this->schemes->has('page'));
        $scheme = $this->schemes->get('page');
        $this->assertInstanceOf(Scheme::class, $scheme);
        $this->assertSame('page', $scheme->id());
        $this->assertSame('Page', $scheme->title());
    }

    public function testSchemesAreCreatedOnlyOnce(): void
    {
        $this->writeScheme('page', "title: Page\n");

        $this->assertSame($this->schemes->get('page'), $this->schemes->get('page'));
    }

    public function testUnknownSchemeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scheme "missing"');

        $this->schemes->get('missing');
    }

    public function testOnlyReadableYamlFilesAreLoaded(): void
    {
        FileSystem::write($this->schemesPath . '/notyaml.txt', 'title: Nope');
        FileSystem::write($this->schemesPath . '/yml.yml', 'title: Nope');

        $this->schemes->load('notyaml', $this->schemesPath . '/notyaml.txt');
        $this->schemes->load('yml', $this->schemesPath . '/yml.yml');

        $this->assertFalse($this->schemes->has('notyaml'));
        $this->assertFalse($this->schemes->has('yml'));
    }

    public function testReloadingAnIdReplacesTheScheme(): void
    {
        $this->writeScheme('page', "title: First\n");
        $this->assertSame('First', $this->schemes->get('page')->title());

        $this->writeScheme('page', "title: Second\n");

        $this->assertSame('Second', $this->schemes->get('page')->title());
    }

    public function testLoadFromPathUsesDottedIdsForSubdirectories(): void
    {
        FileSystem::createDirectory($this->schemesPath . '/pages');
        FileSystem::write($this->schemesPath . '/pages/post.yaml', "title: Post\n");
        FileSystem::write($this->schemesPath . '/default.yaml', "title: Default\n");
        FileSystem::write($this->schemesPath . '/readme.txt', 'ignored');

        $this->schemes->loadFromPath($this->schemesPath);

        $this->assertTrue($this->schemes->has('pages.post'));
        $this->assertTrue($this->schemes->has('default'));
        $this->assertFalse($this->schemes->has('readme'));
        $this->assertSame('Post', $this->schemes->get('pages.post')->title());
    }

    public function testLoadFromPathOverridesSchemesWithTheSameId(): void
    {
        $other = FileSystem::joinPaths(TESTS_TMP_PATH, 'schemes-sandbox', 'site');
        FileSystem::createDirectory($other);
        FileSystem::write($this->schemesPath . '/page.yaml', "title: System\n");
        FileSystem::write($other . '/page.yaml', "title: Site\n");

        $this->schemes->loadFromPath($this->schemesPath);
        $this->schemes->loadFromPath($other);

        $this->assertSame('Site', $this->schemes->get('page')->title());
    }

    public function testGetMultipleKeepsTheRequestedKeys(): void
    {
        $this->writeScheme('a', "title: A\n");
        $this->writeScheme('b', "title: B\n");

        $schemes = $this->schemes->getMultiple(['b', 'a']);

        $this->assertSame(['b', 'a'], array_keys($schemes));
        $this->assertSame('B', $schemes['b']->title());
    }

    public function testGetMultipleFailsOnUnknownIds(): void
    {
        $this->writeScheme('a', "title: A\n");

        $this->expectException(InvalidArgumentException::class);

        $this->schemes->getMultiple(['a', 'missing']);
    }

    public function testGetAllReturnsEveryLoadedScheme(): void
    {
        $this->writeScheme('a', "title: A\n");
        $this->writeScheme('b', "title: B\n");

        $this->assertSame(['a', 'b'], array_keys($this->schemes->getAll()));
    }

    public function testSchemesWithEmptyFilesAreRejectedWithoutAFatalError(): void
    {
        $this->writeScheme('empty', '');

        $this->assertSame('empty', $this->schemes->get('empty')->id());
    }
}
