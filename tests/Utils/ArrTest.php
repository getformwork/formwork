<?php

namespace Formwork\Tests\Utils;

use Formwork\Tests\TestCase;
use Formwork\Tests\Utils\Fixtures\ArrayableFixture;
use Formwork\Tests\Utils\Fixtures\StringableFixture;
use Formwork\Tests\Utils\Fixtures\TraversableFixture;
use Formwork\Utils\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(Arr::class)]
final class ArrTest extends TestCase
{
    public function testGet(): void
    {
        $data = [
            'user' => [
                'name'    => 'Sempronius',
                'email'   => 'sempronius@example.com',
                'country' => 'Italy',
            ],
            'date' => '2025-03-29',
        ];

        $this->assertSame('2025-03-29', Arr::get($data, 'date'));
        $this->assertSame('Sempronius', Arr::get($data, 'user.name'));
        $this->assertNull(Arr::get($data, 'user.age'));
    }

    public function testHas(): void
    {
        $data = [
            'user' => [
                'name'    => 'Sempronius',
                'email'   => 'sempronius@example.com',
                'country' => 'Italy',
            ],
            'date' => '2025-03-29',
        ];

        $this->assertTrue(Arr::has($data, 'date'));
        $this->assertTrue(Arr::has($data, 'user.email'));
        $this->assertFalse(Arr::has($data, 'user.age'));
    }

    public function testSet(): void
    {
        $data = [
            'user' => [
                'name'    => 'Sempronius',
                'email'   => 'sempronius@example.com',
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

    public function testRemove(): void
    {
        $data = [
            'user' => [
                'name'    => 'Sempronius',
                'email'   => 'sempronius@example.com',
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

    public function testFlatten(): void
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

        $this->assertSame($expected, Arr::dot($data));
    }

    public function testExpand(): void
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

        $this->assertSame($data, Arr::undot($dot));
    }

    public function testPull(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        Arr::pull($data, 'banana');
        $this->assertNotContains('banana', $data);
    }

    public function testSplice(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $removed = Arr::splice($data, 1, 2, ['orange', 'grapes']);

        $this->assertSame(['banana', 'cherry'], $removed);
        $this->assertSame(['apple', 'orange', 'grapes', 'banana'], $data);
    }

    public function testSpliceWithAssociativeArray(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $removed = Arr::splice($data, 1, 1, ['age' => 30]);

        $this->assertSame(['email' => 'sempronius@example.com'], $removed);
        $this->assertSame(['name' => 'Sempronius', 'age' => 30, 'country' => 'Italy'], $data);
    }

    public function testSpliceWithNegativeOffset(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $removed = Arr::splice($data, -2, 1, ['age' => 30]);

        $this->assertSame(['email' => 'sempronius@example.com'], $removed);
        $this->assertSame(['name' => 'Sempronius', 'age' => 30, 'country' => 'Italy'], $data);
    }

    public function testSpliceWithNegativeLength(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $removed = Arr::splice($data, 0, -1, ['age' => 30]);

        $this->assertSame(['name' => 'Sempronius', 'email' => 'sempronius@example.com'], $removed);
        $this->assertSame(['age' => 30, 'country' => 'Italy'], $data);
    }

    public function testSpliceThrowsOnDuplicateKeys(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot replace 1 items from offset 1: some keys in the replacement array are the same of the resulting array');
        Arr::splice($data, 1, 1, ['country' => 'Canada']);
    }

    public function testMove(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        Arr::moveItem($data, 0, 2);
        $this->assertSame(['banana', 'cherry', 'apple', 'banana'], $data);
    }

    public function testEntries(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $expected = [
            ['name', 'Sempronius'],
            ['email', 'sempronius@example.com'],
            ['country', 'Italy'],
        ];

        $this->assertSame($expected, Arr::entries($data));
    }

    public function testNth(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $this->assertSame('cherry', Arr::nth($data, 2));
        $this->assertNull(Arr::nth($data, 5));
    }

    public function testAt(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $this->assertSame('cherry', Arr::at($data, 2));
        $this->assertNull(Arr::at($data, 5));

        $this->assertSame('banana', Arr::at($data, -1));
        $this->assertNull(Arr::at($data, -5));
    }

    public function testIndex(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $this->assertSame(1, Arr::indexOf($data, 'banana'));
        $this->assertNull(Arr::indexOf($data, 'orange'));
    }

    public function testKey(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $this->assertSame('email', Arr::keyOf($data, 'sempronius@example.com'));
        $this->assertNull(Arr::keyOf($data, 'Brazil'));
    }

    public function testDuplicates(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $this->assertSame([3 => 'banana'], Arr::duplicates($data));
    }

    public function testAppendMissing(): void
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

    public function testExtend(): void
    {
        // Test basic array extension with associative arrays
        $base = [
            'app' => [
                'name'  => 'Formwork',
                'debug' => false,
            ],
            'cache' => [
                'enabled' => true,
            ],
        ];

        $extension = [
            'app' => [
                'version' => '2.0',
                'debug'   => true,
            ],
            'database' => [
                'host' => 'localhost',
            ],
        ];

        $expected = [
            'app' => [
                'name'    => 'Formwork',
                'debug'   => true,
                'version' => '2.0',
            ],
            'cache' => [
                'enabled' => true,
            ],
            'database' => [
                'host' => 'localhost',
            ],
        ];

        $result = Arr::extend($base, $extension);
        $this->assertSame($expected, $result);
    }

    public function testExtendWithListConcatenation(): void
    {
        // Test that lists are concatenated instead of merged element-by-element
        $base = [
            'roles'       => ['admin', 'editor'],
            'permissions' => ['read', 'write'],
        ];

        $extension = [
            'roles' => ['user', 'guest'],
        ];

        $expected = [
            'roles'       => ['admin', 'editor', 'user', 'guest'],
            'permissions' => ['read', 'write'],
        ];

        $result = Arr::extend($base, $extension);
        $this->assertSame($expected, $result);
    }

    public function testExtendWithDeeplyNested(): void
    {
        // Test deeply nested structures
        $base = [
            'config' => [
                'system' => [
                    'cache' => [
                        'enabled'  => true,
                        'lifetime' => 3600,
                    ],
                    'features' => ['search', 'backup'],
                ],
            ],
        ];

        $extension = [
            'config' => [
                'system' => [
                    'cache' => [
                        'lifetime' => 7200,
                        'driver'   => 'file',
                    ],
                    'features' => ['images'],
                    'logging'  => true,
                ],
            ],
        ];

        $expected = [
            'config' => [
                'system' => [
                    'cache' => [
                        'enabled'  => true,
                        'lifetime' => 7200,
                        'driver'   => 'file',
                    ],
                    'features' => ['search', 'backup', 'images'],
                    'logging'  => true,
                ],
            ],
        ];

        $result = Arr::extend($base, $extension);
        $this->assertSame($expected, $result);
    }

    public function testExtendWithMultipleArrays(): void
    {
        // Test extending with multiple arrays
        $base = [
            'a' => 1,
            'b' => ['x' => 10],
        ];

        $ext1 = [
            'b' => ['y' => 20],
            'c' => 3,
        ];

        $ext2 = [
            'a' => 2,
            'd' => 4,
        ];

        $expected = [
            'a' => 2,
            'b' => ['x' => 10, 'y' => 20],
            'c' => 3,
            'd' => 4,
        ];

        $result = Arr::extend($base, $ext1, $ext2);
        $this->assertSame($expected, $result);
    }

    public function testExtendWithEmptyArrays(): void
    {
        // Test extending empty arrays
        $result = Arr::extend([], ['a' => 1]);
        $this->assertSame(['a' => 1], $result);

        $result = Arr::extend(['a' => 1], []);
        $this->assertSame(['a' => 1], $result);

        $result = Arr::extend([], []);
        $this->assertSame([], $result);
    }

    public function testExtendWithMixedTypes(): void
    {
        // Test that scalar values override arrays and vice versa
        $base = [
            'scalar_to_array' => 'value',
            'array_to_scalar' => ['a', 'b'],
            'keep_scalar'     => 42,
        ];

        $extension = [
            'scalar_to_array' => ['new' => 'array'],
            'array_to_scalar' => 'string',
            'keep_scalar'     => 100,
        ];

        $expected = [
            'scalar_to_array' => ['new' => 'array'],
            'array_to_scalar' => 'string',
            'keep_scalar'     => 100,
        ];

        $result = Arr::extend($base, $extension);
        $this->assertSame($expected, $result);
    }

    public function testOverride(): void
    {
        // Test basic override functionality
        $base = [
            'app' => [
                'name'  => 'Formwork',
                'debug' => false,
            ],
            'cache' => [
                'enabled' => true,
            ],
        ];

        $override = [
            'app' => [
                'debug' => true,
            ],
            'database' => [
                'host' => 'localhost',
            ],
        ];

        $expected = [
            'app' => [
                'name'  => 'Formwork',
                'debug' => true,
            ],
            'cache' => [
                'enabled' => true,
            ],
            'database' => [
                'host' => 'localhost',
            ],
        ];

        $result = Arr::override($base, $override);
        $this->assertSame($expected, $result);
    }

    public function testOverrideReplacingLists(): void
    {
        // Test that lists are completely replaced, not merged
        $base = [
            'roles'       => ['admin', 'editor'],
            'permissions' => ['read', 'write', 'delete'],
        ];

        $override = [
            'roles' => ['user', 'guest'],
        ];

        $expected = [
            'roles'       => ['user', 'guest'],
            'permissions' => ['read', 'write', 'delete'],
        ];

        $result = Arr::override($base, $override);
        $this->assertSame($expected, $result);
    }

    public function testOverrideWithDeeplyNested(): void
    {
        // Test deeply nested structures with lists
        $base = [
            'config' => [
                'system' => [
                    'features' => ['search', 'backup'],
                    'cache'    => [
                        'enabled'  => true,
                        'lifetime' => 3600,
                        'stores'   => ['file', 'redis'],
                    ],
                ],
            ],
        ];

        $override = [
            'config' => [
                'system' => [
                    'features' => ['images'],
                    'cache'    => [
                        'lifetime' => 7200,
                        'stores'   => ['memcached'],
                    ],
                ],
            ],
        ];

        $expected = [
            'config' => [
                'system' => [
                    'features' => ['images'],
                    'cache'    => [
                        'enabled'  => true,
                        'lifetime' => 7200,
                        'stores'   => ['memcached'],
                    ],
                ],
            ],
        ];

        $result = Arr::override($base, $override);
        $this->assertSame($expected, $result);
    }

    public function testOverrideWithMultipleArrays(): void
    {
        // Test overriding with multiple arrays
        $base = [
            'a' => 1,
            'b' => ['x' => 10, 'y' => [1, 2, 3]],
        ];

        $override1 = [
            'b' => ['y' => [4, 5]],
            'c' => 3,
        ];

        $override2 = [
            'a' => 2,
            'd' => 4,
        ];

        $expected = [
            'a' => 2,
            'b' => ['x' => 10, 'y' => [4, 5]],
            'c' => 3,
            'd' => 4,
        ];

        $result = Arr::override($base, $override1, $override2);
        $this->assertSame($expected, $result);
    }

    public function testOverrideWithEmptyArrays(): void
    {
        // Test overriding with empty arrays
        $result = Arr::override([], ['a' => 1]);
        $this->assertSame(['a' => 1], $result);

        $result = Arr::override(['a' => 1], []);
        $this->assertSame(['a' => 1], $result);

        $result = Arr::override([], []);
        $this->assertSame([], $result);
    }

    public function testOverrideReplacingListWithEmptyList(): void
    {
        // Test replacing a list with an empty list
        $base = [
            'items' => [1, 2, 3],
            'other' => 'value',
        ];

        $override = [
            'items' => [],
        ];

        $expected = [
            'items' => [],
            'other' => 'value',
        ];

        $result = Arr::override($base, $override);
        $this->assertSame($expected, $result);
    }

    public function testOverrideWithMixedTypes(): void
    {
        // Test that mixed types are properly replaced
        $base = [
            'scalar_to_array' => 'value',
            'array_to_scalar' => ['a', 'b'],
            'list_to_assoc'   => [1, 2, 3],
            'assoc_to_list'   => ['x' => 1, 'y' => 2],
        ];

        $override = [
            'scalar_to_array' => ['new' => 'array'],
            'array_to_scalar' => 'string',
            'list_to_assoc'   => ['a' => 1],
            'assoc_to_list'   => [1, 2],
        ];

        $expected = [
            'scalar_to_array' => ['new' => 'array'],
            'array_to_scalar' => 'string',
            'list_to_assoc'   => ['a' => 1],
            'assoc_to_list'   => [1, 2],
        ];

        $result = Arr::override($base, $override);
        $this->assertSame($expected, $result);
    }

    public function testExclude(): void
    {
        // Test basic exclusion functionality
        $array = [
            'name'    => 'Formwork',
            'version' => '2.0',
            'debug'   => true,
            'cache'   => [
                'enabled'  => true,
                'lifetime' => 3600,
            ],
        ];

        $exclusion = [
            'debug' => true,
            'cache' => [
                'lifetime' => 3600,
            ],
        ];

        $expected = [
            'name'    => 'Formwork',
            'version' => '2.0',
            'cache'   => [
                'enabled' => true,
            ],
        ];

        $result = Arr::exclude($array, $exclusion);
        $this->assertSame($expected, $result);
    }

    public function testExcludeRemovingEmptyArrays(): void
    {
        // Test that empty arrays resulting from recursion are removed
        $array = [
            'settings' => [
                'cache' => [
                    'enabled' => true,
                ],
            ],
            'other' => 'value',
        ];

        $exclusion = [
            'settings' => [
                'cache' => [
                    'enabled' => true,
                ],
            ],
        ];

        $expected = [
            'other' => 'value',
        ];

        $result = Arr::exclude($array, $exclusion);
        $this->assertSame($expected, $result);
    }

    public function testExcludeWithDeeplyNested(): void
    {
        // Test deeply nested exclusions
        $array = [
            'config' => [
                'system' => [
                    'cache' => [
                        'enabled'  => true,
                        'lifetime' => 3600,
                        'driver'   => 'file',
                    ],
                    'features' => [
                        'search' => true,
                        'backup' => false,
                    ],
                ],
                'other' => 'preserved',
            ],
        ];

        $exclusion = [
            'config' => [
                'system' => [
                    'cache' => [
                        'lifetime' => 3600,
                    ],
                    'features' => [
                        'backup' => false,
                    ],
                ],
            ],
        ];

        $expected = [
            'config' => [
                'system' => [
                    'cache' => [
                        'enabled' => true,
                        'driver'  => 'file',
                    ],
                    'features' => [
                        'search' => true,
                    ],
                ],
                'other' => 'preserved',
            ],
        ];

        $result = Arr::exclude($array, $exclusion);
        $this->assertSame($expected, $result);
    }

    public function testExcludeWithMultipleArrays(): void
    {
        // Test excluding with multiple exclusion arrays
        $array = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ];

        $exc1 = ['a' => 1];
        $exc2 = ['c' => 3];

        $expected = [
            'b' => 2,
            'd' => 4,
        ];

        $result = Arr::exclude($array, $exc1, $exc2);
        $this->assertSame($expected, $result);
    }

    public function testExcludePreservingNonMatchingValues(): void
    {
        // Test that non-matching values are preserved
        $array = [
            'name'  => 'Formwork',
            'debug' => true,
            'cache' => [
                'enabled'  => true,
                'lifetime' => 3600,
            ],
        ];

        $exclusion = [
            'debug' => false,
            'cache' => [
                'lifetime' => 7200,
            ],
        ];

        $expected = [
            'name'  => 'Formwork',
            'debug' => true,
            'cache' => [
                'enabled'  => true,
                'lifetime' => 3600,
            ],
        ];

        $result = Arr::exclude($array, $exclusion);
        $this->assertSame($expected, $result);
    }

    public function testExcludeWithEmptyArray(): void
    {
        // Test with empty exclusion array
        $array = [
            'a' => 1,
            'b' => 2,
        ];

        $result = Arr::exclude($array, []);
        $this->assertSame($array, $result);
    }

    public function testExcludeAllValues(): void
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

    public function testExcludeWithNonExistentKeys(): void
    {
        // Test excluding keys that don't exist in the array
        $array = [
            'a' => 1,
            'b' => 2,
        ];

        $exclusion = [
            'c' => 3,
            'd' => 4,
        ];

        $expected = [
            'a' => 1,
            'b' => 2,
        ];

        $result = Arr::exclude($array, $exclusion);
        $this->assertSame($expected, $result);
    }

    public function testExcludeWithMixedTypes(): void
    {
        // Test excluding with mixed scalar and array values
        $array = [
            'scalar' => 'value',
            'number' => 42,
            'nested' => [
                'a' => 1,
                'b' => 2,
            ],
            'preserve_me' => 'keep',
        ];

        $exclusion = [
            'scalar' => 'value',
            'nested' => [
                'a' => 1,
            ],
        ];

        $expected = [
            'number' => 42,
            'nested' => [
                'b' => 2,
            ],
            'preserve_me' => 'keep',
        ];

        $result = Arr::exclude($array, $exclusion);
        $this->assertSame($expected, $result);
    }

    public function testExcludeDoesNotMatchScalarWithArray(): void
    {
        // Test that array exclusions don't match scalar values
        $array = [
            'value' => 'scalar',
        ];

        $exclusion = [
            'value' => ['array'],
        ];

        $expected = [
            'value' => 'scalar',
        ];

        $result = Arr::exclude($array, $exclusion);
        $this->assertSame($expected, $result);
    }

    public function testRandom(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $this->assertContains(Arr::random($data), $data);
        $this->assertNull(Arr::random([]));
    }

    public function testShuffle(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $shuffled = Arr::shuffle($data);

        $this->assertSameSize($data, $shuffled);

        foreach ($shuffled as $fruit) {
            $this->assertContains($fruit, $data);
        }

        $this->assertSame(['test'], Arr::shuffle(['test']));
    }

    public function testShufflePreservingKeys(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $shuffled = Arr::shuffle($data, preserveKeys: true);
        $this->assertSameSize($data, $shuffled);

        foreach ($shuffled as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            $this->assertSame($data[$key], $value);
        }
    }

    public function testIsAssociative(): void
    {
        $user = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $fruits = [
            'apple',
            'banana',
            'cherry',
            'banana',
        ];

        $this->assertTrue(Arr::isAssociative($user));
        $this->assertFalse(Arr::isAssociative($fruits));
        $this->assertFalse(Arr::isAssociative([]));
    }

    public function testMap(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $expected = [
            'name'    => 'SEMPRONIUS',
            'email'   => 'SEMPRONIUS@EXAMPLE.COM',
            'country' => 'ITALY',
        ];

        $this->assertSame($expected, Arr::map($data, fn($data) => strtoupper($data)));
    }

    public function testMapKeys(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $expected = [
            'USER_NAME'    => 'Sempronius',
            'USER_EMAIL'   => 'sempronius@example.com',
            'USER_COUNTRY' => 'Italy',
        ];

        $this->assertSame($expected, Arr::mapKeys($data, fn($key) => 'USER_' . strtoupper($key)));
    }

    public function testFilter(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $expected = [
            'name'  => 'Sempronius',
            'email' => 'sempronius@example.com',
        ];

        $this->assertSame($expected, Arr::filter($data, fn($value, $key) => $value !== 'Italy'));
    }

    public function testReject(): void
    {
        $data = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $expected = [
            'email' => 'sempronius@example.com',
        ];

        $this->assertSame($expected, Arr::reject($data, fn($value, $key) => !str_contains($value, '@')));
    }

    public function testEvery(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $this->assertTrue(Arr::every($data, fn($value) => is_string($value)));
        $this->assertFalse(Arr::every($data, fn($value) => $value === 'banana'));
    }

    public function testSome(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $this->assertTrue(Arr::some($data, fn($value) => $value === 'banana'));
        $this->assertFalse(Arr::some($data, fn($value) => $value === 'orange'));
    }

    public function testFind(): void
    {
        $data = ['apple', 'banana', 'cherry',  'banana'];

        $this->assertSame('banana', Arr::find($data, fn($value) => $value === 'banana'));
        $this->assertNull(Arr::find($data, fn($value) => $value === 'orange'));
    }

    public function testPluck(): void
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

    public function testGroupBy(): void
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

    public function testCollapse(): void
    {
        $nested = [
            'a',
            ['b', 'c'],
            [['d', 'e'], 'f'],
        ];

        $this->assertSame($nested, Arr::flatten($nested, depth: 0));
        $this->assertSame(['a', 'b', 'c', ['d', 'e'], 'f'], Arr::flatten($nested, depth: 1));

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], Arr::flatten($nested));
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

    public function testFlattenThrowsOnNegativeDepth(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('expects a non-negative depth');
        Arr::flatten([], depth: -1);
    }

    public function testSort(): void
    {
        $fruits = ['banana', 'apple', 'cherry'];

        $this->assertSame([1 => 'apple', 0 => 'banana', 2 => 'cherry'], Arr::sort($fruits));

        $this->assertSame([2 => 'cherry', 0 => 'banana', 1 => 'apple'], Arr::sort($fruits, direction: SORT_DESC));

        $this->assertSame(['apple', 'banana', 'cherry'], Arr::sort($fruits, preserveKeys: false));

        $this->assertSame(['cherry', 'banana', 'apple'], Arr::sort($fruits, direction: SORT_DESC, preserveKeys: false));
    }

    public function testSortWithCallable(): void
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

        $this->assertSame($expected, Arr::sort($data, sortBy: ['roles' => 0, 'permissions' => 1, 'cache' => 2, 'notifications' => 3, 'theme' => 4], preserveKeys: false));
    }

    public function testSortThrowsOnInvalidDirection(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('only accepts SORT_ASC and SORT_DESC as "direction" option');
        Arr::sort([], direction: 123);
    }

    public function testSortThrowsOnInvalidType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('only accepts SORT_REGULAR, SORT_NUMERIC, SORT_STRING and SORT_NATURAL as "type" option');
        Arr::sort([], type: 123);
    }

    public function testSortThrowsOnInvalidSortByCount(): void
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

    public function testSortThrowsOnMissingSortByKey(): void
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

    public function testToArray(): void
    {
        $user = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $fruits = [
            'apple',
            'banana',
            'cherry',
            'banana',
        ];

        $this->assertSame($user, Arr::from($user));
        $this->assertSame($fruits, Arr::from(new TraversableFixture($fruits)));
        $this->assertSame($user, Arr::from(new ArrayableFixture($user)));
    }

    public function testFromThrowsOnInvalidType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot convert to array an object of type string');
        Arr::from('invalid_value');
    }

    public function testFromEntries(): void
    {
        $entries = [
            ['name', 'Sempronius'],
            ['email', 'sempronius@example.com'],
            ['country', 'Italy'],
        ];

        $expected = [
            'name'    => 'Sempronius',
            'email'   => 'sempronius@example.com',
            'country' => 'Italy',
        ];

        $this->assertSame($expected, Arr::fromEntries($entries));
    }
}
