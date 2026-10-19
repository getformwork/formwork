<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Fields\FieldFactory;
use Formwork\Http\RequestData;
use Formwork\Model\Model;
use Formwork\Pages\ContentFile;
use Formwork\Pages\Page;
use Formwork\Panel\Controllers\PagesController;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Scheme;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Pages\Fixtures\BuildsPageSites;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\Fixtures\ProvidesFormSaveCases;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Translations\Translations;
use Formwork\Users\Permissions;
use Formwork\Users\User;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(PagesController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class PagesControllerTest extends TestCase
{
    use BuildsPanelControllers;
    use ProvidesFormSaveCases;
    use BuildsPageSites;

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
    public function testUpdatePageSavesOnlyOverridesAndReplacesArrays(array $existing, array $submitted, array $expected): void
    {
        $app = App::instance();

        $site = $this->siteFromFixture();
        $pagePath = FileSystem::joinPaths((string) $site->contentPath(), 'about') . '/';
        $contentPath = $pagePath . 'page.md';
        FileSystem::write($contentPath, "---\n" . Yaml::encode(['title' => 'About', ...$existing]) . "---\nContent\n");

        $page = new Page(['site' => $site, 'path' => $pagePath], $app);

        $scheme = new Scheme(
            'pages.test',
            ['fields' => self::formSaveFields()],
            $app->getService(Translations::class),
            $app->getService(Schemes::class),
            $app->getService(FieldFactory::class),
        );
        (new ReflectionProperty(Model::class, 'scheme'))->setValue($page, $scheme);

        // Mimic the form, which submits every field
        $fields = $page->fields();
        $fields->setValues(['title' => 'untitled', ...$submitted], null);
        $this->assertTrue($fields->isValid());
        $formData = $fields->extract('value');

        $user = $this->createStub(User::class);
        $user->method('permissions')->willReturn(new Permissions([]));
        $user->method('__call')->willReturn('editor');

        $pagesController = $this->makeController(PagesController::class, [], user: $user);

        $reflectionMethod = new ReflectionMethod($pagesController, 'updatePage');
        $reflectionMethod->invoke($pagesController, $page, $formData, new RequestData(['slug' => 'about', 'language' => null]));

        $frontmatter = (new ContentFile($contentPath))->frontmatter();

        $saved = array_intersect_key($frontmatter, array_flip([...array_keys($existing), ...array_map(static fn(string $name): string => explode('.', $name)[0], array_keys(self::formSaveFields()))]));

        $this->assertSame($expected, $saved);
    }
}
