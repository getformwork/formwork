<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Panel\Controllers\StatisticsController;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\BuildsStatistics;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use PHPUnit\Framework\Attributes\CoversClass;

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
        $response = $this->makeController(StatisticsController::class, ['panel.statistics' => true])->index($this->seededStatistics());

        $this->assertOutcome(['status' => 200], $response);
    }
}
