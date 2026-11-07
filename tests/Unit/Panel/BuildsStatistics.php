<?php

namespace Formwork\Tests\Unit\Panel;

use Formwork\Http\Request;
use Formwork\Parsers\Json;
use Formwork\Parsers\Yaml;
use Formwork\Statistics\Statistics;
use Formwork\Tests\TestInstallation;
use Formwork\Translations\Translation;
use Formwork\Utils\FileSystem;
use ReflectionClass;

/**
 * Builds statistics, either empty or over seeded registries in the temporary directory
 */
trait BuildsStatistics
{
    protected function statistics(): Statistics
    {
        return (new ReflectionClass(Statistics::class))->newInstanceWithoutConstructor();
    }

    /**
     * @param array<string, array<int|string, int>> $registries
     */
    protected function seededStatistics(array $registries = []): Statistics
    {
        $registries += [
            'pageViews' => ['/' => 3, '/blog/' => 7],
            'sources'   => ['example.com' => 4],
            'devices'   => ['Desktop' => 2],
        ];

        $path = TESTS_TMP_PATH . '/statistics';
        $names = ['sessions', 'visits', 'uniqueVisits', 'visitors', 'pageViews', 'sources', 'devices'];

        FileSystem::createDirectory($path);

        foreach ($names as $name) {
            FileSystem::write("{$path}/{$name}.json", Json::encode($registries[$name] ?? []));
        }

        return new Statistics(
            ['path' => $path, 'registries' => array_combine($names, array_map(static fn(string $name): string => $name . '.json', $names))],
            new Request(),
            new Translation('en', Yaml::parseFile(TestInstallation::systemPath() . '/translations/en.yaml')),
        );
    }
}
