<?php

namespace Formwork\Tests\Unit\View;

use Formwork\Cms\App;
use Formwork\Tests\TestCase;
use Formwork\Utils\Str;
use Formwork\View\View;
use Formwork\View\ViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ViewFactory::class)]
final class ViewFactoryTest extends TestCase
{
    private const string VIEWS = __DIR__ . '/Fixtures/views';

    private const string OTHER = __DIR__ . '/Fixtures/other';

    public function testViewsAreCreatedFromTheResolutionPaths(): void
    {
        $view = $this->factory()->make('plain', ['name' => 'World']);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('Hello World', $view->render());
    }

    public function testDefaultsExposeTheApplication(): void
    {
        $this->assertSame(['app' => App::instance()], $this->factory()->defaults());
    }

    public function testDefaultVariablesCanBeOverridden(): void
    {
        $factory = $this->factory();
        $app = $factory->defaults()['app'];

        $this->assertSame($app, $this->factoryVars($factory->make('empty')));
        $this->assertSame('overridden', $this->factoryVars($factory->make('empty', ['app' => 'overridden'])));
    }

    public function testMethodsOfTheFactoryAreAvailableToTheViews(): void
    {
        $this->assertSame('Hello &lt;b&gt;', $this->factory()->make('plain', ['name' => '<b>'])->render());
    }

    public function testMethodsPassedToMakeOverrideFactoryMethods(): void
    {
        $view = $this->factory()->make('plain', ['name' => '<b>'], methods: ['escape' => static fn(string $text): string => strtoupper($text)]);

        $this->assertSame('Hello <B>', $view->render());
    }

    public function testMethodsCanBeAddedLater(): void
    {
        $factory = $this->factory();
        $factory->setMethods(['upper' => strtoupper(...)]);

        $this->assertSame('ABC', $factory->make('echo-method', ['text' => 'abc'])->render());
        $this->assertSame('Hello &lt;b&gt;', $factory->make('plain', ['name' => '<b>'])->render());
    }

    public function testSingleMethodsCanBeAddedLater(): void
    {
        $factory = $this->factory();
        $factory->setMethods(static fn(): string => 'ignored');

        $this->assertSame('Hello World', $factory->make('plain', ['name' => 'World'])->render());
    }

    public function testResolutionPathsCanBeOverriddenPerView(): void
    {
        $view = $this->factory()->make('foreign', ['v' => 'x'], self::OTHER);

        $this->assertSame('foreign x', $view->render());
    }

    public function testResolutionPathsCanBeAddedLater(): void
    {
        $factory = $this->factory();
        $factory->setResolutionPaths(['other' => self::OTHER]);

        $this->assertSame('foreign y', $factory->make('@other.foreign', ['v' => 'y'])->render());
        $this->assertSame('Hello World', $factory->make('plain', ['name' => 'World'])->render());
    }

    private function factory(): ViewFactory
    {
        return new ViewFactory(['escape' => Str::escape(...)], ['' => self::VIEWS], App::instance());
    }

    private function factoryVars(View $view): mixed
    {
        $property = new \ReflectionProperty(View::class, 'vars');

        return $property->getValue($view)['app'];
    }
}
