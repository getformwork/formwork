<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Utils\Fixtures\ArrayableFixture;
use Formwork\Tests\Unit\Utils\Fixtures\StringableFixture;
use Formwork\Tests\Unit\Utils\Fixtures\TraversableFixture;
use Formwork\Utils\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

#[CoversClass(Arr::class)]
final class ArrTest extends TestCase
{
    public function testGetReadsNestedKeysFromDotNotation(): void
    {
        /** @var array<string, mixed> $data */
        $data = [
            'user' => [
                'name'    => 'Test User',
                'email'   => 'user@example.test',
                'country' => 'Italy',
            ],
            'date' => '2025-03-29',
        ];

        $this->assertSame('2025-03-29', Arr::get($data, 'date'));
        $this->assertSame('Test User', Arr::get($data, 'user.name'));
        $this->assertNull(Arr::get($data, 'user.age'));
    }

    public function testGetDistinguishesMissingValuesFromExistingNullValues(): void
    {
        $array = ['present' => null];

        $this->assertTrue(Arr::has($array, 'present'));
        $this->assertNull(Arr::get($array, 'present', 'fallback'));
        $this->assertSame('fallback', Arr::get($array, 'missing', 'fallback'));
    }

    public function testHasChecksNestedKeysFromDotNotation(): void
    {
        $data = [
            'user' => [
                'name'    => 'Test User',
                'email'   => 'user@example.test',
                'country' => 'Italy',
            ],
            'date' => '2025-03-29',
        ];

        $this->assertTrue(Arr::has($data, 'date'));
        $this->assertTrue(Arr::has($data, 'user.email'));
        $this->assertFalse(Arr::has($data, 'user.age'));
    }

    public function testSetCreatesNestedKeysFromDotNotation(): void
    {
        $data = [
            'user' => [
                'name'    => 'Test User',
                'email'   => 'user@example.test',
                'country' => 'Italy',
            ],
            'date' => '2025-03-29',
        ];

        Arr::set($data, 'user.age', 30);
        $this->assertSame(30, Arr::get($data, 'user.age'));

        Arr::set($data, 'roles.available', 'admin');
        $this->assertSame('admin', Arr::get($data, 'roles.available'));

        Arr::set($data, 'date', '2025-04-01');
        $this->assertSame('2025-04-01', Arr::get($data, 'date'));
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('scalarSegmentProvider')]
    public function testSetReportsSegmentsThatAreNotArrays(array $data, string $key, string $message): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);
        Arr::set($data, $key, 'value');
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function scalarSegmentProvider(): iterable
    {
        yield 'a string' => [['site' => 'Formwork'], 'site.title', 'Cannot set "site.title": "site" is not an array'];
        yield 'a nested number' => [['a' => ['b' => 1]], 'a.b.c.d', 'Cannot set "a.b.c.d": "a.b" is not an array'];
        yield 'a boolean' => [['enabled' => true], 'enabled.value', 'Cannot set "enabled.value": "enabled" is not an array'];
    }

    public function testSetCreatesArraysInPlaceOfNullSegments(): void
    {
        $data = ['user' => null];

        Arr::set($data, 'user.name', 'User 1');

        $this->assertSame('User 1', Arr::get($data, 'user.name'));
    }

    public function testRemoveDeletesNestedKeysFromDotNotation(): void
    {
        $data = [
            'user' => [
                'name'    => 'Test User',
                'email'   => 'user@example.test',
                'country' => 'Italy',
            ],
            'date' => '2025-03-29',
        ];

        Arr::remove($data, 'user.email');
        $this->assertFalse(Arr::has($data, 'user.email'));

        Arr::remove($data, 'roles.available');
        $this->assertFalse(Arr::has($data, 'roles.available'));

        Arr::remove($data, 'date');
        $this->assertFalse(Arr::has($data, 'date'));
    }

    public function testDotFlattensAssociativeArraysIntoDottedKeys(): void
    {
        $data = [
            'app' => [
                'theme'         => 'dark',
                'notifications' => true,
                'roles'         => ['admin', 'editor'],
                'permissions'   => ['pages', 'files'],
                'cache'         => [
                    'enabled'  => true,
                    'duration' => 3600,
                ],
            ],

        ];

        $expected = [
            'app.theme'          => 'dark',
            'app.notifications'  => true,
            'app.roles'          => ['admin', 'editor'],
            'app.permissions'    => ['pages', 'files'],
            'app.cache.enabled'  => true,
            'app.cache.duration' => 3600,
        ];

        /** @var array<string, mixed> $actual */
        $actual = Arr::dot($data);
        $this->assertSame($expected, $actual);
    }

    /**
     * @param list<int|list<int|list<int|list<int>>>> $expected
     */
    #[DataProvider('flattenDepthProvider')]
    public function testFlattenStopsAtTheGivenDepth(int $depth, array $expected): void
    {
        $this->assertSame($expected, Arr::flatten([1, [2, [3, [4]]]], $depth));
    }

    /**
     * @return iterable<string, array{int, list<int|list<int|list<int|list<int>>>>}>
     */
    public static function flattenDepthProvider(): iterable
    {
        yield 'depth zero keeps the array untouched' => [0, [1, [2, [3, [4]]]]];
        yield 'depth one' => [1, [1, 2, [3, [4]]]];
        yield 'depth two' => [2, [1, 2, 3, [4]]];
        yield 'depth three' => [3, [1, 2, 3, 4]];
        yield 'unlimited depth by default' => [PHP_INT_MAX, [1, 2, 3, 4]];
    }

    public function testUndotExpandsDottedKeysIntoNestedArrays(): void
    {
        $data = [
            'app' => [
                'theme'         => 'dark',
                'notifications' => true,
                'roles'         => ['admin', 'editor'],
                'permissions'   => ['pages', 'files'],
                'cache'         => [
                    'enabled'  => true,
                    'duration' => 3600,
                ],
            ],
        ];

        $dot = [
            'app.theme'          => 'dark',
            'app.notifications'  => true,
            'app.roles'          => ['admin', 'editor'],
            'app.permissions'    => ['pages', 'files'],
            'app.cache.enabled'  => true,
            'app.cache.duration' => 3600,
        ];

        /** @var array<array-key, mixed> $actual */
        $actual = Arr::undot($dot);
        $this->assertSame($data, $actual);
    }

    public function testPullRemovesEveryOccurrenceOfAValue(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        Arr::pull($data, 'second');
        $this->assertNotContains('second', $data);
    }

    public function testSpliceReplacesARangeAndReturnsTheRemovedItems(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $removed = Arr::splice($data, 1, 2, ['fourth', 'fifth']);

        $this->assertSame(['second', 'third'], $removed);
        $this->assertSame(['first', 'fourth', 'fifth', 'second'], $data);
    }

    public function testSpliceWithAssociativeArray(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $removed = Arr::splice($data, 1, 1, ['age' => 30]);

        $this->assertSame(['email' => 'user@example.test'], $removed);
        $this->assertSame(['name' => 'Test User', 'age' => 30, 'country' => 'Italy'], $data);
    }

    public function testSpliceWithNegativeOffset(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $removed = Arr::splice($data, -2, 1, ['age' => 30]);

        $this->assertSame(['email' => 'user@example.test'], $removed);
        $this->assertSame(['name' => 'Test User', 'age' => 30, 'country' => 'Italy'], $data);
    }

    public function testSpliceWithNegativeLength(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $removed = Arr::splice($data, 0, -1, ['age' => 30]);

        $this->assertSame(['name' => 'Test User', 'email' => 'user@example.test'], $removed);
        $this->assertSame(['age' => 30, 'country' => 'Italy'], $data);
    }

    public function testSpliceRejectsDuplicateKeys(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot replace 1 items from offset 1: some keys in the replacement array are the same of the resulting array');
        Arr::splice($data, 1, 1, ['country' => 'Canada']);
    }

    public function testSpliceRejectsReplacementKeyCollisionsWithoutChangingTheArray(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $before = $array;

        try {
            Arr::splice($array, 1, 1, ['a' => 20]);
            $this->fail('The replacement key should have been rejected.');
        } catch (UnexpectedValueException) {
            // Expected.
        }

        $this->assertSame($before, $array);
    }

    public function testSpliceInsertsWithoutRemovingWhenTheLengthIsZero(): void
    {
        $data = ['a' => 1, 'b' => 2];

        $removed = Arr::splice($data, 1, 0, ['z' => 9]);

        $this->assertSame([], $removed);
        $this->assertSame(['a' => 1, 'z' => 9, 'b' => 2], $data);
    }

    public function testSpliceWithoutLengthRemovesEverythingFromTheOffset(): void
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];

        $removed = Arr::splice($data, 1, null, ['z' => 9]);

        $this->assertSame(['b' => 2, 'c' => 3], $removed);
        $this->assertSame(['a' => 1, 'z' => 9], $data);
    }

    public function testMoveItemForwards(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        Arr::moveItem($data, 0, 2);
        $this->assertSame(['second', 'third', 'first', 'second'], $data);
    }

    public function testMoveItemPreservesAllValuesAndKeys(): void
    {
        $array = ['first' => 'A', 'second' => 'B', 'third' => 'C'];

        Arr::moveItem($array, 0, 2);

        $this->assertSame(['second' => 'B', 'third' => 'C', 'first' => 'A'], $array);
    }

    public function testEntriesArePairsOfKeysAndValues(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $expected = [
            ['name', 'Test User'],
            ['email', 'user@example.test'],
            ['country', 'Italy'],
        ];

        $this->assertSame($expected, Arr::entries($data));
    }

    public function testNthReturnsNullOutsideTheArray(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $this->assertSame('third', Arr::nth($data, 2));
        $this->assertNull(Arr::nth($data, 5));
    }

    public function testAtCountsNegativePositionsFromTheEnd(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $this->assertSame('third', Arr::at($data, 2));
        $this->assertNull(Arr::at($data, 5));

        $this->assertSame('second', Arr::at($data, -1));
        $this->assertNull(Arr::at($data, -5));
    }

    public function testAtReturnsItemsByPositionFromTheStartOrTheEnd(): void
    {
        $data = ['x' => 5, 'y' => 6, 'z' => 7];

        $this->assertSame(5, Arr::at($data, 0));
        $this->assertSame(6, Arr::at($data, 1));
        $this->assertSame(7, Arr::at($data, -1));
        $this->assertSame(5, Arr::at($data, -3));
    }

    public function testAtReturnsNullOutsideTheArray(): void
    {
        $this->assertNull(Arr::at([5, 6, 7], 3));
        $this->assertNull(Arr::at([5, 6, 7], -4));
        $this->assertNull(Arr::at([], 0));
        $this->assertNull(Arr::at([], -1));
    }

    public function testIndexOfReturnsTheFirstPosition(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $this->assertSame(1, Arr::indexOf($data, 'second'));
        $this->assertNull(Arr::indexOf($data, 'fourth'));
    }

    public function testIndexOfAndKeyOfCompareValuesStrictly(): void
    {
        $this->assertNull(Arr::indexOf(['1', 2, 0], 1));
        $this->assertNull(Arr::indexOf(['a', 0], '0'));
        $this->assertNull(Arr::keyOf(['x' => '1'], 1));
        $this->assertNull(Arr::keyOf(['x' => 0], false));

        $this->assertSame(1, Arr::indexOf(['1', 1, 1], 1));
        $this->assertSame('y', Arr::keyOf(['x' => '1', 'y' => 1, 'z' => 1], 1));
    }

    public function testIndexOfIgnoresKeysWhileKeyOfReturnsThem(): void
    {
        $data = ['a' => 'first', 'b' => 'second'];

        $this->assertSame(1, Arr::indexOf($data, 'second'));
        $this->assertSame('b', Arr::keyOf($data, 'second'));
        $this->assertNull(Arr::indexOf($data, 'missing'));
        $this->assertNull(Arr::keyOf($data, 'missing'));
    }

    public function testKeyOfReturnsTheKeyOfAValue(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $this->assertSame('email', Arr::keyOf($data, 'user@example.test'));
        $this->assertNull(Arr::keyOf($data, 'Brazil'));
    }

    public function testDuplicatesPreserveTheirKeys(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $this->assertSame([3 => 'second'], Arr::duplicates($data));
    }

    public function testDuplicatesAreComparedStrictly(): void
    {
        $first = new StringableFixture('same');
        $second = new StringableFixture('same');

        $this->assertSame([], Arr::duplicates(['1', 1, 1.0, true]));
        $this->assertSame([], Arr::duplicates([$first, $second]));
        $this->assertSame([2 => $first], Arr::duplicates([$first, $second, $first]));
    }

    public function testAppendMissingAddsOnlyTheMissingKeys(): void
    {
        $data = [
            'theme'         => 'dark',
            'notifications' => true,
            'roles'         => ['admin', 'editor'],
            'permissions'   => ['pages', 'files'],
            'cache'         => [
                'enabled'  => true,
                'duration' => 3600,
            ],
        ];

        $expected = [
            'theme'         => 'dark',
            'notifications' => true,
            'roles'         => ['admin', 'editor', 'user'],
            'permissions'   => ['pages', 'files'],
            'cache'         => [
                'enabled'  => true,
                'duration' => 3600,
            ],
            'language' => 'en',
        ];

        $result = Arr::appendMissing($data, ['roles' => ['admin', 'editor', 'user'], 'language' => 'en', 'cache' => false]);
        $this->assertSame($expected, $result);
    }

    public function testAppendMissingAddsOnlyMissingValuesRecursively(): void
    {
        $first = ['config' => ['enabled' => true]];
        $second = ['config' => ['enabled' => false, 'cache' => true], 'extra' => 1];

        $this->assertSame([
            'config' => ['enabled' => true, 'cache' => true],
            'extra'  => 1,
        ], Arr::appendMissing($first, $second));
    }

    public function testExcludingEveryValueLeavesAnEmptyArray(): void
    {
        // Test when all items are excluded
        $array = [
            'a' => 1,
            'b' => 2,
        ];

        $exclusion = [
            'a' => 1,
            'b' => 2,
        ];

        $expected = [];

        $result = Arr::exclude($array, $exclusion);
        $this->assertSame($expected, $result);
    }

    public function testRandomReturnsAnItemOrNullForEmptyArrays(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $this->assertContains(Arr::random($data), $data);
        $this->assertNull(Arr::random([]));
    }

    public function testShuffledArraysPreserveTheirValues(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $shuffled = Arr::shuffle($data);

        $this->assertSameSize($data, $shuffled);

        foreach ($shuffled as $item) {
            $this->assertContains($item, $data);
        }

        $this->assertSame(['test'], Arr::shuffle(['test']));
    }

    public function testMapTransformsTheValuesKeepingTheKeys(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $expected = [
            'name'    => 'TEST USER',
            'email'   => 'USER@EXAMPLE.TEST',
            'country' => 'ITALY',
        ];

        $this->assertSame($expected, Arr::map($data, fn($data) => strtoupper($data)));
    }

    public function testMapKeysTransformsTheKeys(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $expected = [
            'USER_NAME'    => 'Test User',
            'USER_EMAIL'   => 'user@example.test',
            'USER_COUNTRY' => 'Italy',
        ];

        $this->assertSame($expected, Arr::mapKeys($data, fn($key) => 'USER_' . strtoupper($key)));
    }

    public function testFilterPreservesTheMatchingItems(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $expected = [
            'name'  => 'Test User',
            'email' => 'user@example.test',
        ];

        $this->assertSame($expected, Arr::filter($data, fn($value, $key) => $value !== 'Italy'));
    }

    public function testRejectRemovesTheMatchingItems(): void
    {
        $data = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $expected = [
            'email' => 'user@example.test',
        ];

        $this->assertSame($expected, Arr::reject($data, fn($value, $key) => !str_contains($value, '@')));
    }

    public function testEveryChecksThatAllItemsMatch(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $this->assertTrue(Arr::every($data, fn($value) => is_string($value)));
        $this->assertFalse(Arr::every($data, fn($value) => $value === 'second'));
    }

    public function testSomeChecksThatAnyItemMatches(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $this->assertTrue(Arr::some($data, fn($value) => $value === 'second'));
        $this->assertFalse(Arr::some($data, fn($value) => $value === 'fourth'));
    }

    public function testFindReturnsTheFirstMatchingItem(): void
    {
        $data = ['first', 'second', 'third',  'second'];

        $this->assertSame('second', Arr::find($data, fn($value) => $value === 'second'));
        $this->assertNull(Arr::find($data, fn($value) => $value === 'fourth'));
    }

    public function testPluckTakesAValueFromEveryItem(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1', 'group' => 'A', 'count' => 5],
            ['id' => 2, 'name' => 'Item 2', 'group' => 'A', 'count' => 3],
            ['id' => 3, 'name' => 'Item 3', 'group' => 'B', 'count' => 8],
        ];

        $expected = [
            'Item 1',
            'Item 2',
            'Item 3',
        ];

        $this->assertSame($expected, Arr::extract($data, 'name'));
    }

    public function testGroupByUsesTheValueOfTheGivenKey(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1', 'group' => 'A', 'count' => 5],
            ['id' => 2, 'name' => 'Item 2', 'group' => 'A', 'count' => 3],
            ['id' => 3, 'name' => 'Item 3', 'group' => 'B', 'count' => 8],

        ];

        $expected = [
            'A' => [
                ['id' => 1, 'name' => 'Item 1', 'group' => 'A', 'count' => 5],
                ['id' => 2, 'name' => 'Item 2', 'group' => 'A', 'count' => 3],
            ],
            'B' => [
                ['id' => 3, 'name' => 'Item 3', 'group' => 'B', 'count' => 8],
            ],
        ];

        $this->assertSame($expected, Arr::group($data, fn($item) => $item['group']));

        // Test with Stringable values
        $this->assertSame($expected, Arr::group($data, fn($item) => new StringableFixture($item['group'])));
    }

    public function testCollapseWithArrayableObjects(): void
    {
        $nested = [
            'a',
            new ArrayableFixture(['b', 'c']),
            new TraversableFixture([new ArrayableFixture(['d', 'e']), 'f']),
        ];

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], Arr::flatten($nested));
    }

    public function testFlattenRejectsNegativeDepths(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('expects a non-negative depth');
        Arr::flatten([], depth: -1);
    }

    public function testSortIsNaturalAndAscendingByDefault(): void
    {
        $items = ['second', 'first', 'third'];

        $this->assertSame([1 => 'first', 0 => 'second', 2 => 'third'], Arr::sort($items));

        $this->assertSame([2 => 'third', 0 => 'second', 1 => 'first'], Arr::sort($items, direction: SORT_DESC));

        $this->assertSame(['first', 'second', 'third'], Arr::sort($items, preserveKeys: false));

        $this->assertSame(['third', 'second', 'first'], Arr::sort($items, direction: SORT_DESC, preserveKeys: false));
    }

    public function testSortCanUseAComparisonCallback(): void
    {
        $expected = [
            ['id' => 2, 'name' => 'Item 2', 'group' => 'A', 'count' => 3],
            ['id' => 1, 'name' => 'Item 1', 'group' => 'A', 'count' => 5],
            ['id' => 3, 'name' => 'Item 3', 'group' => 'B', 'count' => 8],
        ];

        $data = [
            ['id' => 1, 'name' => 'Item 1', 'group' => 'A', 'count' => 5],
            ['id' => 2, 'name' => 'Item 2', 'group' => 'A', 'count' => 3],
            ['id' => 3, 'name' => 'Item 3', 'group' => 'B', 'count' => 8],
        ];

        $this->assertSame($expected, Arr::sort($data, sortBy: fn($a, $b) => $a['count'] <=> $b['count'], preserveKeys: false));
    }

    public function testSortWithCallableHonorsTheDirection(): void
    {
        $data = ['b' => 2, 'c' => 3, 'a' => 1];
        $callback = static fn(int $a, int $b): int => $a <=> $b;

        $this->assertSame([3, 2, 1], Arr::sort([2, 3, 1], SORT_DESC, sortBy: $callback, preserveKeys: false));
        $this->assertSame(['c' => 3, 'b' => 2, 'a' => 1], Arr::sort($data, SORT_DESC, sortBy: $callback));
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], Arr::sort($data, SORT_ASC, sortBy: $callback));
    }

    public function testSortWithOrderArray(): void
    {
        $expected = [
            'roles'       => ['admin', 'editor'],
            'permissions' => ['pages', 'files'],
            'cache'       => [
                'enabled'  => true,
                'duration' => 3600,
            ],
            'notifications' => true,
            'theme'         => 'dark',
        ];

        $data = [
            'theme'         => 'dark',
            'notifications' => true,
            'roles'         => ['admin', 'editor'],
            'permissions'   => ['pages', 'files'],
            'cache'         => [
                'enabled'  => true,
                'duration' => 3600,
            ],
        ];

        $this->assertSame($expected, Arr::sort($data, sortBy: ['roles' => 0, 'permissions' => 1, 'cache' => 2, 'notifications' => 3, 'theme' => 4]));
    }

    public function testSortWithOrderWithoutPreservingKeys(): void
    {
        $data = [
            'theme'         => 'dark',
            'notifications' => true,
            'roles'         => ['admin', 'editor'],
            'permissions'   => ['pages', 'files'],
            'cache'         => [
                'enabled'  => true,
                'duration' => 3600,
            ],
        ];

        $expected = [
            ['admin', 'editor'],
            ['pages', 'files'],
            [
                'enabled'  => true,
                'duration' => 3600,
            ],
            true,
            'dark',
        ];

        /** @var array<array-key, mixed> $actual */
        $actual = Arr::sort($data, sortBy: ['roles' => 0, 'permissions' => 1, 'cache' => 2, 'notifications' => 3, 'theme' => 4], preserveKeys: false);
        $this->assertSame($expected, $actual);
    }

    public function testSortRejectsInvalidDirections(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('only accepts SORT_ASC and SORT_DESC as "direction" option');
        Arr::sort([], direction: $this->unchecked(123));
    }

    public function testSortRejectsInvalidTypes(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('only accepts SORT_REGULAR, SORT_NUMERIC, SORT_STRING and SORT_NATURAL as "type" option');
        Arr::sort([], type: $this->unchecked(123));
    }

    public function testSortRejectsInvalidSortByCounts(): void
    {
        $data = [
            'app' => [
                'theme'         => 'dark',
                'notifications' => true,
                'roles'         => ['admin', 'editor'],
                'permissions'   => ['pages', 'files'],
                'cache'         => [
                    'enabled'  => true,
                    'duration' => 3600,
                ],
            ],
        ];

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot sort array: the $sortBy array must have the same number of items as the array to sort');
        Arr::sort($data, sortBy: []);
    }

    public function testSortRejectsMissingSortByKeys(): void
    {
        $data = [
            'theme'         => 'dark',
            'notifications' => true,
            'roles'         => ['admin', 'editor'],
            'permissions'   => ['pages', 'files'],
            'cache'         => [
                'enabled'  => true,
                'duration' => 3600,
            ],
        ];

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot sort array: key "test" from the $sortBy array is not present in the array to sort');
        Arr::sort($data, sortBy: ['roles' => 0, 'permissions' => 1, 'cache' => 2, 'notifications' => 3, 'test' => 4]);
    }

    public function testSortIsCaseInsensitiveByDefault(): void
    {
        $this->assertSame(['A', 'a', 'b', 'B', 'c'], array_values(Arr::sort(['b', 'A', 'c', 'B', 'a'])));
    }

    public function testSortCanBeCaseSensitive(): void
    {
        $this->assertSame(['A', 'B', 'a', 'b', 'c'], array_values(Arr::sort(['b', 'A', 'c', 'B', 'a'], caseSensitive: true)));
    }

    public function testSortCanBeDescendingAndPreservesKeysByDefault(): void
    {
        $this->assertSame([2 => 'c', 0 => 'b', 1 => 'a'], Arr::sort(['b', 'a', 'c'], SORT_DESC));
        $this->assertSame(['c', 'b', 'a'], Arr::sort(['b', 'a', 'c'], SORT_DESC, preserveKeys: false));
    }

    public function testToArrayAcceptsArraysAndArrayableObjects(): void
    {
        $user = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $items = [
            'first',
            'second',
            'third',
            'second',
        ];

        $this->assertSame($user, Arr::from($user));
        $this->assertSame($items, Arr::from(new TraversableFixture($items)));
        $this->assertSame($user, Arr::from(new ArrayableFixture($user)));
    }

    public function testFromRejectsInvalidTypes(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot convert to array an object of type string');
        Arr::from('invalid_value');
    }

    public function testFromEntriesBuildsAnArrayFromPairs(): void
    {
        $entries = [
            ['name', 'Test User'],
            ['email', 'user@example.test'],
            ['country', 'Italy'],
        ];

        $expected = [
            'name'    => 'Test User',
            'email'   => 'user@example.test',
            'country' => 'Italy',
        ];

        $this->assertSame($expected, Arr::fromEntries($entries));
    }

    public function testLiteralDottedKeysTakePrecedenceOverNestedPaths(): void
    {
        /** @var array<string, mixed> $array */
        $array = [
            'user.name' => 'literal',
            'user'      => ['name' => 'nested'],
        ];

        $this->assertSame('literal', Arr::get($array, 'user.name'));
        $this->assertTrue(Arr::has($array, 'user.name'));

        Arr::set($array, 'user.name', 'changed');
        $this->assertSame('changed', $array['user.name']);
        $this->assertSame('nested', $array['user']['name']);

        Arr::remove($array, 'user.name');
        $this->assertSame(['name' => 'nested'], $array['user']);
    }

    public function testDotAndUndotRoundTripNestedAssociativeArrays(): void
    {
        $source = [
            'user' => [
                'name'    => 'User 1',
                'profile' => ['city' => 'Bologna'],
            ],
            'tags' => ['php', 'cms'],
        ];

        $this->assertSame($source, Arr::undot(Arr::dot($source)));
    }

    public function testDotDoesNotFlattenLists(): void
    {
        $source = ['items' => [['id' => 1], ['id' => 2]]];

        $this->assertSame($source, Arr::undot(Arr::dot($source)));
        $this->assertSame(['items' => $source['items']], Arr::dot($source));
    }

    public function testHasFindsLiteralDottedKeys(): void
    {
        $this->assertTrue(Arr::has(['a.b' => null], 'a.b'));
    }

    public function testRemovingAMissingNestedKeyLeavesTheArrayUntouched(): void
    {
        $data = ['a' => 1];

        Arr::remove($data, 'missing.key');

        $this->assertSame(['a' => 1], $data);
    }

    /**
     * @param array<string, int> $removed
     * @param array<string, int> $expected
     */
    #[DataProvider('negativeLengthProvider')]
    public function testSpliceWithNegativeLengthFromAPositiveOffset(int $offset, int $length, array $removed, array $expected): void
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];

        $this->assertSame($removed, Arr::splice($data, $offset, $length, ['x' => 9]));
        $this->assertSame($expected, $data);
    }

    /**
     * @return iterable<string, array{int, int, array<string, int>, array<string, int>}>
     */
    public static function negativeLengthProvider(): iterable
    {
        yield 'some items' => [1, -1, ['b' => 2, 'c' => 3], ['a' => 1, 'x' => 9, 'd' => 4]];
        yield 'no items' => [3, -2, [], ['a' => 1, 'b' => 2, 'c' => 3, 'x' => 9, 'd' => 4]];
    }

    public function testMoveItemBackwards(): void
    {
        $data = ['a', 'b', 'c'];

        Arr::moveItem($data, 2, 0);

        $this->assertSame(['c', 'a', 'b'], $data);
    }

    public function testNthIgnoresTheKeys(): void
    {
        $this->assertSame(6, Arr::nth(['x' => 5, 'y' => 6], 1));
    }

    public function testAppendMissingSkipsValuesThatCannotBeMergedBeforeRecursing(): void
    {
        $this->assertSame(
            ['scalar' => 1, 'missing' => [], 'replaced' => [], 'nested' => ['a' => 1, 'b' => 2]],
            Arr::appendMissing(
                ['scalar' => 1, 'missing' => [], 'replaced' => [], 'nested' => ['a' => 1]],
                ['scalar' => 2, 'replaced' => 'text', 'nested' => ['b' => 2]],
            ),
        );
    }

    public function testShuffleChangesTheOrderOfTheValues(): void
    {
        mt_srand(42);

        $shuffled = Arr::shuffle(range(1, 20));
        mt_srand();

        $this->assertNotSame(range(1, 20), $shuffled);
        $this->assertEqualsCanonicalizing(range(1, 20), $shuffled);
    }

    public function testShufflePreservingKeysChangesTheOrderOfTheKeys(): void
    {
        $data = array_combine(range('a', 't'), range(1, 20));
        mt_srand(42);

        $shuffled = Arr::shuffle($data, preserveKeys: true);
        mt_srand();

        $this->assertNotSame(array_keys($data), array_keys($shuffled));
        $this->assertEqualsCanonicalizing($data, $shuffled);
        foreach ($shuffled as $key => $value) {
            $this->assertSame($data[$key], $value);
        }
    }

    public function testSortByAnotherArray(): void
    {
        $this->assertSame([1 => 'b', 2 => 'c', 0 => 'a'], Arr::sort(['a', 'b', 'c'], sortBy: [3, 1, 2]));
    }

    /**
     * @param array<mixed>       $array
     * @param list<array<mixed>> $others
     * @param array<mixed>       $expected
     */
    #[DataProvider('extendProvider')]
    public function testExtendMergesTheGivenArrays(array $array, array $others, array $expected): void
    {
        $this->assertSame($expected, Arr::extend($array, ...$others));
    }

    /**
     * @return iterable<string, array{array<mixed>, list<array<mixed>>, array<mixed>}>
     */
    public static function extendProvider(): iterable
    {
        yield 'merges nested associative arrays' => [['app' => ['name' => 'Formwork', 'debug' => false], 'cache' => ['enabled' => true]], [['app' => ['version' => '2.0', 'debug' => true], 'database' => ['host' => 'localhost']]], ['app' => ['name' => 'Formwork', 'debug' => true, 'version' => '2.0'], 'cache' => ['enabled' => true], 'database' => ['host' => 'localhost']]];
        yield 'list concatenation' => [['roles' => ['admin', 'editor'], 'permissions' => ['read', 'write']], [['roles' => ['user', 'guest']]], ['roles' => ['admin', 'editor', 'user', 'guest'], 'permissions' => ['read', 'write']]];
        yield 'deeply nested' => [['config' => ['system' => ['cache' => ['enabled' => true, 'lifetime' => 3600], 'features' => ['search', 'backup']]]], [['config' => ['system' => ['cache' => ['lifetime' => 7200, 'driver' => 'file'], 'features' => ['images'], 'logging' => true]]]], ['config' => ['system' => ['cache' => ['enabled' => true, 'lifetime' => 7200, 'driver' => 'file'], 'features' => ['search', 'backup', 'images'], 'logging' => true]]]];
        yield 'multiple arrays' => [['a' => 1, 'b' => ['x' => 10]], [['b' => ['y' => 20], 'c' => 3], ['a' => 2, 'd' => 4]], ['a' => 2, 'b' => ['x' => 10, 'y' => 20], 'c' => 3, 'd' => 4]];
        yield 'empty base' => [[], [['a' => 1]], ['a' => 1]];
        yield 'empty addition' => [['a' => 1], [[]], ['a' => 1]];
        yield 'both empty' => [[], [[]], []];
        yield 'mixed types' => [['scalar_to_array' => 'value', 'array_to_scalar' => ['a', 'b'], 'keep_scalar' => 42], [['scalar_to_array' => ['new' => 'array'], 'array_to_scalar' => 'string', 'keep_scalar' => 100]], ['scalar_to_array' => ['new' => 'array'], 'array_to_scalar' => 'string', 'keep_scalar' => 100]];
        yield 'does not concatenate a list with an associative array' => [['a' => [1, 2]], [['a' => [1 => 'z']]], ['a' => [1, 'z']]];
    }

    /**
     * @param array<mixed>       $array
     * @param list<array<mixed>> $others
     * @param array<mixed>       $expected
     */
    #[DataProvider('overrideProvider')]
    public function testOverrideReplacesWithTheGivenArrays(array $array, array $others, array $expected): void
    {
        $this->assertSame($expected, Arr::override($array, ...$others));
    }

    /**
     * @return iterable<string, array{array<mixed>, list<array<mixed>>, array<mixed>}>
     */
    public static function overrideProvider(): iterable
    {
        yield 'replaces nested values' => [['app' => ['name' => 'Formwork', 'debug' => false], 'cache' => ['enabled' => true]], [['app' => ['debug' => true], 'database' => ['host' => 'localhost']]], ['app' => ['name' => 'Formwork', 'debug' => true], 'cache' => ['enabled' => true], 'database' => ['host' => 'localhost']]];
        yield 'replaces lists instead of merging them' => [['roles' => ['admin', 'editor'], 'permissions' => ['read', 'write', 'delete']], [['roles' => ['user', 'guest']]], ['roles' => ['user', 'guest'], 'permissions' => ['read', 'write', 'delete']]];
        yield 'deeply nested' => [['config' => ['system' => ['features' => ['search', 'backup'], 'cache' => ['enabled' => true, 'lifetime' => 3600, 'stores' => ['file', 'redis']]]]], [['config' => ['system' => ['features' => ['images'], 'cache' => ['lifetime' => 7200, 'stores' => ['memcached']]]]]], ['config' => ['system' => ['features' => ['images'], 'cache' => ['enabled' => true, 'lifetime' => 7200, 'stores' => ['memcached']]]]]];
        yield 'multiple arrays' => [['a' => 1, 'b' => ['x' => 10, 'y' => [1, 2, 3]]], [['b' => ['y' => [4, 5]], 'c' => 3], ['a' => 2, 'd' => 4]], ['a' => 2, 'b' => ['x' => 10, 'y' => [4, 5]], 'c' => 3, 'd' => 4]];
        yield 'empty base' => [[], [['a' => 1]], ['a' => 1]];
        yield 'empty addition' => [['a' => 1], [[]], ['a' => 1]];
        yield 'both empty' => [[], [[]], []];
        yield 'replacing list with empty list' => [['items' => [1, 2, 3], 'other' => 'value'], [['items' => []]], ['items' => [], 'other' => 'value']];
        yield 'mixed types' => [['scalar_to_array' => 'value', 'array_to_scalar' => ['a', 'b'], 'list_to_assoc' => [1, 2, 3], 'assoc_to_list' => ['x' => 1, 'y' => 2]], [['scalar_to_array' => ['new' => 'array'], 'array_to_scalar' => 'string', 'list_to_assoc' => ['a' => 1], 'assoc_to_list' => [1, 2]]], ['scalar_to_array' => ['new' => 'array'], 'array_to_scalar' => 'string', 'list_to_assoc' => ['a' => 1], 'assoc_to_list' => [1, 2]]];
    }

    /**
     * @param array<mixed>       $array
     * @param list<array<mixed>> $others
     * @param array<mixed>       $expected
     */
    #[DataProvider('excludeProvider')]
    public function testExcludeRemovesTheGivenArrays(array $array, array $others, array $expected): void
    {
        $this->assertSame($expected, Arr::exclude($array, ...$others));
    }

    /**
     * @return iterable<string, array{array<mixed>, list<array<mixed>>, array<mixed>}>
     */
    public static function excludeProvider(): iterable
    {
        yield 'removes matching keys and values' => [['name' => 'Formwork', 'version' => '2.0', 'debug' => true, 'cache' => ['enabled' => true, 'lifetime' => 3600]], [['debug' => true, 'cache' => ['lifetime' => 3600]]], ['name' => 'Formwork', 'version' => '2.0', 'cache' => ['enabled' => true]]];
        yield 'removing empty arrays' => [['settings' => ['cache' => ['enabled' => true]], 'other' => 'value'], [['settings' => ['cache' => ['enabled' => true]]]], ['other' => 'value']];
        yield 'deeply nested' => [['config' => ['system' => ['cache' => ['enabled' => true, 'lifetime' => 3600, 'driver' => 'file'], 'features' => ['search' => true, 'backup' => false]], 'other' => 'preserved']], [['config' => ['system' => ['cache' => ['lifetime' => 3600], 'features' => ['backup' => false]]]]], ['config' => ['system' => ['cache' => ['enabled' => true, 'driver' => 'file'], 'features' => ['search' => true]], 'other' => 'preserved']]];
        yield 'multiple arrays' => [['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], [['a' => 1], ['c' => 3]], ['b' => 2, 'd' => 4]];
        yield 'preserving non matching values' => [['name' => 'Formwork', 'debug' => true, 'cache' => ['enabled' => true, 'lifetime' => 3600]], [['debug' => false, 'cache' => ['lifetime' => 7200]]], ['name' => 'Formwork', 'debug' => true, 'cache' => ['enabled' => true, 'lifetime' => 3600]]];
        yield 'empty array' => [['a' => 1, 'b' => 2], [[]], ['a' => 1, 'b' => 2]];
        yield 'non existent keys' => [['a' => 1, 'b' => 2], [['c' => 3, 'd' => 4]], ['a' => 1, 'b' => 2]];
        yield 'mixed types' => [['scalar' => 'value', 'number' => 42, 'nested' => ['a' => 1, 'b' => 2], 'preserve_me' => 'keep'], [['scalar' => 'value', 'nested' => ['a' => 1]]], ['number' => 42, 'nested' => ['b' => 2], 'preserve_me' => 'keep']];
        yield 'does not match scalar with array' => [['value' => 'scalar'], [['value' => ['array']]], ['value' => 'scalar']];
        yield 'removes empty parents after recursive exclusion' => [['config' => ['a' => 1, 'b' => 2], 'keep' => true], [['config' => ['a' => 1, 'b' => 2]]], ['keep' => true]];
        yield 'continues after missing keys' => [['a' => 1, 'b' => 2], [['missing' => 1, 'b' => 2]], ['a' => 1]];
    }
}
