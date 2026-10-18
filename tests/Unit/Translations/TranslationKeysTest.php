<?php

namespace Formwork\Tests\Unit\Translations;

use Formwork\Parsers\Yaml;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Check that the translation strings referenced by the code are defined
 */
#[CoversClass(Translation::class)]
final class TranslationKeysTest extends TestCase
{
    /**
     * Directories containing code that references translation strings
     */
    private const array SOURCE_DIRECTORIES = ['formwork/src', 'formwork/views', 'formwork/fields', 'panel/views'];

    /**
     * Files of the English translation strings, which are the reference for every other language
     */
    private const array TRANSLATION_FILES = ['formwork/translations/en.yaml', 'panel/translations/en.yaml'];

    /**
     * @return iterable<string, array{string}>
     */
    public static function referencedKeys(): iterable
    {
        $keys = [];

        foreach (self::SOURCE_DIRECTORIES as $directory) {
            foreach (FileSystem::listRecursive(self::path($directory)) as $file) {
                if (!str_ends_with($file, '.php')) {
                    continue;
                }

                $contents = FileSystem::read(FileSystem::joinPaths(self::path($directory), $file));

                preg_match_all('/->translate\(\s*[\'"]([A-Za-z0-9_.]+)[\'"]/', $contents, $matches);

                // Language strings of translated exceptions
                preg_match_all('/new TranslatedException\((?:[^;]*?),\s*\'([a-z][A-Za-z0-9_.]+)\'\s*[,)]/s', $contents, $exceptionMatches);

                foreach ([...$matches[1], ...$exceptionMatches[1]] as $key) {
                    // Keys ending with a dot are completed at runtime
                    if (!str_ends_with($key, '.')) {
                        $keys[$key] = [$key];
                    }
                }
            }
        }

        return $keys;
    }

    #[DataProvider('referencedKeys')]
    public function testReferencedKeysAreDefined(string $key): void
    {
        $this->assertArrayHasKey($key, self::definedKeys());
    }

    /**
     * Page errors are translated with the identifier of the exception thrown by the page
     *
     * @return iterable<string, array{string}>
     */
    public static function pageErrorKeys(): iterable
    {
        preg_match_all('/new InvalidValueException\([^;]*?,\s*\'([A-Za-z]+)\'\)/', FileSystem::read(self::path('formwork/src/Pages/Page.php')), $matches);

        foreach (array_unique($matches[1]) as $identifier) {
            foreach (['cannotCreate', 'cannotEdit'] as $action) {
                $key = "panel.pages.page.{$action}.{$identifier}";
                yield $key => [$key];
            }
        }
    }

    #[DataProvider('pageErrorKeys')]
    public function testPageErrorKeysAreDefined(string $key): void
    {
        $this->assertArrayHasKey($key, self::definedKeys());
    }

    /**
     * @return array<string, mixed>
     */
    private static function definedKeys(): array
    {
        static $keys;

        return $keys ??= array_merge(...array_map(
            static fn(string $file): array => Yaml::parseFile(self::path($file)),
            self::TRANSLATION_FILES
        ));
    }

    private static function path(string $path): string
    {
        return FileSystem::joinPaths(dirname(__DIR__, 3), $path);
    }
}
