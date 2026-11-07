<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Http\RedirectResponse;
use Formwork\Http\Request;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\ToolsController;
use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionMethod;

#[CoversClass(ToolsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class ToolsControllerTest extends TestCase
{
    use BuildsPanelControllers;

    protected function setUp(): void
    {
        parent::setUp();

        $root = App::instance()->rootPath();

        FileSystem::write($root . '/composer.json', Json::encode([
            'require' => ['php' => '>=8.3', 'ext-mbstring' => '*', 'ext-zip' => '*', 'league/commonmark' => '^2.4'],
        ]));
        FileSystem::write($root . '/composer.lock', Json::encode([
            'packages' => [['name' => 'league/commonmark', 'version' => '2.4.0']],
        ]));
    }

    protected function tearDown(): void
    {
        $root = App::instance()->rootPath();

        FileSystem::delete($root . '/composer.json');
        FileSystem::delete($root . '/composer.lock');

        $this->closePanelSession();
        parent::tearDown();
    }

    public function testIndexRedirectsToTheBackups(): void
    {
        $response = $this->controller(['panel.tools.backups' => true])->index();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.tools.backups', $response->headers()->get('Location'));
    }

    public function testIndexRequiresTheBackupsPermission(): void
    {
        $response = $this->controller(['panel.tools.backups' => false, 'panel.tools.updates' => true])->index();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testBackupsRequireThePermission(): void
    {
        $backupper = (new ReflectionClass(Backupper::class))->newInstanceWithoutConstructor();

        $response = $this->controller(['panel.tools.backups' => false])->backups($backupper);

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testUpdatesRequireThePermission(): void
    {
        $response = $this->controller(['panel.tools.updates' => false, 'panel.tools.backups' => true])->updates();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testInfoRequiresThePermission(): void
    {
        $response = $this->controller(['panel.tools.info' => false, 'panel.tools.backups' => true, 'panel.tools.updates' => true])->info();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testRequiredDependenciesExcludePhpAndExtensions(): void
    {
        $dependencies = $this->invoke('getRequiredDependencies');

        $this->assertContains('league/commonmark', $dependencies);
        $this->assertNotContains('php', $dependencies);

        foreach ($dependencies as $dependency) {
            $this->assertStringStartsNotWith('ext-', $dependency);
        }
    }

    public function testRequiredExtensionsAreListedWithoutPrefix(): void
    {
        $extensions = $this->invoke('getRequiredExtensions');

        $this->assertContains('mbstring', $extensions);
        $this->assertContains('zip', $extensions);

        foreach ($extensions as $extension) {
            $this->assertStringStartsNotWith('ext-', $extension);
        }
    }

    public function testInstalledDependenciesAreIndexedByName(): void
    {
        $dependencies = $this->invoke('getDependencies');

        $this->assertArrayHasKey('league/commonmark', $dependencies);
        $this->assertSame('2.4.0', $dependencies['league/commonmark']['version']);
    }

    public function testResponseHeadersAreIndexedByName(): void
    {
        $headers = $this->invoke('getHeaders');

        $this->assertIsArray($headers);

        foreach ($headers as $name => $value) {
            $this->assertIsString($name);
            $this->assertSame(trim($value), $value);
        }
    }

    public function testPagesAreDisplayed(): void
    {
        $path = TESTS_TMP_PATH . '/backups';
        FileSystem::createDirectory($path);
        FileSystem::write($path . '/backup.zip', 'zip');

        $backupper = new Backupper(new Request(), ['path' => $path, 'name' => 'backup', 'maxExecutionTime' => 60, 'maxFiles' => 10, 'ignore' => []], App::instance()->rootPath(), App::instance());

        $responses = [
            $this->controller(['panel.tools.updates' => true])->updates(),
            $this->controller(['panel.tools.backups' => true])->backups($backupper),
            $this->controller(['panel.tools.info' => true])->info(),
            $this->makeController(ToolsController::class, ['panel.tools.info' => true], ['debug' => ['enabled' => true]])->info(),
        ];

        foreach ($responses as $response) {
            $this->assertOutcome(['status' => 200], $response);
        }
    }

    /**
     * @param array<string, bool> $permissions
     */
    private function controller(array $permissions): ToolsController
    {
        return $this->makeController(ToolsController::class, $permissions);
    }

    private function invoke(string $method): mixed
    {
        $controller = $this->controller([]);

        return (new ReflectionMethod($controller, $method))->invoke($controller);
    }
}
