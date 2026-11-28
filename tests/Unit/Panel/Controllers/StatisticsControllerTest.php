<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Http\FileResponse;
use Formwork\Panel\Controllers\StatisticsController;
use Formwork\Router\RouteParams;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\BuildsStatistics;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\Attributes\Permission;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;
use ZipArchive;

#[CoversClass(StatisticsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class StatisticsControllerTest extends TestCase
{
    use BuildsPanelControllers;
    use BuildsStatistics;

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testStatisticsAreDisplayed(): void
    {
        $response = $this->makeController(StatisticsController::class)->index($this->seededStatistics());

        $this->assertOutcome(['status' => 200], $response);
    }

    public function testDownloadRequiresThePermission(): void
    {
        $attributes = (new ReflectionMethod(StatisticsController::class, 'download'))->getAttributes(Permission::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(['panel.statistics.download'], $attributes[0]->getArguments());
    }

    #[DataProvider('formatProvider')]
    public function testStatisticsAreDownloadedAsAnArchive(string $format, string $separator, ?string $requested = null): void
    {
        $response = $this->makeController(StatisticsController::class, site: ['statistics' => ['path' => TESTS_TMP_PATH . '/statistics']])->download($this->seededStatistics(), new RouteParams(['format' => $requested === 'default' ? null : $format]));

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertMatchesRegularExpression('/^attachment;.*statistics-\d{8}-\d{6}\.zip/', $response->headers()->get('Content-Disposition'));

        $file = (new ReflectionProperty(FileResponse::class, 'path'))->getValue($response);
        $zipArchive = new ZipArchive();
        $this->assertTrue($zipArchive->open($file));

        $names = [];
        for ($i = 0; $i < $zipArchive->numFiles; $i++) {
            $names[] = $zipArchive->getNameIndex($i);
        }
        sort($names);

        $this->assertSame(["devices.{$format}", "pageViews.{$format}", "sources.{$format}", "visits.{$format}"], $names);
        $this->assertSame(
            implode($separator, ['Page', 'Count']) . "\n" . implode($separator, ['/blog/', '7']) . "\n" . implode($separator, ['/', '3']) . "\n",
            $zipArchive->getFromName("pageViews.{$format}")
        );

        $zipArchive->close();
    }

    /**
     * @return iterable<string, array{string, string, string}|array{string, string}>
     */
    public static function formatProvider(): iterable
    {
        yield 'omitted' => ['tsv', "\t", 'default'];
        yield 'tsv' => ['tsv', "\t"];
        yield 'csv' => ['csv', ','];
    }
}
