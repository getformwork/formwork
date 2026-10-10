<?php

namespace Formwork\Tests\Unit\Panel\Views;

use Formwork\Cms\App;
use Formwork\Tests\TestCase;
use Formwork\Users\Permissions;
use Formwork\Utils\FileSystem;
use Formwork\View\View;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Renders the header of the panel layout with a hostile site title
 */
#[CoversNothing]
final class PanelHeaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    #[DataProvider('permissionProvider')]
    public function testSiteTitleIsEscapedInThePanelHeader(bool $canEditSite): void
    {
        $title = '</span><script>alert(1)</script><img src=x onerror=alert(2)>';

        $document = $this->document($this->renderHeader($title, $canEditSite));
        $xpath = new \DOMXPath($document);

        $this->assertSame(0, $xpath->query('//script')->length, 'Script injected');
        $this->assertSame(0, $xpath->query('//img')->length, 'Image injected');
        $this->assertSame(0, $xpath->query('//@*[starts-with(name(), "on")]')->length, 'Event handler injected');
        $this->assertStringContainsString($title, (string) $document->textContent);
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function permissionProvider(): iterable
    {
        yield 'with the permission to edit the site options' => [true];
        yield 'without the permission to edit the site options' => [false];
    }

    public function testSiteTitleIsRenderedAsText(): void
    {
        $document = $this->document($this->renderHeader('Tom & "Jerry"', true));

        $this->assertStringContainsString('Tom & "Jerry"', (string) $document->textContent);
    }

    public function testOptionsLinkIsOnlyRenderedWithThePermission(): void
    {
        $with = $this->document($this->renderHeader('Site', true));
        $without = $this->document($this->renderHeader('Site', false));

        $this->assertSame(1, (new \DOMXPath($with))->query('//a[@href="/panel/options/site/"]')->length);
        $this->assertSame(0, (new \DOMXPath($without))->query('//a[@href="/panel/options/site/"]')->length);
    }

    private function renderHeader(string $siteTitle, bool $canEditSite): string
    {
        $layout = FileSystem::read(ROOT_PATH . '/panel/views/layouts/panel.php');

        $this->assertSame(1, preg_match('#<header class="panel-header">.*?</header>#s', $layout, $matches), 'Header not found in the panel layout');

        $views = FileSystem::joinPaths(TESTS_TMP_PATH, 'views');
        if (!FileSystem::isDirectory($views, assertExists: false)) {
            FileSystem::createDirectory($views);
        }
        FileSystem::write(FileSystem::joinPaths($views, 'header.php'), $matches[0]);

        $user = new class ($canEditSite) {
            public function __construct(private bool $canEditSite) {}

            public function permissions(): Permissions
            {
                return new Permissions(['panel.options.site' => $this->canEditSite]);
            }
        };

        $panel = new class ($user) {
            public function __construct(private object $user) {}

            public function user(): object
            {
                return $this->user;
            }

            public function uri(string $path): string
            {
                return '/panel' . $path;
            }
        };

        $site = new class ($siteTitle) {
            public function __construct(private string $title) {}

            public function title(): string
            {
                return $this->title;
            }

            public function uri(): string
            {
                return '/';
            }
        };

        $app = App::instance();

        $view = new View('header', ['panel' => $panel, 'site' => $site, 'app' => $app], $views, [
            ...(require SYSTEM_PATH . '/config/views/methods.php')($app),
            'translate' => static fn(string $key): string => $key,
            'icon'      => static fn(string $icon): string => '',
        ]);

        return $view->render();
    }

    private function document(string $html): \DOMDocument
    {
        $document = new \DOMDocument();
        $document->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NOERROR);

        return $document;
    }
}
