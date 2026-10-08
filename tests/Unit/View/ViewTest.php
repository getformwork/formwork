<?php

namespace Formwork\Tests\Unit\View;

use Formwork\Tests\TestCase;
use Formwork\Utils\Str;
use Formwork\View\Exceptions\RenderingException;
use Formwork\View\Exceptions\ViewResolutionException;
use Formwork\View\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(View::class)]
final class ViewTest extends TestCase
{
    private const string VIEWS = __DIR__ . '/Fixtures/views';

    private const string OTHER = __DIR__ . '/Fixtures/other';

    public function testViewIsRendered(): void
    {
        $this->assertSame('Hello World', $this->view('plain', ['name' => 'World'])->render());
    }

    public function testEmptyViewIsRenderedAsAnEmptyString(): void
    {
        $this->assertSame('', $this->view('empty')->render());
    }

    public function testNameAndPathAreExposed(): void
    {
        $view = $this->view('plain', ['name' => 'World']);

        $this->assertSame('plain', $view->name());
        $this->assertSame(self::VIEWS, $view->path());
    }

    public function testVariablesAreAvailableToTheView(): void
    {
        $this->assertSame('1-two', $this->view('vars', ['a' => 1, 'b' => 'two'])->render());
    }

    public function testViewCanBeRenderedMoreThanOnce(): void
    {
        $view = $this->view('plain', ['name' => 'World']);

        $this->assertSame($view->render(), $view->render());
    }

    public function testMissingViewsAreReported(): void
    {
        $this->expectException(ViewResolutionException::class);
        $this->expectExceptionMessage('View "missing" not found');
        $this->view('missing');
    }

    public function testNamespacedViewsAreResolvedFromTheirPath(): void
    {
        $view = new View('@other.foreign', ['v' => 'view'], ['' => self::VIEWS, 'other' => self::OTHER]);

        $this->assertSame('foreign view', $view->render());
        $this->assertSame(self::OTHER, $view->path());
    }

    public function testUnknownNamespacesAreReported(): void
    {
        $this->expectException(ViewResolutionException::class);
        new View('@unknown.foreign', [], ['' => self::VIEWS]);
    }

    public function testViewNamesCannotBeResolvedOutsideTheResolutionPath(): void
    {
        $this->expectException(ViewResolutionException::class);
        $this->view('../other/foreign');
    }

    public function testViewsCanBeResolvedFromTheFirstStringPath(): void
    {
        $view = new View('foreign', ['v' => 'x'], self::OTHER);

        $this->assertSame('foreign x', $view->render());
    }

    public function testMethodsAreAvailableWhileRendering(): void
    {
        $view = $this->view('echo-method', ['text' => 'abc'], ['upper' => strtoupper(...)]);

        $this->assertSame('ABC', $view->render());
    }

    public function testMethodsCannotBeCalledOutsideOfRendering(): void
    {
        $view = $this->view('plain', ['name' => 'x'], ['escape' => Str::escape(...)]);

        $this->expectException(RenderingException::class);
        $view->escape('x');
    }

    #[DataProvider('hostileTextProvider')]
    public function testEscapeMethodNeutralizesMarkup(string $text, string $expected): void
    {
        $view = $this->view('plain', ['name' => $text], ['escape' => Str::escape(...)]);

        $this->assertSame('Hello ' . $expected, $view->render());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function hostileTextProvider(): iterable
    {
        yield 'script element' => ['<script>alert(1)</script>', '&lt;script&gt;alert(1)&lt;/script&gt;'];
        yield 'image with handler' => ['<img src=x onerror=alert(1)>', '&lt;img src=x onerror=alert(1)&gt;'];
        yield 'title closing' => ['</title><script>alert(1)</script>', '&lt;/title&gt;&lt;script&gt;alert(1)&lt;/script&gt;'];
        yield 'double quotes' => ['" onmouseover="alert(1)', '&quot; onmouseover=&quot;alert(1)'];
        yield 'single quotes' => ["' onmouseover='alert(1)", '&#039; onmouseover=&#039;alert(1)'];
        yield 'comment' => ['<!--', '&lt;!--'];
        yield 'ampersand' => ['a & b', 'a &amp; b'];
        yield 'invalid unicode' => ["a\xffb", "a\u{FFFD}b"];
    }

    public function testViewWithoutEscapingKeepsMarkup(): void
    {
        $view = new View('plain', ['name' => '<b>'], self::VIEWS, ['escape' => static fn(string $text): string => $text]);

        $this->assertSame('Hello <b>', $view->render());
    }

    public function testLayoutWrapsTheViewContent(): void
    {
        $this->assertSame('[Inner]', $this->view('uses-layout')->render());
    }

    public function testLayoutsCanHaveTheirOwnLayout(): void
    {
        $this->assertSame('[outer(core)]', $this->view('nested-layout')->render());
    }

    public function testLayoutCannotBeSetTwice(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('layout for the view "double-layout" is already set');
        $this->view('double-layout')->render();
    }

    public function testMissingLayoutsAreReported(): void
    {
        $this->expectException(ViewResolutionException::class);
        $this->view('uses-missing-layout')->render();
    }

    public function testBlocksAreCapturedAndAvailableToTheLayout(): void
    {
        $this->assertSame('Body|Side|yes|no', $this->view('blocks')->render());
    }

    public function testBlocksCannotBeReadOutsideOfRendering(): void
    {
        $view = $this->view('blocks');

        $this->expectException(RenderingException::class);
        $view->block('side');
    }

    public function testUndefinedBlocksAreReported(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('The block "missing" is undefined');
        $this->view('unknown-block')->render();
    }

    public function testContentBlockIsReserved(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('The block "content" is reserved');
        $this->view('reserved')->render();
    }

    public function testBlocksMustBeClosed(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('Incomplete blocks found: "x"');
        $this->view('incomplete')->render();
    }

    public function testEndWithoutAnOpenBlockIsReported(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('There are no blocks to end');
        $this->view('unmatched-end')->render();
    }

    #[DataProvider('renderingOnlyMethodProvider')]
    public function testBlockAndInsertMethodsRequireTheRenderingContext(string $method, array $arguments): void
    {
        $view = $this->view('plain', ['name' => 'x']);

        $this->expectException(RenderingException::class);
        $view->$method(...$arguments);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function renderingOnlyMethodProvider(): iterable
    {
        yield 'insert' => ['insert', ['_part']];
        yield 'define' => ['define', ['block']];
        yield 'end' => ['end', []];
        yield 'defined' => ['defined', ['block']];
        yield 'content' => ['content', []];
    }

    public function testViewsCanInsertPartials(): void
    {
        $this->assertSame('A(X/none)B', $this->view('inserts')->render());
    }

    public function testInsertedViewsInheritTheVariablesOfTheParent(): void
    {
        $this->assertSame('(X/shared)', $this->view('inserts-with-vars', ['shared' => 'shared'])->render());
    }

    public function testExplicitVariablesOverrideInheritedOnes(): void
    {
        $this->assertSame('(X/shared)', $this->view('inserts-with-vars', ['shared' => 'shared', 'x' => 'inherited'])->render());
    }

    public function testExceptionsPropagateAndDiscardPartialOutput(): void
    {
        $level = ob_get_level();

        try {
            $this->view('throws')->render();
            $this->fail('The exception thrown by the view should have propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertSame($level, ob_get_level());
    }

    public function testRenderingIsNotAllowedWhileRendering(): void
    {
        $view = $this->view('empty', methods: []);
        $property = new \ReflectionProperty(View::class, 'rendering');
        $property->setValue($view, true);

        $this->expectException(RenderingException::class);
        $view->render();
    }

    /**
     * @param array<string, mixed>   $vars
     * @param array<string, Closure> $methods
     */
    private function view(string $name, array $vars = [], array $methods = []): View
    {
        return new View($name, $vars, self::VIEWS, ['escape' => Str::escape(...), ...$methods]);
    }
}
