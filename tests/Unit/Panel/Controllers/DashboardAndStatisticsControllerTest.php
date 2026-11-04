<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\DashboardController;
use Formwork\Panel\Controllers\StatisticsController;
use Formwork\Statistics\Statistics;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;

#[CoversClass(DashboardController::class)]
#[CoversClass(StatisticsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class DashboardAndStatisticsControllerTest extends TestCase
{
    use BuildsPanelControllers;

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

    public function testDashboardRequiresThePermission(): void
    {
        $response = $this->makeController(DashboardController::class, ['panel.dashboard' => false])->index($this->statistics());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUsersWithoutDashboardAccessAreLoggedOut(): void
    {
        $user = $this->createMock(User::class);
        $user->method('permissions')->willReturn(new Permissions(['panel.dashboard' => false]));
        $user->expects($this->once())->method('logout');

        $response = $this->makeController(DashboardController::class, [], user: $user)->index($this->statistics());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testStatisticsRequireThePermission(): void
    {
        $response = $this->makeController(StatisticsController::class, ['panel.statistics' => false])->index($this->statistics());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testDashboardPermissionDoesNotGrantStatistics(): void
    {
        $response = $this->makeController(StatisticsController::class, ['panel.dashboard' => true, 'panel.statistics' => false])->index($this->statistics());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    private function statistics(): Statistics
    {
        return (new ReflectionClass(Statistics::class))->newInstanceWithoutConstructor();
    }
}
