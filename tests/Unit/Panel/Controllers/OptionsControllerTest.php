<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Http\RedirectResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Panel\Controllers\OptionsController;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\ProvidesFormSaveCases;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

#[CoversClass(OptionsController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class OptionsControllerTest extends TestCase
{
    use BuildsPanelControllers;
    use ProvidesFormSaveCases;

    protected function tearDown(): void
    {
        $this->closePanelSession();
        parent::tearDown();
    }

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
            $controller = $this->makeController(OptionsController::class);
            $reflectionMethod = new ReflectionMethod($controller, 'updateOptions');

            $reflectionMethod->invoke($controller, 'site', $submitted, $existing, self::formSaveDefaults());

            $this->assertSame($expected, Yaml::parseFile($path));
        } finally {
            Yaml::encodeToFile($original, $path);
            $this->closePanelSession();
        }
    }

    public function testIndexRedirectsToTheSiteOptions(): void
    {
        $response = $this->makeController(OptionsController::class, ['panel.options.site' => true])->index();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.options.site', $response->headers()->get('Location'));
    }

    public function testIndexRequiresTheSiteOptionsPermission(): void
    {
        $response = $this->makeController(OptionsController::class, ['panel.options.site' => false, 'panel.options.system' => true])->index();

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testSiteOptionsRequireThePermission(): void
    {
        $response = $this->makeController(OptionsController::class, ['panel.options.site' => false, 'panel.options.system' => true])->siteOptions($this->schemes());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testSystemOptionsRequireThePermission(): void
    {
        $response = $this->makeController(OptionsController::class, ['panel.options.site' => true, 'panel.options.system' => false])->systemOptions($this->schemes());

        $this->assertSame(ResponseStatus::Forbidden, $response->status());
    }

    public function testInvalidSiteOptionsAreNotSaved(): void
    {
        $path = App::instance()->rootPath() . '/site/config/site.yaml';
        $original = Yaml::parseFile($path);

        try {
            $controller = $this->makeController(OptionsController::class, ['panel.options.site' => true], input: ['title' => ''], server: ['REQUEST_METHOD' => 'POST']);

            $response = $controller->siteOptions($this->schemes());

            $this->assertSame(ResponseStatus::UnprocessableEntity, $response->status());
            $this->assertSame($original, Yaml::parseFile($path));
            $this->assertSame('error', $this->lastNotificationType());
        } finally {
            Yaml::encodeToFile($original, $path);
            $this->closePanelSession();
        }
    }

    public function testValidSiteOptionsAreSavedAndInvalidateTheCache(): void
    {
        $path = App::instance()->rootPath() . '/site/config/site.yaml';
        $original = Yaml::parseFile($path);

        $content = TESTS_TMP_PATH . '/content';
        FileSystem::createDirectory($content);
        touch($content, 1000);

        $site = $this->createStub(Site::class);
        $site->method('data')->willReturn(['title' => 'Old title']);
        $site->method('contentPath')->willReturn($content);

        try {
            $controller = $this->makeController(OptionsController::class, ['panel.options.site' => true], input: ['title' => 'New title'], services: [Site::class => $site], server: ['REQUEST_METHOD' => 'POST']);

            $response = $controller->siteOptions($this->schemes());

            $this->assertInstanceOf(RedirectResponse::class, $response, $response->content());
            $this->assertStringEndsWith('/route/panel.options.site', $response->headers()->get('Location'));
            $this->assertSame('New title', Yaml::parseFile($path)['title']);
            $this->assertSame('success', $this->lastNotificationType());

            clearstatcache();
            $this->assertGreaterThan(1000, filemtime($content));
        } finally {
            Yaml::encodeToFile($original, $path);
            $this->closePanelSession();
        }
    }

    public function testUnchangedSiteOptionsDoNotInvalidateTheCache(): void
    {
        $path = App::instance()->rootPath() . '/site/config/site.yaml';
        $original = Yaml::parseFile($path);

        $content = TESTS_TMP_PATH . '/content';
        FileSystem::createDirectory($content);
        touch($content, 1000);

        $site = $this->createStub(Site::class);
        $site->method('data')->willReturn([]);
        $site->method('contentPath')->willReturn($content);

        try {
            $controller = $this->makeController(OptionsController::class, ['panel.options.site' => true], input: [], services: [Site::class => $site], server: ['REQUEST_METHOD' => 'POST']);

            $controller->siteOptions($this->schemes());

            clearstatcache();
            $this->assertSame(1000, filemtime($content));
        } finally {
            Yaml::encodeToFile($original, $path);
            $this->closePanelSession();
        }
    }

    public function testFormsAreDisplayed(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('data')->willReturn(['title' => 'My site']);

        $siteOptions = $this->makeController(OptionsController::class, ['panel.options.site' => true], services: [Site::class => $site], server: ['REQUEST_METHOD' => 'GET'])->siteOptions($this->schemes());

        $this->assertOutcome(['status' => 200], $siteOptions);

        $systemOptions = $this->makeController(OptionsController::class, ['panel.options.system' => true], server: ['REQUEST_METHOD' => 'GET'])->systemOptions($this->schemes());

        $this->assertOutcome(['status' => 200], $systemOptions);
    }

    private function schemes(): Schemes
    {
        return App::instance()->getService(Schemes::class);
    }

    private function lastNotificationType(): ?string
    {
        $messages = $this->panelRequest?->session()->messages()->getAll() ?? [];

        return array_key_last($messages);
    }
}
