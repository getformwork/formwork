<?php

namespace Formwork\Tests\Unit\Files;

use Formwork\Data\AbstractCollection;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileCollection::class)]
final class FileCollectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testFileCollectionIsTypedAssociativeAndImmutable(): void
    {
        $path = TESTS_TMP_PATH . '/first.txt';
        FileSystem::write($path, '1');
        $collection = new FileCollection([new File($path)]);

        $this->assertInstanceOf(AbstractCollection::class, $collection);
        $this->assertTrue($collection->isAssociative());
        $this->assertSame(File::class, $collection->dataType());
        $this->assertFalse($collection->isMutable());
    }

    public function testListInputIsIndexedByFileName(): void
    {
        $firstPath = TESTS_TMP_PATH . '/first.txt';
        $secondPath = TESTS_TMP_PATH . '/second.txt';
        FileSystem::write($firstPath, '1');
        FileSystem::write($secondPath, '2');
        $first = new File($firstPath);
        $second = new File($secondPath);

        $collection = new FileCollection([$first, $second]);

        $this->assertSame([$first, $second], $collection->values());
        $this->assertSame(['first.txt', 'second.txt'], $collection->keys());
    }

    public function testCollectionKeysCorrespondToEachFileName(): void
    {
        $firstPath = TESTS_TMP_PATH . '/first.txt';
        $secondPath = TESTS_TMP_PATH . '/second.txt';
        FileSystem::write($firstPath, '1');
        FileSystem::write($secondPath, '2');
        $collection = new FileCollection([new File($firstPath), new File($secondPath)]);

        $this->assertSame(
            $collection->keys(),
            array_map(static fn(File $file): string => $file->name(), $collection->values()),
        );
    }

    public function testListInputIsKeyedByEachFileNameAndPreservesOrder(): void
    {
        $first = new File('/files/first.txt');
        $second = new File('/files/second.txt');
        $collection = new FileCollection([$first, $second]);

        $this->assertTrue($collection->isAssociative());
        $this->assertTrue($collection->isTyped());
        $this->assertSame(File::class, $collection->dataType());
        $this->assertSame(['first.txt', 'second.txt'], $collection->keys());
        $this->assertSame([$first, $second], $collection->values());
        $this->assertSame($first, $collection->first());
        $this->assertSame($second, $collection->last());
    }

    public function testAssociativeInputUsesItsExplicitKeys(): void
    {
        $file = new File('/files/original.txt');
        $collection = new FileCollection(['alias' => $file]);

        $this->assertSame($file, $collection->get('alias'));
        $this->assertTrue($collection->has('alias'));
        $this->assertSame(['alias'], $collection->keys());
    }

    public function testEmptyCollectionHasNoItems(): void
    {
        $collection = new FileCollection();

        $this->assertTrue($collection->isEmpty());
        $this->assertSame(0, $collection->count());
        $this->assertNull($collection->first());
        $this->assertSame([], $collection->toArray());
    }

    public function testCollectionRejectsNonFileItems(): void
    {
        $this->expectException(\TypeError::class);
        new FileCollection(['not a File']);
    }

    public function testFilesCannotBeAddedOrRemovedBecauseThisCollectionIsImmutable(): void
    {
        $collection = new FileCollection([new File('/files/one.txt')]);

        $this->expectException(LogicException::class);
        $collection->remove('one.txt');
    }

    public function testFilteringReturnsAFileCollectionAndRetainsFileIdentity(): void
    {
        $first = new File('/files/first.txt');
        $second = new File('/files/second.jpg');
        $collection = new FileCollection([$first, $second]);

        $filtered = $collection->filter(fn(File $file) => $file->extension() === 'jpg');

        $this->assertInstanceOf(FileCollection::class, $filtered);
        $this->assertSame(['second.jpg' => $second], $filtered->toArray());
        $this->assertSame(['first.txt' => $first, 'second.jpg' => $second], $collection->toArray());
    }
}
