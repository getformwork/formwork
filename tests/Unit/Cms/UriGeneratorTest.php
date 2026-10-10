<?php

namespace Formwork\Tests\Unit\Cms;

use Formwork\Cms\UriGenerator;
use Formwork\Http\Request;
use Formwork\Router\Router;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UriGenerator::class)]
final class UriGeneratorTest extends TestCase
{
    public function testPathsAreResolvedUnderTheRequestRoot(): void
    {
        $generator = $this->generator('/subdir/index.php');

        $this->assertSame('/subdir/page/', $generator->path('/page/'));
        $this->assertSame('/subdir/page/', $generator->path('page/'));
        $this->assertSame('/subdir/assets/a.css', $generator->path('/assets/a.css'));
    }

    public function testInstallationInTheDocumentRoot(): void
    {
        $generator = $this->generator('/index.php');

        $this->assertSame('/page/', $generator->path('/page/'));
        $this->assertSame('/', $generator->path('/'));
    }

    public function testSlashesAreNormalized(): void
    {
        $this->assertSame('/subdir/a/b/', $this->generator('/subdir/index.php')->path('//a///b//'));
    }

    public function testRoutesAreGeneratedByNameAndPlacedUnderTheRoot(): void
    {
        $router = $this->createMock(Router::class);
        $router->expects($this->once())->method('generate')->with('page', ['page' => 'about'])->willReturn('/about/');

        $generator = $this->generator('/subdir/index.php', $router);

        $this->assertSame('/subdir/about/', $generator->route('page', ['page' => 'about']));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function traversals(): iterable
    {
        yield 'parent directory' => ['../secret'];
        yield 'several parents' => ['../../../../secret'];
        yield 'nested parents' => ['a/../../../secret'];
    }

    #[DataProvider('traversals')]
    public function testPathsCannotClimbAboveTheRequestRoot(string $path): void
    {
        $uri = $this->generator('/subdir/deeper/index.php')->path($path);

        $this->assertStringStartsWith('/subdir/deeper/', $uri);
    }

    public function testQueryStringsAndFragmentsArePreserved(): void
    {
        $this->assertSame('/subdir/page/?a=1#top', $this->generator('/subdir/index.php')->path('/page/?a=1#top'));
    }

    private function generator(string $scriptName, ?Router $router = null): UriGenerator
    {
        $request = new Request([], [], [], [], ['SCRIPT_NAME' => $scriptName, 'REQUEST_METHOD' => 'GET']);

        return new UriGenerator($request, $router ?? $this->createStub(Router::class));
    }
}
