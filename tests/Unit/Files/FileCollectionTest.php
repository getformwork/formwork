<?php

namespace Formwork\Tests\Unit\Files;

use Closure;
use Formwork\Data\AbstractCollection;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

#[CoversClass(FileCollection::class)]
final class FileCollectionTest extends TestCase
{
    public function testFileCollectionIsTypedAssociativeAndImmutable(): void
    {
        $collection = new FileCollection([new File('/files/first.txt')]);

        $this->assertInstanceOf(AbstractCollection::class, $collection);
        $this->assertTrue($collection->isAssociative());
        $this->assertTrue($collection->isTyped());
        $this->assertSame(File::class, $collection->dataType());
        $this->assertFalse($collection->isMutable());
    }

    public function testListInputIsKeyedByEachFileNameAndPreservesOrder(): void
    {
        $first = new File('/files/first.txt');
        $second = new File('/files/second.txt');
        $collection = new FileCollection([$first, $second]);

        $this->assertSame(['first.txt', 'second.txt'], $collection->keys());
        $this->assertSame([$first, $second], $collection->values());
        $this->assertSame($first, $collection->get('first.txt'));
        $this->assertSame($first, $collection->first());
        $this->assertSame($second, $collection->last());
    }

    public function testAssociativeInputUsesItsExplicitKeys(): void
    {
        $file = new File('/files/original.txt');
        $collection = new FileCollection(['alias' => $file]);

        $this->assertSame($file, $collection->get('alias'));
        $this->assertTrue($collection->has('alias'));
        $this->assertFalse($collection->has('original.txt'));
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
        $this->expectException(TypeError::class);
        new FileCollection(['not a File']);
    }

    public function testFilesWithTheSameNameInDifferentDirectoriesKeepOnlyTheLastOne(): void
    {
        $first = new File('/files/a/report.txt');
        $second = new File('/files/b/report.txt');

        $collection = new FileCollection([$first, $second]);

        $this->assertSame(['report.txt'], $collection->keys());
        $this->assertSame($second, $collection->get('report.txt'));
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

    /**
     * @param Closure(FileCollection): mixed $operation
     */
    #[DataProvider('mutatingOperationProvider')]
    public function testImmutableCollectionRejectsMutatingOperations(Closure $operation): void
    {
        $collection = new FileCollection([new File('/files/one.txt')]);

        try {
            $operation($collection);
            $this->fail('The immutable file collection was mutated.');
        } catch (LogicException) {
            $this->assertSame(['one.txt'], $collection->keys());
        }
    }

    /**
     * @return iterable<string, array{Closure(FileCollection): mixed}>
     */
    public static function mutatingOperationProvider(): iterable
    {
        yield 'set' => [static fn(FileCollection $collection) => $collection->set('two.txt', new File('/files/two.txt'))];
        yield 'remove' => [static fn(FileCollection $collection) => $collection->remove('one.txt')];
        yield 'merge' => [static fn(FileCollection $collection) => $collection->merge(new FileCollection([new File('/files/two.txt')]))];
    }
}
