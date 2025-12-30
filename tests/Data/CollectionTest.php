<?php

namespace Formwork\Tests\Data;

use Formwork\Data\AbstractCollection;
use Formwork\Data\Collection;
use Formwork\Data\CollectionDataProxy;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Collection::class)]
final class CollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $collection = new Collection(['item1', 'item2', 'item3']);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(AbstractCollection::class, $collection);
        $this->assertCount(3, $collection);
    }

    public function testToMutable(): void
    {
        $immutableCollection = Collection::from(['itemA', 'itemB'], mutable: false);
        $mutableCollection = $immutableCollection->toMutable();

        $this->assertInstanceOf(Collection::class, $mutableCollection);
        $this->assertNotSame($immutableCollection, $mutableCollection);
        $this->assertTrue($mutableCollection->isMutable());
        $this->assertSame(['itemA', 'itemB'], $mutableCollection->values());
    }

    public function testToMutableThrowsOnAlreadyMutable(): void
    {
        $mutableCollection = Collection::from(['itemA', 'itemB'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot convert an already mutable collection to mutable');
        $mutableCollection->toMutable();
    }

    public function testToImmutable(): void
    {
        $mutableCollection = Collection::from(['itemA', 'itemB'], mutable: true);
        $immutableCollection = $mutableCollection->toImmutable();

        $this->assertInstanceOf(Collection::class, $immutableCollection);
        $this->assertNotSame($mutableCollection, $immutableCollection);
        $this->assertFalse($immutableCollection->isMutable());
        $this->assertSame(['itemA', 'itemB'], $immutableCollection->values());
    }

    public function testToImmutableThrowsOnAlreadyImmutable(): void
    {
        $immutableCollection = Collection::from(['itemA', 'itemB'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot convert an already immutable collection to immutable');
        $immutableCollection->toImmutable();
    }

    public function testOfThrowsOnAssociativityMismatch(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Associative collections cannot be created from non-associative data');
        Collection::of('string', ['item1', 'item2'], associative: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Non-associative collections cannot be created from associative data');
        Collection::of('string', ['key1' => 'item1', 'key2' => 'item2'], associative: false);
    }

    public function testOfThrowsOnTypeMismatch(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');
        Collection::of('string', ['item1', 2, 'item3']);
    }

    public function testFromWithMatchingTypes(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3']);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertTrue($collection->isTyped());
        $this->assertSame('string', $collection->dataType());
    }

    public function testFromWithMismatchedTypes(): void
    {
        $collection = Collection::from(['item1', 2, 'item3']);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertFalse($collection->isTyped());
    }

    public function testFromThrowsOnStrictTypeMismatch(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot create a typed collection with data of different types');
        Collection::from(['item1', 2, 'item3'], typed: true);
    }

    public function testIsAssociative(): void
    {
        $associativeCollection = Collection::from(['key1' => 'value1', 'key2' => 'value2']);
        $indexedCollection = Collection::from(['value1', 'value2', 'value3']);

        $this->assertTrue($associativeCollection->isAssociative());
        $this->assertFalse($indexedCollection->isAssociative());
    }

    public function testIsMutable(): void
    {
        $mutableCollection = Collection::from([], mutable: true);
        $immutableCollection = Collection::from([], mutable: false);

        $this->assertTrue($mutableCollection->isMutable());
        $this->assertFalse($immutableCollection->isMutable());
    }

    public function testIsTyped(): void
    {
        $typedCollection = Collection::of('string');
        $untypedCollection = Collection::from([]);

        $this->assertTrue($typedCollection->isTyped());
        $this->assertFalse($untypedCollection->isTyped());
    }

    public function testDataType(): void
    {
        $arrayCollection = Collection::of('array');
        $intCollection = Collection::of('int');

        $this->assertSame('array', $arrayCollection->dataType());
        $this->assertSame('int', $intCollection->dataType());
    }

    public function testIsEmpty(): void
    {
        $emptyCollection = Collection::from([]);
        $nonEmptyCollection = Collection::from(['item1']);

        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($nonEmptyCollection->isEmpty());
    }

    public function testNth(): void
    {
        $collection = Collection::from(['first', 'second', 'third', 'fourth']);

        $this->assertSame('first', $collection->nth(0));
        $this->assertSame('second', $collection->nth(1));
        $this->assertSame('third', $collection->nth(2));
        $this->assertSame('fourth', $collection->nth(3));
    }

    public function testAt(): void
    {
        $collection = Collection::from(['apple', 'banana', 'cherry']);

        // Positive indices
        $this->assertSame('apple', $collection->at(0));
        $this->assertSame('banana', $collection->at(1));
        $this->assertSame('cherry', $collection->at(2));

        // Negative indices
        $this->assertSame('cherry', $collection->at(-1));
        $this->assertSame('banana', $collection->at(-2));
        $this->assertSame('apple', $collection->at(-3));
    }

    public function testFirst(): void
    {
        $collection = Collection::from(['alpha', 'beta', 'gamma']);

        $this->assertSame('alpha', $collection->first());
    }

    public function testLast(): void
    {
        $collection = Collection::from(['alpha', 'beta', 'gamma']);

        $this->assertSame('gamma', $collection->last());
    }

    public function testRandom(): void
    {
        $data = ['red', 'green', 'blue'];
        $collection = Collection::from($data);

        $randomItem = $collection->random();
        $this->assertContains($randomItem, $data);
    }

    public function testIndexOf(): void
    {
        $collection = Collection::from(['cat', 'dog', 'fish']);

        $this->assertSame(0, $collection->indexOf('cat'));
        $this->assertSame(1, $collection->indexOf('dog'));
        $this->assertSame(2, $collection->indexOf('fish'));
        $this->assertNull($collection->indexOf('bird'));
    }

    public function testKeyOf(): void
    {
        $collection = Collection::from(['a' => 'apple', 'b' => 'banana', 'c' => 'cherry']);

        $this->assertSame('a', $collection->keyOf('apple'));
        $this->assertSame('b', $collection->keyOf('banana'));
        $this->assertSame('c', $collection->keyOf('cherry'));
        $this->assertNull($collection->keyOf('date'));
    }

    public function testKeyOfThrowsOnNonAssociative(): void
    {
        $collection = Collection::from(['apple', 'banana', 'cherry']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only associative collections support keys');
        $collection->keyOf('banana');
    }

    public function testKeys(): void
    {
        $collection = Collection::from(['x' => 10, 'y' => 20, 'z' => 30]);

        $this->assertSame(['x', 'y', 'z'], $collection->keys());
    }

    public function testKeysThrowsOnNonAssociative(): void
    {
        $collection = Collection::from([10, 20, 30]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only associative collections support keys');
        $collection->keys();
    }

    public function testValues(): void
    {
        $associativeCollection = Collection::from(['a' => 'apple', 'b' => 'banana', 'c' => 'cherry']);
        $indexedCollection = Collection::from(['apple', 'banana', 'cherry']);

        $this->assertSame(['apple', 'banana', 'cherry'], $associativeCollection->values());
        $this->assertSame(['apple', 'banana', 'cherry'], $indexedCollection->values());
    }

    public function testContains(): void
    {
        $associativeCollection = Collection::from(['key1' => 'value1', 'key2' => 'value2']);
        $indexedCollection = Collection::from(['value1', 'value2', 'value3']);

        $this->assertTrue($associativeCollection->contains('value1'));
        $this->assertFalse($associativeCollection->contains('value3'));

        $this->assertTrue($indexedCollection->contains('value2'));
        $this->assertFalse($indexedCollection->contains('value4'));
    }

    public function testEvery(): void
    {
        $data = [2, 4, 6, 8];
        $collection = Collection::from($data);

        $this->assertTrue($collection->every(fn($item) => $item % 2 === 0));
        $this->assertFalse($collection->every(fn($item) => $item > 4));
    }

    public function testSome(): void
    {
        $data = [1, 3, 5, 7];
        $collection = Collection::from($data);

        $this->assertTrue($collection->some(fn($item) => $item === 3));
        $this->assertFalse($collection->some(fn($item) => $item % 2 === 0));
    }

    public function testFind(): void
    {
        $associativeCollection = Collection::from(['a' => 10, 'b' => 15, 'c' => 20]);
        $indexedCollection = Collection::from([10, 15, 20]);

        $this->assertSame(15, $associativeCollection->find(fn($item) => $item > 12));
        $this->assertNull($associativeCollection->find(fn($item) => $item > 25));

        $this->assertSame(15, $indexedCollection->find(fn($item) => $item > 12));
        $this->assertNull($indexedCollection->find(fn($item) => $item > 25));
    }

    public function testClone(): void
    {
        $collection = Collection::from(['itemA', 'itemB', 'itemC']);
        $clonedCollection = $collection->clone();

        $this->assertInstanceOf(Collection::class, $clonedCollection);
        $this->assertNotSame($collection, $clonedCollection);
        $this->assertEquals($collection, $clonedCollection);
    }

    public function testDeepClone(): void
    {
        $data = [
            new Collection(['a', 'b']),
            new Collection(['c', 'd']),
        ];

        $collection = Collection::from($data);
        $deepClonedCollection = $collection->deepClone();

        $this->assertInstanceOf(Collection::class, $deepClonedCollection);
        $this->assertNotSame($collection, $deepClonedCollection);
        $this->assertEquals($collection, $deepClonedCollection);

        foreach ($deepClonedCollection->values() as $index => $item) {
            $this->assertNotSame($collection->at($index), $item);
            $this->assertEquals($collection->at($index), $item);
        }
    }

    public function testReverse(): void
    {
        $collection = Collection::from(['first', 'second', 'third']);
        $reversedCollection = $collection->reverse();

        $this->assertInstanceOf(Collection::class, $reversedCollection);
        $this->assertNotSame($collection, $reversedCollection);
        $this->assertSame(['third', 'second', 'first'], $reversedCollection->values());
    }

    public function testShuffle(): void
    {
        $collection = Collection::from(['one', 'two', 'three', 'four', 'five']);
        $shuffledCollection = $collection->shuffle();

        $this->assertInstanceOf(Collection::class, $shuffledCollection);
        $this->assertNotSame($collection, $shuffledCollection);
        $this->assertCount(5, $shuffledCollection);
        $this->assertNotSame($collection->values(), $shuffledCollection->values());

        foreach (['one', 'two', 'three', 'four', 'five'] as $item) {
            $this->assertTrue($shuffledCollection->contains($item));
        }
    }

    public function testUnique(): void
    {
        $collection = Collection::from(['apple', 'banana', 'apple', 'orange', 'banana']);
        $uniqueCollection = $collection->unique();

        $this->assertInstanceOf(Collection::class, $uniqueCollection);
        $this->assertNotSame($collection, $uniqueCollection);
        $this->assertSame(['apple', 'banana', 'orange'], $uniqueCollection->values());
    }

    public function testDuplicates(): void
    {
        $collection = Collection::from(['apple', 'banana', 'apple', 'orange', 'banana', 'banana']);
        $duplicatesCollection = $collection->duplicates();

        $this->assertInstanceOf(Collection::class, $duplicatesCollection);
        $this->assertNotSame($collection, $duplicatesCollection);
        $this->assertSame(['apple', 'banana', 'banana'], $duplicatesCollection->values());
    }

    public function testSlice(): void
    {
        $collection = Collection::from(['a', 'b', 'c', 'd', 'e']);

        $slicedCollection = $collection->slice(1, 3);
        $this->assertInstanceOf(Collection::class, $slicedCollection);
        $this->assertNotSame($collection, $slicedCollection);
        $this->assertSame(['b', 'c', 'd'], $slicedCollection->values());
    }

    public function testLimit(): void
    {
        $collection = Collection::from(['x', 'y', 'z', 'w']);

        $limitedCollection = $collection->limit(2);
        $this->assertInstanceOf(Collection::class, $limitedCollection);
        $this->assertNotSame($collection, $limitedCollection);
        $this->assertSame(['x', 'y'], $limitedCollection->values());
    }

    public function testEach(): void
    {
        $collection = Collection::from(['red', 'green', 'blue']);

        $collectedItems = [];
        $collection->each(function ($item) use (&$collectedItems) {
            $collectedItems[] = $item;
        });

        $this->assertSame(['red', 'green', 'blue'], $collectedItems);
    }

    public function testEachWithEarlyReturn(): void
    {
        $collection = Collection::from([1, 2, 3, 4, 5]);

        $collectedItems = [];
        $collection->each(function ($item) use (&$collectedItems) {
            $collectedItems[] = $item;
            if ($item >= 3) {
                return false;
            }
            return true;
        });

        $this->assertSame([1, 2, 3], $collectedItems);
    }

    public function testMap(): void
    {
        $collection = Collection::from([1, 2, 3]);

        $mappedCollection = $collection->map(fn($item) => $item * 2);

        $this->assertInstanceOf(Collection::class, $mappedCollection);
        $this->assertNotSame($collection, $mappedCollection);
        $this->assertSame([2, 4, 6], $mappedCollection->values());
    }

    public function testFilter(): void
    {
        $data = [1, 2, 3, 4, 5];
        $collection = Collection::from($data);

        $filteredCollection = $collection->filter(fn($item) => $item % 2 === 0);

        $this->assertInstanceOf(Collection::class, $filteredCollection);
        $this->assertNotSame($collection, $filteredCollection);
        $this->assertSame([2, 4], $filteredCollection->values());
    }

    public function testReject(): void
    {
        $data = [1, 2, 3, 4, 5];
        $collection = Collection::from($data);

        $rejectedCollection = $collection->reject(fn($item) => $item % 2 === 0);

        $this->assertInstanceOf(Collection::class, $rejectedCollection);
        $this->assertNotSame($collection, $rejectedCollection);
        $this->assertSame([1, 3, 5], $rejectedCollection->values());
    }

    public function testSort(): void
    {
        $collection = Collection::from([3, 1, 4, 2]);

        $sortedCollection = $collection->sort();

        $this->assertInstanceOf(Collection::class, $sortedCollection);
        $this->assertNotSame($collection, $sortedCollection);
        $this->assertSame([1, 2, 3, 4], $sortedCollection->values());
    }

    public function testGroup(): void
    {
        $collection = Collection::from(['apple', 'apricot', 'banana', 'blueberry', 'cherry']);

        $grouped = $collection->group(fn($item) => $item[0]);

        $this->assertIsArray($grouped);
        $this->assertCount(3, $grouped);
        $this->assertSame(['apple', 'apricot'], $grouped['a']);
        $this->assertSame(['banana', 'blueberry'], $grouped['b']);
        $this->assertSame(['cherry'], $grouped['c']);
    }

    public function testExtract(): void
    {
        $collection = Collection::from([
            'alice'   => ['name' => 'Alice', 'age' => 30],
            'bob'     => ['name' => 'Bob', 'age' => 25],
            'charlie' => ['name' => 'Charlie', 'age' => 35],
        ]);

        $names = $collection->extract('name');
        $ages = $collection->extract('age');

        $this->assertSame(['alice' => 'Alice', 'bob' => 'Bob', 'charlie' => 'Charlie'], $names);
        $this->assertSame(['alice' => 30, 'bob' => 25, 'charlie' => 35], $ages);
    }

    public function testPluck(): void
    {
        $collection = Collection::from([
            'alice'   => ['name' => 'Alice', 'age' => 30],
            'bob'     => ['name' => 'Bob', 'age' => 25],
            'charlie' => ['name' => 'Charlie', 'age' => 35],
        ]);

        $names = $collection->pluck('name');
        $ages = $collection->pluck('age');

        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names);
        $this->assertSame([30, 25, 35], $ages);
    }

    public function testFlatten(): void
    {
        $collection = Collection::from([
            ['a', 'b'],
            ['c', 'd'],
            ['e', 'f'],
        ]);

        $flattenedCollection = $collection->flatten();

        $this->assertInstanceOf(Collection::class, $flattenedCollection);
        $this->assertNotSame($collection, $flattenedCollection);
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $flattenedCollection->values());
    }

    public function testFilterByWithCallback(): void
    {
        $collection = Collection::from([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
            ['name' => 'Charlie', 'age' => 35],
        ]);

        $filteredCollection = $collection->filterBy('age', fn($age) => $age > 30);

        $this->assertInstanceOf(Collection::class, $filteredCollection);
        $this->assertNotSame($collection, $filteredCollection);
        $this->assertSame([['name' => 'Charlie', 'age' => 35]], $filteredCollection->values());
    }

    public function testFilterByWithDefaultFilters(): void
    {
        $filters = [
            '==',
            'equalTo',
            '!=',
            'notEqualTo',
            '===',
            'strictlyEqualTo',
            '!==',
            'strictlyNotEqualTo',
            '>',
            'greaterThan',
            '>=',
            'greaterThanOrEqualTo',
            '<',
            'lessThan',
            '<=',
            'lessThanOrEqualTo',
        ];

        $data = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $collection = Collection::from($data);

        foreach ($filters as $filter) {
            $expected = match ($filter) {
                '==', 'equalTo' => [['value' => 20]],
                '!=', 'notEqualTo' => [['value' => 10], ['value' => 30]],
                '===', 'strictlyEqualTo' => [['value' => 20]],
                '!==', 'strictlyNotEqualTo' => [['value' => 10], ['value' => 30]],
                '>', 'greaterThan' => [['value' => 30]],
                '>=', 'greaterThanOrEqualTo' => [['value' => 20], ['value' => 30]],
                '<', 'lessThan' => [['value' => 10]],
                '<=', 'lessThanOrEqualTo' => [['value' => 10], ['value' => 20]],
            };

            $filteredCollection = $collection->filterBy('value', $filter, 20);

            $this->assertInstanceOf(Collection::class, $filteredCollection);
            $this->assertNotSame($collection, $filteredCollection);
            $this->assertSame($expected, $filteredCollection->values());
        }
    }

    public function testFilterByThrowsOnInvalidFilter(): void
    {
        $collection = Collection::from([
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown filter "invalidComparison"');
        $collection->filterBy('value', 'invalidComparison', 20);
    }

    public function testFilterByThrowsOnCallbackWithThirdArgument(): void
    {
        $collection = Collection::from([
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unexpected third argument passed');
        $collection->filterBy('value', fn($value) => $value > 15, 20);
    }

    public function testSortBy(): void
    {
        $collection = Collection::from([
            ['name' => 'Charlie', 'age' => 35],
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]);

        $sortedByName = $collection->sortBy('name');
        $sortedByAge = $collection->sortBy('age');

        $this->assertInstanceOf(Collection::class, $sortedByName);
        $this->assertNotSame($collection, $sortedByName);
        $this->assertSame([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
            ['name' => 'Charlie', 'age' => 35],
        ], $sortedByName->values());

        $this->assertInstanceOf(Collection::class, $sortedByAge);
        $this->assertNotSame($collection, $sortedByAge);
        $this->assertSame([
            ['name' => 'Bob', 'age' => 25],
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Charlie', 'age' => 35],
        ], $sortedByAge->values());
    }

    public function testGroupBy(): void
    {
        $collection = Collection::from([
            ['name' => 'Alice', 'department' => 'HR'],
            ['name' => 'Bob', 'department' => 'IT'],
            ['name' => 'Charlie', 'department' => 'HR'],
            ['name' => 'David', 'department' => 'IT'],
            ['name' => 'Eve', 'department' => 'Finance'],
        ]);

        $grouped = $collection->groupBy('department');

        $this->assertIsArray($grouped);
        $this->assertCount(3, $grouped);
        $this->assertSame([
            ['name' => 'Alice', 'department' => 'HR'],
            ['name' => 'Charlie', 'department' => 'HR'],
        ], $grouped['HR']);
        $this->assertSame([
            ['name' => 'Bob', 'department' => 'IT'],
            ['name' => 'David', 'department' => 'IT'],
        ], $grouped['IT']);
        $this->assertSame([
            ['name' => 'Eve', 'department' => 'Finance'],
        ], $grouped['Finance']);
    }

    public function testKeyBy(): void
    {
        $collection = Collection::from([
            ['id' => 'a1', 'name' => 'Alice'],
            ['id' => 'b2', 'name' => 'Bob'],
            ['id' => 'c3', 'name' => 'Charlie'],
        ]);

        $keyedCollection = $collection->keyBy('id');

        $this->assertInstanceOf(Collection::class, $keyedCollection);
        $this->assertNotSame($collection, $keyedCollection);
        $this->assertSame([
            'a1' => ['id' => 'a1', 'name' => 'Alice'],
            'b2' => ['id' => 'b2', 'name' => 'Bob'],
            'c3' => ['id' => 'c3', 'name' => 'Charlie'],
        ], $keyedCollection->toArray());
    }

    public function testWith(): void
    {
        $data = ['item1', 'item2'];
        $collection = Collection::from($data);

        $newCollection = $collection->with('item3');

        $this->assertInstanceOf(Collection::class, $newCollection);
        $this->assertNotSame($collection, $newCollection);
        $this->assertSame(['item1', 'item2', 'item3'], $newCollection->values());
    }

    public function testWithSkipsExistingItems(): void
    {
        $collection = Collection::from(['item1', 'item2']);

        $newCollection = $collection->with('item2');

        $this->assertInstanceOf(Collection::class, $newCollection);
        $this->assertNotSame($collection, $newCollection);
        $this->assertSame(['item1', 'item2'], $newCollection->values());
    }

    public function testWithout(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3']);

        $newCollection = $collection->without('item2');

        $this->assertInstanceOf(Collection::class, $newCollection);
        $this->assertNotSame($collection, $newCollection);
        $this->assertSame(['item1', 'item3'], $newCollection->values());
    }

    public function testEveryItem(): void
    {
        $collection = Collection::from(['itemA', 'itemB', 'itemC']);

        $dataProxy = $collection->everyItem();

        $this->assertInstanceOf(CollectionDataProxy::class, $dataProxy);
    }

    public function testUnion(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2']);
        $collection2 = Collection::from(['b' => 'item2', 'c' => 'item3']);

        $unionCollection = $collection1->union($collection2);

        $this->assertInstanceOf(Collection::class, $unionCollection);
        $this->assertNotSame($collection1, $unionCollection);
        $this->assertSame(['a' => 'item1', 'b' => 'item2', 'c' => 'item3'], $unionCollection->toArray());
    }

    public function testIntersection(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2', 'c' => 'item3']);
        $collection2 = Collection::from(['b' => 'item2', 'c' => 'item4', 'd' => 'item3']);

        $intersectionCollection = $collection1->intersection($collection2);

        $this->assertInstanceOf(Collection::class, $intersectionCollection);
        $this->assertNotSame($collection1, $intersectionCollection);
        $this->assertSame(['b' => 'item2', 'c' => 'item3'], $intersectionCollection->toArray());
    }

    public function testDifference(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2', 'c' => 'item3']);
        $collection2 = Collection::from(['b' => 'item2', 'c' => 'item4']);

        $differenceCollection = $collection1->difference($collection2);

        $this->assertInstanceOf(Collection::class, $differenceCollection);
        $this->assertNotSame($collection1, $differenceCollection);
        $this->assertSame(['a' => 'item1', 'c' => 'item3'], $differenceCollection->toArray());
    }

    public function testAdd(): void
    {
        $collection = Collection::from(['item1', 'item2'], mutable: true);

        $collection->add('item3');

        $this->assertCount(3, $collection);
        $this->assertSame(['item1', 'item2', 'item3'], $collection->values());
    }

    public function testAddThrowsOnImmutable(): void
    {
        $collection = Collection::from(['item1', 'item2'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be added only to mutable and non-associative collections');
        $collection->add('item3');
    }

    public function testAddThrowsOnAssociative(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be added only to mutable and non-associative collections');
        $collection->add('value3');
    }

    public function testAddThrowsOnTypeMismatch(): void
    {
        $collection = Collection::of('int', mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type int to be added, string given');
        $collection->add('notAnInteger');
    }

    public function testAddMultiple(): void
    {
        $collection = Collection::from(['item1'], mutable: true);

        $collection->addMultiple(['item2', 'item3']);

        $this->assertCount(3, $collection);
        $this->assertSame(['item1', 'item2', 'item3'], $collection->values());
    }

    public function testPull(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3'], mutable: true);

        $collection->pull('item2');

        $this->assertSame(['item1', 'item3'], $collection->values());
    }

    public function testPullThrowsOnImmutable(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be pulled only from mutable and non-associative collections');
        $collection->pull('item2');
    }

    public function testPullThrowsOnAssociative(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be pulled only from mutable and non-associative collections');
        $collection->pull('value1');
    }

    public function testPullMultiple(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3', 'item4'], mutable: true);

        $collection->pullMultiple(['item2', 'item4']);

        $this->assertSame(['item1', 'item3'], $collection->values());
    }

    public function testMoveItem(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3'], mutable: true);

        $collection->moveItem(0, 2);

        $this->assertSame(['item2', 'item3', 'item1'], $collection->values());
    }

    public function testMoveItemInAssociative(): void
    {
        $collection = Collection::from(['a' => 'item1', 'b' => 'item2', 'c' => 'item3'], mutable: true);

        $collection->moveItem(0, 2);

        $this->assertSame(['b' => 'item2', 'c' => 'item3', 'a' => 'item1'], $collection->toArray());
    }

    public function testHas(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertTrue($collection->has('key1'));
        $this->assertFalse($collection->has('key3'));
    }

    public function testHasThrowsOnNonAssociative(): void
    {
        $collection = Collection::from(['value1', 'value2', 'value3']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value presence can be checked only in associative collections');
        $collection->has('key1');
    }

    public function testGet(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertSame('value1', $collection->get('key1'));
        $this->assertSame('value2', $collection->get('key2'));
        $this->assertNull($collection->get('key3'));
        $this->assertSame('default', $collection->get('key3', 'default'));
    }

    public function testGetThrowsOnNonAssociative(): void
    {
        $collection = Collection::from(['value1', 'value2', 'value3']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be get only from associative collections');
        $collection->get('key1');
    }

    public function testSet(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: true);

        $collection->set('key2', 'newValue2');
        $collection->set('key3', 'value3');

        $this->assertSame(['key1' => 'value1', 'key2' => 'newValue2', 'key3' => 'value3'], $collection->toArray());
    }

    public function testSetThrowsOnNonAssociative(): void
    {
        $collection = Collection::from(['value1', 'value2', 'value3'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be set only to associative and mutable collections');
        $collection->set('key1', 'newValue1');
    }

    public function testSetThrowsOnImmutable(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be set only to associative and mutable collections');
        $collection->set('key2', 'newValue2');
    }

    public function testSetThrowsOnTypeMismatch(): void
    {
        $collection = Collection::of('int', associative: true, mutable: true);
        $collection->set('key1', 10);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type int, string given');
        $collection->set('key2', 'notAnInteger');
    }

    public function testRemove(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'], mutable: true);

        $collection->remove('key2');

        $this->assertSame(['key1' => 'value1', 'key3' => 'value3'], $collection->toArray());
    }

    public function testRemoveThrowsOnNonAssociative(): void
    {
        $collection = Collection::from(['value1', 'value2', 'value3'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be removed only from associative and mutable collections');
        $collection->remove('key1');
    }

    public function testRemoveThrowsOnImmutable(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be removed only from associative and mutable collections');
        $collection->remove('key2');
    }

    public function testMerge(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2'], mutable: true);
        $collection2 = Collection::from(['b' => 'item3', 'c' => 'item4']);

        $collection1->merge($collection2);

        $this->assertSame(['a' => 'item1', 'b' => 'item3', 'c' => 'item4'], $collection1->toArray());
    }

    public function testMergeThrowsOnImmutable(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2'], mutable: false);
        $collection2 = Collection::from(['b' => 'item3', 'c' => 'item4']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be merged only into mutable collections');
        $collection1->merge($collection2);
    }

    public function testMergeThrowsOnAssociativityMismatch(): void
    {
        $collection1 = Collection::from(['item1', 'item2'], mutable: true);
        $collection2 = Collection::from(['b' => 'item3', 'c' => 'item4']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Collections cannot be merged if their associativeness is different');
        $collection1->merge($collection2);
    }

    public function testMergeThrowsOnTypeMismatch(): void
    {
        $collection1 = Collection::of('int', mutable: true);
        $collection1->add(1);
        $collection1->add(2);

        $collection2 = Collection::of('string', mutable: true);
        $collection2->add('three');
        $collection2->add('four');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Collections with data of different types cannot be merged');
        $collection1->merge($collection2);
    }
}
