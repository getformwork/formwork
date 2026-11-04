<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Pages\PageCollection;
use Formwork\Pages\PaginationFactory;
use Formwork\Panel\Controllers\DashboardController;
use Formwork\Panel\Controllers\StatisticsController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\Modals;
use Formwork\Parsers\Json;
use Formwork\Parsers\Yaml;
use Formwork\Router\Router;
use Formwork\Statistics\Statistics;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Translations\Translation;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use Formwork\Utils\FileSystem;
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

    public function testDashboardIsRenderedWithTheLastModifiedPages(): void
    {
        $modal = $this->createMock(Modal::class);
        $modal->expects($this->once())->method('setFieldsModel');

        $modals = $this->createStub(Modals::class);
        $modals->method('get')->willReturn($modal);

        $site = $this->createStub(Site::class);
        $site->method('descendants')->willReturn(new PageCollection([], new PaginationFactory(App::instance(), $this->createStub(Router::class))));

        $controller = $this->makeController(DashboardController::class, ['panel.dashboard' => true], services: [Modals::class => $modals, Site::class => $site]);

        $response = $controller->index($this->seededStatistics());
        $view = $this->renderedView($response);

        $this->assertSame(ResponseStatus::OK, $response->status());
        $this->assertSame('@panel.dashboard.index', $view['view']);
        $this->assertSame('Dashboard', $view['vars']['title']);
        $this->assertSame('@panel.pages.tree', $this->renderedView(new Response($view['vars']['lastModifiedPages']))['view']);
        $this->assertCount(2, Json::parse($view['vars']['statistics'])['series']);
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

    public function testStatisticsAreRenderedWithTheirTotals(): void
    {
        $statistics = $this->seededStatistics();

        $view = $this->renderedView($this->makeController(StatisticsController::class, ['panel.statistics' => true])->index($statistics));

        $this->assertSame('@panel.statistics.index', $view['view']);
        $this->assertSame(['/blog/' => 7, '/' => 3], $view['vars']['pageViews']);
        $this->assertSame(10, $view['vars']['totalViews']);
        $this->assertSame(['example.com' => 4], $view['vars']['sources']);
        $this->assertSame(4, $view['vars']['totalSources']);
        $this->assertSame(['Desktop' => 2], $view['vars']['devices']);
        $this->assertSame(2, $view['vars']['totalDevices']);
    }

    public function testStatisticsLimitTheListsToFifteenItems(): void
    {
        $pageViews = [];
        foreach (range(1, 20) as $i) {
            $pageViews["/page-{$i}/"] = $i;
        }

        $statistics = $this->seededStatistics(['pageViews' => $pageViews]);

        $view = $this->renderedView($this->makeController(StatisticsController::class, ['panel.statistics' => true])->index($statistics));

        $this->assertCount(15, $view['vars']['pageViews']);
        $this->assertSame(210, $view['vars']['totalViews']);
        $this->assertSame('/page-20/', array_key_first($view['vars']['pageViews']));
    }

    public function testStatisticsCountOnlyTheVisitsOfTheLastDays(): void
    {
        $today = date('Ymd');
        $tenDaysAgo = date('Ymd', strtotime('-10 days'));

        $statistics = $this->seededStatistics([
            'visits'       => [$today => 5, $tenDaysAgo => 3],
            'uniqueVisits' => [$today => 2, $tenDaysAgo => 1],
        ]);

        $vars = $this->renderedView($this->makeController(StatisticsController::class, ['panel.statistics' => true])->index($statistics))['vars'];

        $this->assertSame(8, $vars['monthVisits']);
        $this->assertSame(5, $vars['weekVisits']);
        $this->assertSame(3, $vars['monthUniqueVisits']);
        $this->assertSame(2, $vars['weekUniqueVisits']);
    }

    private function statistics(): Statistics
    {
        return (new ReflectionClass(Statistics::class))->newInstanceWithoutConstructor();
    }

    /**
     * @param array<string, array<int|string, int>> $registries
     */
    private function seededStatistics(array $registries = []): Statistics
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
            new Request([], [], [], [], []),
            new Translation('en', Yaml::parseFile(TestInstallation::systemPath() . '/translations/en.yaml')),
        );
    }
}
