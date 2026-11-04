<?php

namespace Formwork\Tests\Unit\Panel\Fixtures;

use Formwork\Cms\App;
use Formwork\Parsers\Json;
use Formwork\Tests\TestInstallation;
use Formwork\Utils\FileSystem;
use Formwork\View\ViewFactory;

/**
 * Replaces the panel views with views printing their name and variables, so that the controllers
 * can be tested for the view they choose and the data they pass, without depending on the markup
 */
final class FakeViews
{
    /**
     * Fake view, scalars and arrays are printed as they are (arrays also for arrayable objects), other objects as their type
     */
    private const string TEMPLATE = <<<'PHP'
        <?php
        $convert = static function (mixed $value) use (&$convert): mixed {
            if ($value instanceof \Formwork\Data\Contracts\Arrayable) {
                $value = $value->toArray();
            }
            if (is_array($value)) {
                return array_map($convert, $value);
            }
            return is_scalar($value) || $value === null ? $value : get_debug_type($value);
        };
        echo json_encode(['view' => $this->name(), 'vars' => $convert($_vars)]);
        PHP;

    /**
     * Create a view factory with a fake view for each view of the panel
     */
    public static function factory(string $path): ViewFactory
    {
        $source = TestInstallation::rootPath() . '/panel/views';

        foreach (FileSystem::listRecursive($source, FileSystem::LIST_FILES) as $file) {
            if (FileSystem::extension($file) !== 'php' || FileSystem::exists($path . '/' . $file)) {
                continue;
            }

            if (!FileSystem::isDirectory(dirname($path . '/' . $file), assertExists: false)) {
                FileSystem::createDirectory(dirname($path . '/' . $file), recursive: true);
            }

            FileSystem::write($path . '/' . $file, self::TEMPLATE);
        }

        return new ViewFactory([], ['panel' => $path], App::instance());
    }

    /**
     * Decode the content of a response rendered with fake views
     *
     * @return array{view: string, vars: array<string, mixed>}
     */
    public static function decode(string $content): array
    {
        /** @var array{view: string, vars: array<string, mixed>} */
        return Json::parse($content);
    }
}
