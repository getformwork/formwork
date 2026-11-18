<?php

namespace Formwork\Tests\Unit\Data;

use Closure;
use Formwork\Data\Collection;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Utils\Fixtures\StringableFixture;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(Collection::class)]
final class CollectionTest extends TestCase
{
    public function testItemsArePassedToTheConstructor(): void
    {
        $collection = new Collection(['item1', 'item2', 'item3']);

        $this->assertCount(3, $collection);
    }

    public function testImmutableCollectionsCanBeCopiedAsMutable(): void
    {
        $immutableCollection = Collection::from(['itemA', 'itemB'], mutable: false);
        $mutableCollection = $immutableCollection->toMutable();

        $this->assertNotSame($immutableCollection, $mutableCollection);
        $this->assertTrue($mutableCollection->isMutable());
        $this->assertSame(['itemA', 'itemB'], $mutableCollection->values());
    }

    public function testToMutableRejectsAlreadyMutableCollections(): void
    {
        $mutableCollection = Collection::from(['itemA', 'itemB'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot convert an already mutable collection to mutable');
        $mutableCollection->toMutable();
    }

    public function testMutableCollectionsCanBeCopiedAsImmutable(): void
    {
        $mutableCollection = Collection::from(['itemA', 'itemB'], mutable: true);
        $immutableCollection = $mutableCollection->toImmutable();

        $this->assertNotSame($mutableCollection, $immutableCollection);
        $this->assertFalse($immutableCollection->isMutable());
        $this->assertSame(['itemA', 'itemB'], $immutableCollection->values());
    }

    public function testToImmutableRejectsAlreadyImmutableCollections(): void
    {
        $immutableCollection = Collection::from(['itemA', 'itemB'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot convert an already immutable collection to immutable');
        $immutableCollection->toImmutable();
    }

    public function testOfRejectsMismatchingTypes(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');
        Collection::of('string', ['item1', 2, 'item3']);
    }

    public function testOfRejectsAssociativeCollectionsFromLists(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Associative collections cannot be created from non-associative data');
        Collection::of('string', ['item1', 'item2'], associative: true);
    }

    public function testOfRejectsNonAssociativeCollectionsFromAssociativeData(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Non-associative collections cannot be created from associative data');
        Collection::of('string', ['key1' => 'item1', 'key2' => 'item2'], associative: false);
    }

    public function testFromWithMatchingTypes(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3']);

        $this->assertTrue($collection->isTyped());
        $this->assertSame('string', $collection->dataType());
    }

    public function testFromWithMismatchedTypes(): void
    {
        $collection = Collection::from(['item1', 2, 'item3']);

        $this->assertFalse($collection->isTyped());
    }

    public function testFromRejectsMismatchingTypesInStrictMode(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot create a typed collection with data of different types');
        Collection::from(['item1', 2, 'item3'], typed: true);
    }

    public function testIsAssociativeDependsOnTheKeys(): void
    {
        $associativeCollection = Collection::from(['key1' => 'value1', 'key2' => 'value2']);
        $indexedCollection = Collection::from(['value1', 'value2', 'value3']);

        $this->assertTrue($associativeCollection->isAssociative());
        $this->assertFalse($indexedCollection->isAssociative());
    }

    public function testIsMutableDependsOnTheCreation(): void
    {
        $mutableCollection = Collection::from([], mutable: true);
        $immutableCollection = Collection::from([], mutable: false);

        $this->assertTrue($mutableCollection->isMutable());
        $this->assertFalse($immutableCollection->isMutable());
    }

    public function testIsTypedIsTrueOnlyForTypedCollections(): void
    {
        $typedCollection = Collection::of('string');
        $untypedCollection = Collection::from([]);

        $this->assertTrue($typedCollection->isTyped());
        $this->assertFalse($untypedCollection->isTyped());
    }

    public function testDataTypeIsTheOneGivenToOf(): void
    {
        $arrayCollection = Collection::of('array');
        $intCollection = Collection::of('int');

        $this->assertSame('array', $arrayCollection->dataType());
        $this->assertSame('int', $intCollection->dataType());
    }

    public function testIsEmptyIsTrueOnlyWithoutItems(): void
    {
        $emptyCollection = Collection::from([]);
        $nonEmptyCollection = Collection::from(['item1']);

        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($nonEmptyCollection->isEmpty());
    }

    public function testNthReturnsTheItemAtThePosition(): void
    {
        $collection = Collection::from(['first', 'second', 'third', 'fourth']);

        $this->assertSame('first', $collection->nth(0));
        $this->assertSame('second', $collection->nth(1));
        $this->assertSame('third', $collection->nth(2));
        $this->assertSame('fourth', $collection->nth(3));
    }

    public function testAtCountsNegativePositionsFromTheEnd(): void
    {
        $collection = Collection::from(['first', 'second', 'third']);

        // Positive indices
        $this->assertSame('first', $collection->at(0));
        $this->assertSame('second', $collection->at(1));
        $this->assertSame('third', $collection->at(2));

        // Negative indices
        $this->assertSame('third', $collection->at(-1));
        $this->assertSame('second', $collection->at(-2));
        $this->assertSame('first', $collection->at(-3));
    }

    public function testFirstReturnsTheFirstItem(): void
    {
        $collection = Collection::from(['alpha', 'beta', 'gamma']);

        $this->assertSame('alpha', $collection->first());
    }

    public function testLastReturnsTheLastItem(): void
    {
        $collection = Collection::from(['alpha', 'beta', 'gamma']);

        $this->assertSame('gamma', $collection->last());
    }

    public function testRandomReturnsAnItemOfTheCollection(): void
    {
        $data = ['red', 'green', 'blue'];
        $collection = Collection::from($data);

        $this->assertSameSize($data, $collection);

        $randomItem = $collection->random();
        $this->assertContains($randomItem, $data);
    }

    public function testIndexOfReturnsThePositionOfAValue(): void
    {
        $collection = Collection::from(['cat', 'dog', 'fish']);

        $this->assertSame(0, $collection->indexOf('cat'));
        $this->assertSame(1, $collection->indexOf('dog'));
        $this->assertSame(2, $collection->indexOf('fish'));
        $this->assertNull($collection->indexOf('bird'));
    }

    public function testKeyOfReturnsTheKeyOfAValue(): void
    {
        $collection = Collection::from(['a' => 'first', 'b' => 'second', 'c' => 'third']);

        $this->assertSame('a', $collection->keyOf('first'));
        $this->assertSame('b', $collection->keyOf('second'));
        $this->assertSame('c', $collection->keyOf('third'));
        $this->assertNull($collection->keyOf('date'));
    }

    public function testKeyOfRejectsNonAssociativeCollections(): void
    {
        $collection = Collection::from(['first', 'second', 'third']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only associative collections support keys');
        $collection->keyOf('second');
    }

    public function testKeysAreReturnedInOrder(): void
    {
        $collection = Collection::from(['x' => 10, 'y' => 20, 'z' => 30]);

        $this->assertSame(['x', 'y', 'z'], $collection->keys());
    }

    public function testKeysRejectsNonAssociativeCollections(): void
    {
        $collection = Collection::from([10, 20, 30]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only associative collections support keys');
        $collection->keys();
    }

    public function testValuesAreReturnedAsAList(): void
    {
        $associativeCollection = Collection::from(['a' => 'first', 'b' => 'second', 'c' => 'third']);
        $indexedCollection = Collection::from(['first', 'second', 'third']);

        $this->assertSame(['first', 'second', 'third'], $associativeCollection->values());
        $this->assertSame(['first', 'second', 'third'], $indexedCollection->values());
    }

    public function testContainsChecksTheValues(): void
    {
        $associativeCollection = Collection::from(['key1' => 'value1', 'key2' => 'value2']);
        $indexedCollection = Collection::from(['value1', 'value2', 'value3']);

        $this->assertTrue($associativeCollection->contains('value1'));
        $this->assertFalse($associativeCollection->contains('value3'));

        $this->assertTrue($indexedCollection->contains('value2'));
        $this->assertFalse($indexedCollection->contains('value4'));
    }

    public function testEveryChecksThatAllItemsMatch(): void
    {
        $data = [2, 4, 6, 8];
        $collection = Collection::from($data);

        $this->assertTrue($collection->every(fn($item) => $item % 2 === 0));
        $this->assertFalse($collection->every(fn($item) => $item > 4));
    }

    public function testSomeChecksThatAnyItemMatches(): void
    {
        $data = [1, 3, 5, 7];
        $collection = Collection::from($data);

        $this->assertTrue($collection->some(fn($item) => $item === 3));
        $this->assertFalse($collection->some(fn($item) => $item % 2 === 0));
    }

    public function testFindReturnsTheFirstMatchingItem(): void
    {
        $associativeCollection = Collection::from(['a' => 10, 'b' => 15, 'c' => 20]);
        $indexedCollection = Collection::from([10, 15, 20]);

        $this->assertSame(15, $associativeCollection->find(fn($item) => $item > 12));
        $this->assertNull($associativeCollection->find(fn($item) => $item > 25));

        $this->assertSame(15, $indexedCollection->find(fn($item) => $item > 12));
        $this->assertNull($indexedCollection->find(fn($item) => $item > 25));
    }

    public function testCloneReturnsAnEqualCollection(): void
    {
        $collection = Collection::from(['itemA', 'itemB', 'itemC']);
        $clonedCollection = $collection->clone();

        $this->assertNotSame($collection, $clonedCollection);
        $this->assertEquals($collection, $clonedCollection);
    }

    public function testClonePreservesCollectionConfig(): void
    {
        $collection = Collection::of('string', ['a' => 'A', 'b' => 'B'], associative: true, mutable: true);
        $clone = $collection->clone();

        $this->assertNotSame($collection, $clone);
        $this->assertSame($collection->toArray(), $clone->toArray());
        $this->assertSame($collection->isAssociative(), $clone->isAssociative());
        $this->assertSame($collection->isMutable(), $clone->isMutable());
        $this->assertSame($collection->dataType(), $clone->dataType());
    }

    public function testDeepCloneAlsoClonesNestedCollections(): void
    {
        $data = [
            new Collection(['a', 'b']),
            new Collection(['c', 'd']),
        ];

        $collection = Collection::from($data);
        $deepClonedCollection = $collection->deepClone();

        $this->assertNotSame($collection, $deepClonedCollection);
        $this->assertEquals($collection, $deepClonedCollection);

        foreach ($deepClonedCollection->values() as $index => $item) {
            $this->assertNotSame($collection->at($index), $item);
            $this->assertEquals($collection->at($index), $item);
        }
    }

    public function testDeepCloneBreaksObjectIdentityWhileCloneDoesNot(): void
    {
        $item = new stdClass();
        $item->value = 'original';
        $collection = Collection::from([$item]);

        $shallow = $collection->clone();
        $deep = $collection->deepClone();

        $this->assertSame($item, $shallow->first());
        $this->assertNotSame($item, $deep->first());

        $deep->first()->value = 'changed';
        $this->assertSame('original', $item->value);
    }

    public function testReverseReturnsTheItemsInReverseOrder(): void
    {
        $collection = Collection::from(['first', 'second', 'third']);
        $reversedCollection = $collection->reverse();

        $this->assertNotSame($collection, $reversedCollection);
        $this->assertSame(['third', 'second', 'first'], $reversedCollection->values());
    }

    public function testShufflePreservesTheSameItems(): void
    {
        $collection = Collection::from(['one', 'two', 'three', 'four', 'five']);
        $this->assertEqualsCanonicalizing(['one', 'two', 'three', 'four', 'five'], $collection->shuffle()->values());
    }

    public function testUniqueRemovesDuplicateValues(): void
    {
        $collection = Collection::from(['first', 'second', 'first', 'fourth', 'second']);
        $uniqueCollection = $collection->unique();

        $this->assertNotSame($collection, $uniqueCollection);
        $this->assertSame(['first', 'second', 'fourth'], $uniqueCollection->values());
    }

    public function testDuplicatesReturnsTheRepeatedOccurrences(): void
    {
        $collection = Collection::from(['first', 'second', 'first', 'fourth', 'second', 'second']);
        $duplicatesCollection = $collection->duplicates();

        $this->assertNotSame($collection, $duplicatesCollection);
        $this->assertSame(['first', 'second', 'second'], $duplicatesCollection->values());
    }

    public function testSliceReturnsTheGivenRange(): void
    {
        $collection = Collection::from(['a', 'b', 'c', 'd', 'e']);

        $slicedCollection = $collection->slice(1, 3);
        $this->assertNotSame($collection, $slicedCollection);
        $this->assertSame(['b', 'c', 'd'], $slicedCollection->values());
    }

    public function testLimitPreservesTheFirstItems(): void
    {
        $collection = Collection::from(['x', 'y', 'z', 'w']);

        $limitedCollection = $collection->limit(2);
        $this->assertNotSame($collection, $limitedCollection);
        $this->assertSame(['x', 'y'], $limitedCollection->values());
    }

    public function testEachVisitsEveryItemInOrder(): void
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
            return $item < 3;
        });

        $this->assertSame([1, 2, 3], $collectedItems);
    }

    public function testMapTransformsEveryItem(): void
    {
        $collection = Collection::from([1, 2, 3]);

        $mappedCollection = $collection->map(fn($item) => $item * 2);

        $this->assertNotSame($collection, $mappedCollection);
        $this->assertSame([2, 4, 6], $mappedCollection->values());
    }

    public function testFilterPreservesTheMatchingItems(): void
    {
        $data = [1, 2, 3, 4, 5];
        $collection = Collection::from($data);

        $filteredCollection = $collection->filter(fn($item) => $item % 2 === 0);

        $this->assertNotSame($collection, $filteredCollection);
        $this->assertSame([2, 4], $filteredCollection->values());
    }

    public function testRejectRemovesTheMatchingItems(): void
    {
        $data = [1, 2, 3, 4, 5];
        $collection = Collection::from($data);

        $rejectedCollection = $collection->reject(fn($item) => $item % 2 === 0);

        $this->assertNotSame($collection, $rejectedCollection);
        $this->assertSame([1, 3, 5], $rejectedCollection->values());
    }

    public function testSortOrdersTheValuesAscending(): void
    {
        $collection = Collection::from([3, 1, 4, 2]);

        $sortedCollection = $collection->sort();

        $this->assertNotSame($collection, $sortedCollection);
        $this->assertSame([1, 2, 3, 4], $sortedCollection->values());
    }

    public function testGroupUsesTheKeyReturnedByTheCallback(): void
    {
        $collection = Collection::from(['first', 'fourth', 'second', 'sixth', 'third']);

        $this->assertSame(
            ['f' => ['first', 'fourth'], 's' => ['second', 'sixth'], 't' => ['third']],
            $collection->group(fn($item) => $item[0]),
        );
    }

    public function testExtractTakesAValueFromEveryItemKeepingTheKeys(): void
    {
        $collection = Collection::from([
            'user1' => ['name' => 'User 1', 'age' => 30],
            'user2' => ['name' => 'User 2', 'age' => 25],
            'user3' => ['name' => 'User 3', 'age' => 35],
        ]);

        $names = $collection->extract('name');
        $ages = $collection->extract('age');

        $this->assertSame(['user1' => 'User 1', 'user2' => 'User 2', 'user3' => 'User 3'], $names);
        $this->assertSame(['user1' => 30, 'user2' => 25, 'user3' => 35], $ages);
    }

    public function testPluckTakesAValueFromEveryItem(): void
    {
        $collection = Collection::from([
            'user1' => ['name' => 'User 1', 'age' => 30],
            'user2' => ['name' => 'User 2', 'age' => 25],
            'user3' => ['name' => 'User 3', 'age' => 35],
        ]);

        $names = $collection->pluck('name');
        $ages = $collection->pluck('age');

        $this->assertSame(['User 1', 'User 2', 'User 3'], $names);
        $this->assertSame([30, 25, 35], $ages);
    }

    public function testFlattenJoinsNestedLists(): void
    {
        $collection = Collection::from([
            ['a', 'b'],
            ['c', 'd'],
            ['e', 'f'],
        ]);

        $flattenedCollection = $collection->flatten();

        $this->assertNotSame($collection, $flattenedCollection);
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $flattenedCollection->values());
    }

    public function testFilterByWithCallback(): void
    {
        $collection = Collection::from([
            ['name' => 'User 1', 'age' => 30],
            ['name' => 'User 2', 'age' => 25],
            ['name' => 'User 3', 'age' => 35],
        ]);

        $filteredCollection = $collection->filterBy('age', fn($age) => $age > 30);

        $this->assertNotSame($collection, $filteredCollection);
        $this->assertSame([['name' => 'User 3', 'age' => 35]], $filteredCollection->values());
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
                '==', 'equalTo'              => [['value' => 20]],
                '!=', 'notEqualTo'           => [['value' => 10], ['value' => 30]],
                '===', 'strictlyEqualTo'     => [['value' => 20]],
                '!==', 'strictlyNotEqualTo'  => [['value' => 10], ['value' => 30]],
                '>', 'greaterThan'           => [['value' => 30]],
                '>=', 'greaterThanOrEqualTo' => [['value' => 20], ['value' => 30]],
                '<', 'lessThan'              => [['value' => 10]],
                '<=', 'lessThanOrEqualTo'    => [['value' => 10], ['value' => 20]],
            };

            $filteredCollection = $collection->filterBy('value', $filter, 20);

            $this->assertNotSame($collection, $filteredCollection);
            $this->assertSame($expected, $filteredCollection->values());
        }
    }

    public function testFilterByRejectsInvalidFilters(): void
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

    public function testFilterByRejectsCallbacksWithThreeArguments(): void
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

    #[DataProvider('phpFunctionNameProvider')]
    public function testFilterByComparesValuesThatAreNamesOfPhpFunctions(string $word): void
    {
        $collection = Collection::from([
            ['word' => $word],
            ['word' => 'other'],
            ['word' => 'something else'],
        ]);

        $this->assertSame([['word' => $word]], $collection->filterBy('word', $word)->values());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function phpFunctionNameProvider(): iterable
    {
        foreach (['date', 'time', 'count', 'key', 'max', 'current'] as $word) {
            yield $word => [$word];
        }
    }

    public function testFilterByComparesLooselyWithoutAnExplicitComparison(): void
    {
        $collection = Collection::from([['n' => 1], ['n' => '1'], ['n' => 2], ['n' => true]]);

        $this->assertSame([['n' => 1], ['n' => '1'], ['n' => true]], $collection->filterBy('n', 1)->values());
        $this->assertSame([['n' => 1]], $collection->filterBy('n', '===', 1)->values());
        $this->assertSame([['n' => 1], ['n' => '1'], ['n' => true]], $collection->filterBy('n', '==', '1')->values());
        $this->assertSame([['n' => '1'], ['n' => 2], ['n' => true]], $collection->filterBy('n', '!==', 1)->values());
    }

    public function testSortByOrdersByTheGivenKey(): void
    {
        $collection = Collection::from([
            ['name' => 'User 3', 'age' => 35],
            ['name' => 'User 1', 'age' => 30],
            ['name' => 'User 2', 'age' => 25],
        ]);

        $sortedByName = $collection->sortBy('name');
        $sortedByAge = $collection->sortBy('age');

        $this->assertNotSame($collection, $sortedByName);
        $this->assertSame([
            ['name' => 'User 1', 'age' => 30],
            ['name' => 'User 2', 'age' => 25],
            ['name' => 'User 3', 'age' => 35],
        ], $sortedByName->values());

        $this->assertNotSame($collection, $sortedByAge);
        $this->assertSame([
            ['name' => 'User 2', 'age' => 25],
            ['name' => 'User 1', 'age' => 30],
            ['name' => 'User 3', 'age' => 35],
        ], $sortedByAge->values());
    }

    public function testSortByIsCaseInsensitiveAndPreservesKeysByDefault(): void
    {
        $collection = Collection::from(['x' => ['name' => 'b'], 'y' => ['name' => 'A'], 'z' => ['name' => 'C']]);

        $this->assertSame(['y', 'x', 'z'], $collection->sortBy('name')->keys());
        $this->assertSame([0, 1, 2], $collection->sortBy('name', preserveKeys: false)->keys());
        $this->assertSame(['y', 'z', 'x'], $collection->sortBy('name', caseSensitive: true)->keys());
        $this->assertSame(['z', 'x', 'y'], $collection->sortBy('name', SORT_DESC)->keys());
    }

    public function testGroupByUsesTheValueOfTheGivenKey(): void
    {
        $collection = Collection::from([
            ['name' => 'User 1', 'department' => 'HR'],
            ['name' => 'User 2', 'department' => 'IT'],
            ['name' => 'User 3', 'department' => 'HR'],
            ['name' => 'User 4', 'department' => 'IT'],
            ['name' => 'User 5', 'department' => 'Finance'],
        ]);

        $this->assertSame([
            'HR' => [
                ['name' => 'User 1', 'department' => 'HR'],
                ['name' => 'User 3', 'department' => 'HR'],
            ],
            'IT' => [
                ['name' => 'User 2', 'department' => 'IT'],
                ['name' => 'User 4', 'department' => 'IT'],
            ],
            'Finance' => [
                ['name' => 'User 5', 'department' => 'Finance'],
            ],
        ], $collection->groupBy('department'));
    }

    public function testKeyByUsesTheValueOfTheGivenKeyAsKey(): void
    {
        $collection = Collection::from([
            ['id' => 'a1', 'name' => 'User 1'],
            ['id' => 'b2', 'name' => 'User 2'],
            ['id' => 'c3', 'name' => 'User 3'],
        ]);

        $keyedCollection = $collection->keyBy('id');

        $this->assertNotSame($collection, $keyedCollection);
        $this->assertSame([
            'a1' => ['id' => 'a1', 'name' => 'User 1'],
            'b2' => ['id' => 'b2', 'name' => 'User 2'],
            'c3' => ['id' => 'c3', 'name' => 'User 3'],
        ], $keyedCollection->toArray());
    }

    public function testKeyedListsBecomeAssociative(): void
    {
        $keyedCollection = Collection::from([
            ['id' => 'a1', 'name' => 'User 1'],
            ['id' => 'b2', 'name' => 'User 2'],
        ])->keyBy('id');

        $this->assertTrue($keyedCollection->isAssociative());
        $this->assertSame(['a1', 'b2'], $keyedCollection->keys());
        $this->assertSame(['id' => 'b2', 'name' => 'User 2'], $keyedCollection->get('b2'));
    }

    public function testWithReturnsACopyWithTheItemAdded(): void
    {
        $data = ['item1', 'item2'];
        $collection = Collection::from($data);

        $newCollection = $collection->with('item3');

        $this->assertNotSame($collection, $newCollection);
        $this->assertSame(['item1', 'item2', 'item3'], $newCollection->values());
    }

    public function testWithSkipsExistingItems(): void
    {
        $collection = Collection::from(['item1', 'item2']);

        $newCollection = $collection->with('item2');

        $this->assertNotSame($collection, $newCollection);
        $this->assertSame(['item1', 'item2'], $newCollection->values());
    }

    public function testWithRejectsValuesOfOtherTypes(): void
    {
        $collection = Collection::from(['a', 'b'], typed: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type string to be added, int given');
        $collection->with(42);
    }

    public function testWithoutReturnsACopyWithoutTheItem(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3']);

        $newCollection = $collection->without('item2');

        $this->assertNotSame($collection, $newCollection);
        $this->assertSame(['item1', 'item3'], $newCollection->values());
    }

    public function testUnionPreservesTheItemsOfBothCollections(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2']);
        $collection2 = Collection::from(['b' => 'item2', 'c' => 'item3']);

        $unionCollection = $collection1->union($collection2);

        $this->assertNotSame($collection1, $unionCollection);
        $this->assertSame(['a' => 'item1', 'b' => 'item2', 'c' => 'item3'], $unionCollection->toArray());
    }

    public function testUnionOfListsJoinsValues(): void
    {
        $collection1 = Collection::from(['a', 'b']);
        $collection2 = Collection::from(['b', 'c', 'd']);

        $this->assertSame(['a', 'b', 'c', 'd'], $collection1->union($collection2)->toArray());
    }

    public function testUnionOfTypedListsRejectsValuesOfOtherTypes(): void
    {
        $collection = Collection::from(['a', 'b'], typed: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type string to be added, int given');

        $collection->union(Collection::from([1, 2]));
    }

    public function testUnionTypedCollectionsAcceptEveryListedType(): void
    {
        $collection = Collection::of('int|string', [1, 'two'], mutable: true);

        $collection->add(3);
        $collection->add('four');

        $this->assertSame([1, 'two', 3, 'four'], $collection->values());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type int|string to be added, float given');

        $collection->add(5.5);
    }

    public function testUnionTypedCollectionsRejectInitialDataOfOtherTypes(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');

        Collection::of('int|string', [1, 2.5]);
    }

    public function testUnionTypedAssociativeCollectionsValidateSetValues(): void
    {
        $collection = Collection::of('int|string', ['a' => 1], associative: true, mutable: true);

        $collection->set('b', 'two');
        $this->assertSame(['a' => 1, 'b' => 'two'], $collection->toArray());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type int|string, null given');

        $collection->set('c', null);
    }

    public function testIntersectionPreservesTheCommonValues(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2', 'c' => 'item3']);
        $collection2 = Collection::from(['b' => 'item2', 'c' => 'item4', 'd' => 'item3']);

        $intersectionCollection = $collection1->intersection($collection2);

        $this->assertNotSame($collection1, $intersectionCollection);
        $this->assertSame(['b' => 'item2', 'c' => 'item3'], $intersectionCollection->toArray());
    }

    public function testDifferenceRemovesTheValuesOfTheOtherCollection(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2', 'c' => 'item3']);
        $collection2 = Collection::from(['b' => 'item2', 'c' => 'item4']);

        $differenceCollection = $collection1->difference($collection2);

        $this->assertNotSame($collection1, $differenceCollection);
        $this->assertSame(['a' => 'item1', 'c' => 'item3'], $differenceCollection->toArray());
    }

    public function testSetOperationsCompareObjectsByIdentity(): void
    {
        // Different objects with the same string representation
        $first = new StringableFixture('same');
        $second = new StringableFixture('same');
        $collection = Collection::from([$first, $second]);

        $this->assertSame([$first], $collection->difference(Collection::from([$second]))->values());
        $this->assertSame([$second], $collection->intersection(Collection::from([$second]))->values());
        $this->assertSame([$first, $second], $collection->unique()->values());
        $this->assertSame([], $collection->duplicates()->values());
    }

    public function testSetOperationsCompareScalarsStrictly(): void
    {
        $collection = Collection::from(['1', 1, 1.0, true]);

        $this->assertSame(['1', 1.0, true], $collection->difference(Collection::from([1]))->values());
        $this->assertSame([1], $collection->intersection(Collection::from([1]))->values());
        $this->assertSame(['1', 1, 1.0, true], $collection->unique()->values());
        $this->assertSame([], $collection->duplicates()->values());
    }

    public function testSetOperationsAcceptValuesThatCannotBeConvertedToStrings(): void
    {
        $object = new stdClass();
        $array = ['a'];
        $collection = Collection::from([$object, $array, $object, $array]);

        $this->assertSame([$array, $array], $collection->difference(Collection::from([$object]))->values());
        $this->assertSame([$object, $object], $collection->intersection(Collection::from([$object]))->values());
        $this->assertSame([$object, $array], $collection->unique()->values());
        $this->assertSame([$object, $array], $collection->duplicates()->values());
    }

    public function testAddAppendsAnItem(): void
    {
        $collection = Collection::from(['item1', 'item2'], mutable: true);

        $collection->add('item3');

        $this->assertCount(3, $collection);
        $this->assertSame(['item1', 'item2', 'item3'], $collection->values());
    }

    public function testAddRejectsImmutableCollections(): void
    {
        $collection = Collection::from(['item1', 'item2'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be added only to mutable and non-associative collections');
        $collection->add('item3');
    }

    public function testAddRejectsAssociativeCollections(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be added only to mutable and non-associative collections');
        $collection->add('value3');
    }

    public function testAddRejectsMismatchingTypes(): void
    {
        $collection = Collection::of('int', mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type int to be added, string given');
        $collection->add('notAnInteger');
    }

    public function testAddMultipleAppendsSeveralItems(): void
    {
        $collection = Collection::from(['item1'], mutable: true);

        $collection->addMultiple(['item2', 'item3']);

        $this->assertCount(3, $collection);
        $this->assertSame(['item1', 'item2', 'item3'], $collection->values());
    }

    public function testPullRemovesAValue(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3'], mutable: true);

        $collection->pull('item2');

        $this->assertSame(['item1', 'item3'], $collection->values());
    }

    public function testPullRejectsImmutableCollections(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be pulled only from mutable and non-associative collections');
        $collection->pull('item2');
    }

    public function testPullRejectsAssociativeCollections(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be pulled only from mutable and non-associative collections');
        $collection->pull('value1');
    }

    public function testPullMultipleRemovesSeveralValues(): void
    {
        $collection = Collection::from(['item1', 'item2', 'item3', 'item4'], mutable: true);

        $collection->pullMultiple(['item2', 'item4']);

        $this->assertSame(['item1', 'item3'], $collection->values());
    }

    public function testMoveItemChangesThePositionOfAnItem(): void
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

    public function testMoveItemPreservesTheKeysOfAssociativeCollections(): void
    {
        $collection = Collection::from(['a' => 'A', 'b' => 'B', 'c' => 'C'], mutable: true);

        $collection->moveItem(2, 0);

        $this->assertSame(['c' => 'C', 'a' => 'A', 'b' => 'B'], $collection->toArray());
    }

    public function testHasChecksTheKeys(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertTrue($collection->has('key1'));
        $this->assertFalse($collection->has('key3'));
    }

    public function testHasRejectsNonAssociativeCollections(): void
    {
        $collection = Collection::from(['value1', 'value2', 'value3']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value presence can be checked only in associative collections');
        $collection->has('key1');
    }

    public function testGetReturnsTheValueOfAKey(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertSame('value1', $collection->get('key1'));
        $this->assertSame('value2', $collection->get('key2'));
        $this->assertNull($collection->get('key3'));
        $this->assertSame('default', $collection->get('key3', 'default'));
    }

    public function testGetRejectsNonAssociativeCollections(): void
    {
        $collection = Collection::from(['value1', 'value2', 'value3']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be get only from associative collections');
        $collection->get('key1');
    }

    public function testSetReplacesOrAddsAKey(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: true);

        $collection->set('key2', 'newValue2');
        $collection->set('key3', 'value3');

        $this->assertSame(['key1' => 'value1', 'key2' => 'newValue2', 'key3' => 'value3'], $collection->toArray());
    }

    public function testSetRejectsNonAssociativeCollections(): void
    {
        $collection = Collection::from(['value1', 'value2', 'value3'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be set only to associative and mutable collections');
        $collection->set('key1', 'newValue1');
    }

    public function testSetRejectsImmutableCollections(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be set only to associative and mutable collections');
        $collection->set('key2', 'newValue2');
    }

    public function testSetRejectsMismatchingTypes(): void
    {
        $collection = Collection::of('int', associative: true, mutable: true);
        $collection->set('key1', 10);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value must be of type int, string given');
        $collection->set('key2', 'notAnInteger');
    }

    public function testRemoveDeletesAKey(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'], mutable: true);

        $collection->remove('key2');

        $this->assertSame(['key1' => 'value1', 'key3' => 'value3'], $collection->toArray());
    }

    public function testRemoveRejectsNonAssociativeCollections(): void
    {
        $collection = Collection::from(['value1', 'value2', 'value3'], mutable: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be removed only from associative and mutable collections');
        $collection->remove('key1');
    }

    public function testRemoveRejectsImmutableCollections(): void
    {
        $collection = Collection::from(['key1' => 'value1', 'key2' => 'value2'], mutable: false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be removed only from associative and mutable collections');
        $collection->remove('key2');
    }

    public function testMergeOverridesTheExistingKeys(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2'], mutable: true);
        $collection2 = Collection::from(['b' => 'item3', 'c' => 'item4']);

        $collection1->merge($collection2);

        $this->assertSame(['a' => 'item1', 'b' => 'item3', 'c' => 'item4'], $collection1->toArray());
    }

    public function testMergeRejectsImmutableCollections(): void
    {
        $collection1 = Collection::from(['a' => 'item1', 'b' => 'item2'], mutable: false);
        $collection2 = Collection::from(['b' => 'item3', 'c' => 'item4']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Values can be merged only into mutable collections');
        $collection1->merge($collection2);
    }

    public function testMergeRejectsAssociativityMismatches(): void
    {
        $collection1 = Collection::from(['item1', 'item2'], mutable: true);
        $collection2 = Collection::from(['b' => 'item3', 'c' => 'item4']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Collections cannot be merged if their associativeness is different');
        $collection1->merge($collection2);
    }

    public function testMergeRejectsMismatchingTypes(): void
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

    /**
     * @param Closure(Collection<mixed>): mixed $operation
     */
    #[DataProvider('immutableOperationProvider')]
    public function testImmutableCollectionRejectsEveryMutatingOperation(bool $associative, Closure $operation): void
    {
        $collection = $associative
            ? Collection::from(['a' => 'A', 'b' => 'B'], mutable: false)
            : Collection::from(['A', 'B'], mutable: false);
        $before = $collection->toArray();

        try {
            $operation($collection);
            $this->fail('An immutable collection was mutated.');
        } catch (LogicException) {
            // Expected
        }

        $this->assertSame($before, $collection->toArray());
    }

    /**
     * @return iterable<string, array{bool, Closure(Collection): mixed}>
     */
    public static function immutableOperationProvider(): iterable
    {
        yield 'add' => [false, static fn(Collection $collection) => $collection->add('C')];
        yield 'pull' => [false, static fn(Collection $collection) => $collection->pull('A')];
        yield 'set' => [true, static fn(Collection $collection) => $collection->set('c', 'C')];
        yield 'remove' => [true, static fn(Collection $collection) => $collection->remove('a')];
        yield 'merge' => [false, static fn(Collection $collection) => $collection->merge(Collection::from(['C']))];

        // Move operations are not considered mutating, since collections are considered equivalent regardless of item order
    }

    public function testMutableCollectionsRejectOperationsOfTheOtherKind(): void
    {
        $collection = Collection::from(['A'], mutable: true);
        $map = Collection::from(['a' => 'A'], mutable: true);

        foreach (
            [
                static fn() => $collection->set('b', 'B'),
                static fn() => $collection->remove('a'),
                static fn() => $map->add('B'),
                static fn() => $map->pull('A'),
            ] as $operation
        ) {
            try {
                $operation();
                $this->fail('An operation of the wrong kind was accepted.');
            } catch (LogicException) {
                // Expected
            }
        }

        $this->assertSame(['A'], $collection->toArray());
        $this->assertSame(['a' => 'A'], $map->toArray());
    }

    public function testFailedMutationLeavesTheCollectionUnchanged(): void
    {
        $collection = Collection::of('string', ['first'], mutable: true);
        $before = $collection->toArray();

        $this->captureException(LogicException::class, static fn() => $collection->add(123));

        $this->assertSame($before, $collection->toArray());
    }

    public function testNonMutatingTransformationsDoNotChangeTheSource(): void
    {
        $collection = Collection::from([3, 1, 2]);
        $original = $collection->toArray();

        $collection->reverse();
        $collection->shuffle();
        $collection->unique();
        $collection->duplicates();
        $collection->slice(1);
        $collection->limit(2);
        $collection->map(fn(int $value): int => $value * 2);
        $collection->filter(fn(int $value): bool => $value > 1);
        $collection->reject(fn(int $value): bool => $value > 1);
        $collection->sort();
        $collection->flatten();
        $collection->with(4);
        $collection->without(1);

        $this->assertSame($original, $collection->toArray());
    }

    public function testMutabilityConversionsPreserveDataAndDoNotAliasCollections(): void
    {
        $collection = Collection::from(['a', 'b']);
        $mutable = $collection->toMutable();
        $roundTrip = $mutable->toImmutable();

        $this->assertFalse($collection->isMutable());
        $this->assertTrue($mutable->isMutable());
        $this->assertFalse($roundTrip->isMutable());
        $this->assertSame($collection->toArray(), $mutable->toArray());
        $this->assertSame($collection->toArray(), $roundTrip->toArray());

        $mutable->add('c');
        $this->assertSame(['a', 'b'], $collection->toArray());
        $this->assertSame(['a', 'b'], $roundTrip->toArray());
    }
}
