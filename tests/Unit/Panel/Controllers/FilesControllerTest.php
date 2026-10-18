<?php

namespace Formwork\Tests\Unit\Panel\Controllers;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Fields\FieldFactory;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Files\FileFactory;
use Formwork\Files\Services\FileUploader;
use Formwork\Http\RedirectResponse;
use Formwork\Pages\Page;
use Formwork\Panel\Controllers\FilesController;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Schemes\Scheme;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Panel\Fixtures\BuildsPanelControllers;
use Formwork\Tests\Unit\Panel\Fixtures\ProvidesFormSaveCases;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FilesController::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class FilesControllerTest extends TestCase
{
    use BuildsPanelControllers;
    use ProvidesFormSaveCases;

    private string $filePath;

    private string $metadataPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filePath = TESTS_TMP_PATH . '/sample.txt';
        $this->metadataPath = $this->filePath . '.meta.yaml';
        FileSystem::write($this->filePath, 'hello');
    }

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
    public function testEditSavesOnlyOverridesAndReplacesArrays(array $existing, array $submitted, array $expected): void
    {
        $app = App::instance();

        $file = new File($this->filePath);
        $file->setScheme(new Scheme(
            'files.test',
            ['fields' => self::formSaveFields()],
            $app->getService(Translations::class),
            $app->getService(Schemes::class),
            $app->getService(FieldFactory::class),
        ));
        $file->setMultiple($existing);
        $file->save();

        $this->assertSame($existing, $this->savedMetadata());

        $page = $this->createStub(Page::class);
        $page->method('files')->willReturn(new FileCollection([$file]));
        $page->method('contentFile')->willReturn(null);

        $site = $this->createStub(Site::class);
        $site->method('findPage')->willReturn($page);

        $filesController = $this->makeController(
            FilesController::class,
            ['panel.files.edit' => true],
            input: ['title' => 'untitled', ...$submitted],
            services: [Site::class => $site, FileUploader::class => $this->createStub(FileUploader::class), FileFactory::class => $app->getService(FileFactory::class)],
        );

        $response = $filesController->edit(new RouteParams(['model' => 'page', 'id' => '/about/', 'filename' => 'sample.txt']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expected, $this->savedMetadata());
    }

    /**
     * @return array<string, mixed>
     */
    private function savedMetadata(): array
    {
        return FileSystem::exists($this->metadataPath) ? Yaml::parseFile($this->metadataPath) : [];
    }
}
