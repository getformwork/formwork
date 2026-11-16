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
use Formwork\Utils\Arr;
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
        $response = $this->makeController(OptionsController::class)->index();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/route/panel.options.site', $response->headers()->get('Location'));
    }

    public function testInvalidSiteOptionsAreNotSaved(): void
    {
        $path = App::instance()->rootPath() . '/site/config/site.yaml';
        $original = Yaml::parseFile($path);

        try {
            $controller = $this->makeController(OptionsController::class, input: ['title' => ''], server: ['REQUEST_METHOD' => 'POST']);

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
            $controller = $this->makeController(OptionsController::class, input: ['title' => 'New title'], services: [Site::class => $site], server: ['REQUEST_METHOD' => 'POST']);

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

    public function testInterpolationSequencesInSiteOptionsAreSavedEscaped(): void
    {
        $path = App::instance()->rootPath() . '/site/config/site.yaml';
        $original = Yaml::parseFile($path);

        $content = TESTS_TMP_PATH . '/content';
        FileSystem::createDirectory($content);

        $site = $this->createStub(Site::class);
        $site->method('data')->willReturn(['title' => 'Old title']);
        $site->method('contentPath')->willReturn($content);

        try {
            $controller = $this->makeController(OptionsController::class, input: ['title' => 'Costs ${price}'], services: [Site::class => $site], server: ['REQUEST_METHOD' => 'POST']);

            $controller->siteOptions($this->schemes());

            $this->assertSame('Costs \${price}', Yaml::parseFile($path)['title']);
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
            $controller = $this->makeController(OptionsController::class, input: [], services: [Site::class => $site], server: ['REQUEST_METHOD' => 'POST']);

            $controller->siteOptions($this->schemes());

            clearstatcache();
            $this->assertSame(1000, filemtime($content));
        } finally {
            Yaml::encodeToFile($original, $path);
            $this->closePanelSession();
        }
    }

    public function testInvalidSystemOptionsAreNotSaved(): void
    {
        $path = App::instance()->rootPath() . '/site/config/system.yaml';
        $original = Yaml::parseFile($path);

        try {
            $controller = $this->makeController(OptionsController::class, input: $this->systemInput(['pages.index' => '']), server: ['REQUEST_METHOD' => 'POST']);

            $response = $controller->systemOptions($this->schemes());

            $this->assertSame(ResponseStatus::UnprocessableEntity, $response->status());
            $this->assertSame($original, Yaml::parseFile($path));
            $this->assertSame('error', $this->lastNotificationType());
        } finally {
            Yaml::encodeToFile($original, $path);
            $this->closePanelSession();
        }
    }

    public function testValidSystemOptionsAreSavedAndInvalidateTheCache(): void
    {
        $path = App::instance()->rootPath() . '/site/config/system.yaml';
        $original = Yaml::parseFile($path);

        // The real site is used, as its content path is touched
        $content = (string) App::instance()->site()->contentPath();
        $modified = (int) filemtime($content);
        touch($content, 1000);

        try {
            $controller = $this->makeController(OptionsController::class, input: $this->systemInput(['backup.maxFiles' => 15]), services: [Site::class => App::instance()->site()], server: ['REQUEST_METHOD' => 'POST']);

            $response = $controller->systemOptions($this->schemes());

            $this->assertInstanceOf(RedirectResponse::class, $response, $response->content());
            $this->assertStringEndsWith('/route/panel.options.system', $response->headers()->get('Location'));
            $this->assertSame(15, Yaml::parseFile($path)['backup']['maxFiles']);
            $this->assertSame('success', $this->lastNotificationType());

            clearstatcache();
            $this->assertGreaterThan(1000, filemtime($content));
        } finally {
            Yaml::encodeToFile($original, $path);
            touch($content, $modified);
            $this->closePanelSession();
        }
    }

    public function testFormsAreDisplayed(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('data')->willReturn(['title' => 'My site']);

        $siteOptions = $this->makeController(OptionsController::class, services: [Site::class => $site], server: ['REQUEST_METHOD' => 'GET'])->siteOptions($this->schemes());

        $this->assertOutcome(['status' => 200], $siteOptions);

        $systemOptions = $this->makeController(OptionsController::class, server: ['REQUEST_METHOD' => 'GET'])->systemOptions($this->schemes());

        $this->assertOutcome(['status' => 200], $systemOptions);
    }

    /**
     * Get the input of the system options form, with the current values of its fields
     *
     * @param array<string, mixed> $overrides Values by field name
     *
     * @return array<string, mixed>
     */
    private function systemInput(array $overrides): array
    {
        $fields = $this->schemes()->get('config.system')->fields();
        $fields->setValues(App::instance()->config()->getArray('system'));

        $input = [];

        foreach ($fields as $field) {
            if (!$field->isVisible()) {
                continue;
            }

            $value = $overrides[$field->name()] ?? $field->value();

            Arr::set($input, $field->name(), is_bool($value) ? ($value ? '1' : '0') : $value);
        }

        return $input;
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
