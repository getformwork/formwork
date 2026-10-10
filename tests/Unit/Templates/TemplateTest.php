<?php

namespace Formwork\Tests\Unit\Templates;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Pages\Page;
use Formwork\Schemes\Scheme;
use Formwork\Templates\Template;
use Formwork\Tests\TestCase;
use Formwork\View\Exceptions\RenderingException;
use Formwork\View\ViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Template::class)]
final class TemplateTest extends TestCase
{
    private const string PATH = __DIR__ . '/Fixtures/templates';

    public function testIdentity(): void
    {
        $scheme = $this->createStub(Scheme::class);
        $scheme->method('title')->willReturn('Post');
        $template = $this->template('post', scheme: $scheme);

        $this->assertSame('post', $template->name());
        $this->assertSame('post', (string) $template);
        $this->assertSame(self::PATH, $template->path());
        $this->assertSame($scheme, $template->scheme());
        $this->assertSame('Post', $template->title());
    }

    public function testRenderingRequiresAPageVariable(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('Missing "page" variable');

        $this->template('plain')->render(['title' => 'x']);
    }

    public function testTemplateIsRenderedWithTheGivenVariables(): void
    {
        $template = $this->template('plain');

        $this->assertSame('plain:Hello', $template->render(['page' => $this->page(), 'title' => 'Hello']));
    }

    public function testVariablesPassedToRenderOverrideTheTemplateOnes(): void
    {
        $template = $this->template('plain', ['title' => 'from template']);

        $this->assertSame('plain:from template', $template->render(['page' => $this->page(), 'title' => 'from template']));
        $this->assertSame('plain:from render', $template->render(['page' => $this->page(), 'title' => 'from render']));
    }

    public function testTemplateVariablesAreAvailableWithoutBeingPassedToRender(): void
    {
        $template = $this->template('plain', ['title' => 'from template']);

        $this->assertSame('plain:from template', $template->render(['page' => $this->page()]));
    }

    public function testControllerVariablesOverrideEverythingElse(): void
    {
        $template = $this->template('post', ['fromVars' => 'template-var']);
        $page = $this->page();
        $page->title = 'Page title';

        $html = $template->render(['page' => $page, 'title' => 'given']);

        $this->assertSame('<article>overridden by controller|template-var|controller:Page title</article>' . "\n", $html);
    }

    public function testControllerWithoutReturnValueDoesNotBreakRendering(): void
    {
        $this->assertSame('noreturn:Hello', $this->template('noreturn')->render(['page' => $this->page(), 'title' => 'Hello']));
    }

    public function testControllerChangingTheCurrentPageDelegatesToTheNewCurrentPage(): void
    {
        $site = $this->createStub(Site::class);
        $newCurrent = $this->createStub(Page::class);
        $newCurrent->method('render')->willReturn('rendered by the new current page');
        $site->method('currentPage')->willReturn($newCurrent);

        $page = $this->page(current: true);

        $this->assertSame('rendered by the new current page', $this->template('redirecting', site: $site)->render(['page' => $page]));
    }

    public function testControllerRemovingTheCurrentPageIsReported(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('currentPage')->willReturn(null);

        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('Invalid current page');

        $this->template('redirecting', site: $site)->render(['page' => $this->page(current: true)]);
    }

    public function testNonCurrentPagesAreNotRedirected(): void
    {
        $page = $this->page(current: false);

        $this->assertSame('plain:x', $this->template('plain')->render(['page' => $page, 'title' => 'x']));
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function template(string $name, array $vars = [], ?Scheme $scheme = null, ?Site $site = null): Template
    {
        return new Template(
            $name,
            $vars,
            self::PATH,
            [],
            $scheme ?? $this->createStub(Scheme::class),
            $site ?? $this->createStub(Site::class),
            new ViewFactory(['escape' => static fn(string $text): string => htmlspecialchars($text)], [], App::instance()),
        );
    }

    private function page(bool $current = false): Page
    {
        return new class ($current) extends Page {
            public string $title = '';

            private int $calls = 0;

            public function __construct(private bool $current) {}

            public function isCurrent(): bool
            {
                // The controller of the "redirecting" template calls changeCurrent()
                return $this->current && $this->calls === 0;
            }

            public function changeCurrent(): void
            {
                ++$this->calls;
            }
        };
    }
}
