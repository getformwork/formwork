<?php

namespace Formwork\Tests\Unit\Panel\Views;

use DOMDocument;
use DOMXPath;
use Formwork\Cms\App;
use Formwork\Fields\FieldFactory;
use Formwork\Panel\Panel;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\TestInstallation;
use Formwork\Tests\UsesApplication;
use Formwork\View\View;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

/**
 * Renders the panel field views with hostile data to ensure dynamic values are escaped
 */
#[CoversNothing]
#[UsesApplication]
final class FieldViewsTest extends TestCase
{
    private const string HOSTILE = '"><script>alert(1)</script><img src=x onerror=alert(2)>\' onfocus=\'alert(3)';

    private App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = App::instance();

        // `FieldFactory` is registered lazily by the schemes service loader
        $this->app->getService(Schemes::class);
    }

    /**
     * @param array{options?: array<int|string, string>, translate?: list<string>} $config
     */
    #[DataProvider('fieldTypeProvider')]
    public function testHostileValuesCannotInjectMarkup(string $type, array $config): void
    {
        $document = $this->document($this->render($type, [
            'label'       => self::HOSTILE,
            'suggestion'  => self::HOSTILE,
            'placeholder' => self::HOSTILE,
            ...$config,
        ], self::HOSTILE));

        $domxPath = new DOMXPath($document);

        $this->assertSame(0.0, $domxPath->evaluate('count(//script[normalize-space() != ""])'), 'Inline script injected');
        $this->assertSame(0.0, $domxPath->evaluate('count(//img)'), 'Image injected');
        $this->assertSame(0.0, $domxPath->evaluate('count(//@*[starts-with(name(), "on")])'), 'Event handler injected');
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function fieldTypeProvider(): iterable
    {
        yield 'text' => ['text', []];
        yield 'textarea' => ['textarea', []];
        yield 'email' => ['email', []];
        yield 'password' => ['password', []];
        yield 'slug' => ['slug', []];
        yield 'tags' => ['tags', ['options' => [self::HOSTILE, 'plain']]];
        yield 'tags with option keys' => ['tags', ['options' => [self::HOSTILE => self::HOSTILE], 'translate' => []]];
        yield 'select' => ['select', ['options' => [self::HOSTILE => self::HOSTILE, 'plain' => 'Plain'], 'translate' => []]];
    }

    public function testTextValueIsRenderedAsAnAttributeValue(): void
    {
        $document = $this->document($this->render('text', [], self::HOSTILE));

        $this->assertSame(self::HOSTILE, (new DOMXPath($document))->evaluate('string(//input/@value)'));
    }

    public function testTagsAreRenderedAsACommaSeparatedValue(): void
    {
        $document = $this->document($this->render('tags', [], ['first', self::HOSTILE]));

        $this->assertSame('first, ' . self::HOSTILE, (new DOMXPath($document))->evaluate('string(//input/@value)'));
    }

    public function testTagOptionsAreRenderedAsEscapedJson(): void
    {
        $document = $this->document($this->render('tags', ['options' => ['plain', self::HOSTILE]], []));

        $options = (new DOMXPath($document))->evaluate('string(//input/@data-options)');

        $this->assertSame(['plain', self::HOSTILE], json_decode((string) $options, true));
    }

    public function testTextareaValueIsRenderedAsText(): void
    {
        $document = $this->document($this->render('textarea', [], self::HOSTILE));

        $this->assertSame(self::HOSTILE, (new DOMXPath($document))->evaluate('string(//textarea)'));
    }

    public function testLabelIsRenderedAsText(): void
    {
        $document = $this->document($this->render('text', ['label' => self::HOSTILE], ''));

        $this->assertSame(self::HOSTILE . ':', (new DOMXPath($document))->evaluate('string(//label)'));
    }

    public function testSelectOptionLabelsAreRenderedAsText(): void
    {
        $document = $this->document($this->render('select', ['options' => ['a' => self::HOSTILE], 'translate' => []], 'a'));

        $domxPath = new DOMXPath($document);

        $this->assertSame(self::HOSTILE, $domxPath->evaluate('string(//option)'));
        $this->assertSame('a', $domxPath->evaluate('string(//option/@value)'));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function render(string $type, array $config, mixed $value): string
    {
        $field = $this->app->getService(FieldFactory::class)->make('field', ['type' => $type, ...$config]);
        $field->set('value', $value);

        $panel = (new ReflectionClass(Panel::class))->newInstanceWithoutConstructor();

        $methods = [
            ...(require TestInstallation::systemPath() . '/config/views/methods.php')($this->app),
            ...(require TestInstallation::rootPath() . '/panel/config/views/methods.php')($this->app, $panel),
            // Icons are loaded from the panel assets, which are not needed here
            'icon' => static fn(string $icon): string => '',
        ];

        $view = new View(
            "@panel.fields.{$type}",
            ['field' => $field, 'app' => $this->app],
            ['' => TestInstallation::rootPath() . '/site/templates', 'panel' => TestInstallation::rootPath() . '/panel/views'],
            $methods,
        );

        return $view->render();
    }

    private function document(string $html): DOMDocument
    {
        $domDocument = new DOMDocument();
        $domDocument->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NOERROR);

        return $domDocument;
    }
}
