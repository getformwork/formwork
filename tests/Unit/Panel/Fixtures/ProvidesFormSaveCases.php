<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

/**
 * Cases shared by the controllers that persist model forms (only overrides are saved and arrays are replaced)
 */
trait ProvidesFormSaveCases
{
    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>, array<string, mixed>}>
     *
     * Each case is: persisted data, submitted data, expected persisted data
     */
    public static function formSaveProvider(): iterable
    {
        yield 'scalar override' => [
            [],
            ['title' => 'Hello'],
            ['title' => 'Hello'],
        ];

        yield 'scalar changed' => [
            ['title' => 'Hello'],
            ['title' => 'World'],
            ['title' => 'World'],
        ];

        yield 'scalar equal to default is not saved' => [
            ['title' => 'Hello'],
            ['title' => 'untitled'],
            [],
        ];

        yield 'keyed array key removed' => [
            ['metadata' => ['en' => 'a', 'de' => 'b']],
            ['metadata' => ['en' => 'a']],
            ['metadata' => ['en' => 'a']],
        ];

        yield 'keyed array keys replaced' => [
            ['metadata' => ['en' => 'a', 'de' => 'b']],
            ['metadata' => ['fr' => 'c']],
            ['metadata' => ['fr' => 'c']],
        ];

        yield 'keyed array cleared' => [
            ['metadata' => ['en' => 'a']],
            ['metadata' => []],
            [],
        ];

        yield 'list replaced' => [
            ['tags' => ['a', 'b', 'c']],
            ['tags' => ['a']],
            ['tags' => ['a']],
        ];

        yield 'list cleared' => [
            ['tags' => ['a', 'b']],
            ['tags' => []],
            [],
        ];

        yield 'nested array replaced' => [
            ['nested' => ['a' => ['x' => 1, 'y' => 2], 'b' => 3]],
            ['nested' => ['a' => ['x' => 1]]],
            ['nested' => ['a' => ['x' => 1]]],
        ];

        yield 'nested array keys swapped' => [
            ['nested' => ['a' => ['x' => 1], 'b' => 3]],
            ['nested' => ['c' => ['z' => 9]]],
            ['nested' => ['c' => ['z' => 9]]],
        ];

        yield 'unrelated fields are retained' => [
            ['title' => 'Hello', 'metadata' => ['en' => 'a', 'de' => 'b'], 'tags' => ['a']],
            ['title' => 'Hello', 'metadata' => ['en' => 'a'], 'tags' => ['a']],
            ['title' => 'Hello', 'metadata' => ['en' => 'a'], 'tags' => ['a']],
        ];

        yield 'unknown existing data is retained' => [
            ['custom' => 'value', 'metadata' => ['en' => 'a', 'de' => 'b']],
            ['metadata' => ['en' => 'a']],
            ['custom' => 'value', 'metadata' => ['en' => 'a']],
        ];

        yield 'dotted keyed array replaced and sibling retained' => [
            ['appearance' => ['metadata' => ['en' => 'a', 'de' => 'b'], 'mode' => 'dark']],
            ['appearance.metadata' => ['en' => 'a'], 'appearance.mode' => 'dark'],
            ['appearance' => ['metadata' => ['en' => 'a'], 'mode' => 'dark']],
        ];

        yield 'dotted keyed array cleared and sibling retained' => [
            ['appearance' => ['metadata' => ['en' => 'a'], 'mode' => 'dark']],
            ['appearance.metadata' => [], 'appearance.mode' => 'dark'],
            ['appearance' => ['mode' => 'dark']],
        ];

        yield 'dotted scalar override' => [
            [],
            ['appearance.mode' => 'dark'],
            ['appearance' => ['mode' => 'dark']],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function formSaveFields(): array
    {
        return [
            'title'               => ['type' => 'text', 'default' => 'untitled'],
            'tags'                => ['type' => 'array'],
            'metadata'            => ['type' => 'array', 'associative' => true],
            'nested'              => ['type' => 'array', 'associative' => true],
            'appearance.metadata' => ['type' => 'array', 'associative' => true],
            'appearance.mode'     => ['type' => 'text'],
        ];
    }

    /**
     * Defaults matching the fields of `formSaveFields()`
     *
     * @return array<string, mixed>
     */
    protected static function formSaveDefaults(): array
    {
        return [
            'title'      => 'untitled',
            'tags'       => [],
            'metadata'   => [],
            'nested'     => [],
            'appearance' => ['metadata' => []],
        ];
    }
}
