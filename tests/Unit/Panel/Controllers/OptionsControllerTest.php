<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Panel\Controllers\OptionsController;
use Formwork\Parsers\Yaml;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\Fixtures\ProvidesFormSaveCases;
use Formwork\Tests\UsesApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

#[CoversClass(OptionsController::class)]
#[UsesApplication]
final class OptionsControllerTest extends TestCase
{
    use BuildsPanelControllers;
    use ProvidesFormSaveCases;

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $submitted
     * @param array<string, mixed> $expected
     */
    #[DataProvider('formSaveProvider')]
    public function testUpdatingOptionsSavesOnlyOverridesAndReplacesArrays(array $existing, array $submitted, array $expected): void
    {
        $path = App::instance()->rootPath() . '/site/config/site.yaml';
        $original = Yaml::parseFile($path);

        try {
            $controller = $this->makeController(OptionsController::class, []);
            $reflectionMethod = new ReflectionMethod($controller, 'updateOptions');

            $reflectionMethod->invoke($controller, 'site', $submitted, $existing, self::formSaveDefaults());

            $this->assertSame($expected, Yaml::parseFile($path));
        } finally {
            Yaml::encodeToFile($original, $path);
            $this->closePanelSession();
        }
    }
}
