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
        $fileCollection = new FileCollection([new File('/files/first.txt')]);

        $this->assertInstanceOf(AbstractCollection::class, $fileCollection);
        $this->assertTrue($fileCollection->isAssociative());
        $this->assertTrue($fileCollection->isTyped());
        $this->assertSame(File::class, $fileCollection->dataType());
        $this->assertFalse($fileCollection->isMutable());
    }

    public function testListInputIsKeyedByEachFileNameAndPreservesOrder(): void
    {
        $first = new File('/files/first.txt');
        $second = new File('/files/second.txt');
        $fileCollection = new FileCollection([$first, $second]);

        $this->assertSame(['first.txt', 'second.txt'], $fileCollection->keys());
        $this->assertSame([$first, $second], $fileCollection->values());
        $this->assertSame($first, $fileCollection->get('first.txt'));
        $this->assertSame($first, $fileCollection->first());
        $this->assertSame($second, $fileCollection->last());
    }

    public function testAssociativeInputUsesItsExplicitKeys(): void
    {
        $file = new File('/files/original.txt');
        $fileCollection = new FileCollection(['alias' => $file]);

        $this->assertSame($file, $fileCollection->get('alias'));
        $this->assertTrue($fileCollection->has('alias'));
        $this->assertFalse($fileCollection->has('original.txt'));
        $this->assertSame(['alias'], $fileCollection->keys());
    }

    public function testEmptyCollectionHasNoItems(): void
    {
        $fileCollection = new FileCollection();

        $this->assertTrue($fileCollection->isEmpty());
        $this->assertSame(0, $fileCollection->count());
        $this->assertNull($fileCollection->first());
        $this->assertSame([], $fileCollection->toArray());
    }

    public function testCollectionRejectsNonFileItems(): void
    {
        $typeError = $this->captureException(TypeError::class, fn() => new FileCollection($this->unchecked(['not a File'])));

        $this->assertInstanceOf(TypeError::class, $typeError);
    }

    public function testFilesWithTheSameNameInDifferentDirectoriesKeepOnlyTheLastOne(): void
    {
        $first = new File('/files/a/report.txt');
        $second = new File('/files/b/report.txt');

        $fileCollection = new FileCollection([$first, $second]);

        $this->assertSame(['report.txt'], $fileCollection->keys());
        $this->assertSame($second, $fileCollection->get('report.txt'));
    }

    public function testFilteringReturnsAFileCollectionAndRetainsFileIdentity(): void
    {
        $first = new File('/files/first.txt');
        $second = new File('/files/second.jpg');
        $fileCollection = new FileCollection([$first, $second]);

        $filtered = $fileCollection->filter(fn(File $file) => $file->extension() === 'jpg');

        $this->assertInstanceOf(FileCollection::class, $filtered);
        $this->assertSame(['second.jpg' => $second], $filtered->toArray());
        $this->assertSame(['first.txt' => $first, 'second.jpg' => $second], $fileCollection->toArray());
    }

    /**
     * @param Closure(FileCollection): mixed $operation
     */
    #[DataProvider('mutatingOperationProvider')]
    public function testImmutableCollectionRejectsMutatingOperations(Closure $operation): void
    {
        $fileCollection = new FileCollection([new File('/files/one.txt')]);

        try {
            $operation($fileCollection);
            $this->fail('The immutable file collection was mutated.');
        } catch (LogicException) {
            $this->assertSame(['one.txt'], $fileCollection->keys());
        }
    }

    /**
     * @return iterable<string, array{Closure(FileCollection): mixed}>
     */
    public static function mutatingOperationProvider(): iterable
    {
        yield 'set' => [static fn(FileCollection $fileCollection) => $fileCollection->set('two.txt', new File('/files/two.txt'))];
        yield 'remove' => [static fn(FileCollection $fileCollection) => $fileCollection->remove('one.txt')];
        yield 'merge' => [static fn(FileCollection $fileCollection) => $fileCollection->merge(new FileCollection([new File('/files/two.txt')]))];
    }
}
