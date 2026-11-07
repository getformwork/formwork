<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PaginationFactory;
use Formwork\Panel\Controllers\DashboardController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\Modals;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\BuildsStatistics;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DashboardController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class DashboardControllerTest extends TestCase
{
    use BuildsPanelControllers;
    use BuildsStatistics;

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

        $response = $this->makeController(DashboardController::class, user: $user)->index($this->statistics());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testDashboardIsDisplayedWithTheLastModifiedPages(): void
    {
        $modal = $this->createMock(Modal::class);
        $modal->expects($this->once())->method('setFieldsModel');

        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturn($modal);

        $site = $this->createStub(Site::class);
        $site->method('descendants')->willReturn(new PageCollection([], new PaginationFactory(App::instance(), $this->createStub(Router::class))));

        $controller = $this->makeController(DashboardController::class, ['panel.dashboard' => true], services: [Modals::class => $modals, Site::class => $site]);

        $this->assertSame(ResponseStatus::OK, $controller->index($this->seededStatistics())->status());
    }
}
