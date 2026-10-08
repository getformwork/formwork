<?php

namespace Formwork\Tests\Unit\Panel\Views;

use Formwork\Cms\App;
use Formwork\Fields\FieldFactory;
use Formwork\Panel\Panel;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\View\View;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Renders the panel field views with hostile data to ensure dynamic values are escaped
 */
#[CoversNothing]
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

    #[DataProvider('fieldTypeProvider')]
    public function testHostileValuesCannotInjectMarkup(string $type, array $config): void
    {
        $document = $this->document($this->render($type, [
            'label'       => self::HOSTILE,
            'suggestion'  => self::HOSTILE,
            'placeholder' => self::HOSTILE,
            ...$config,
        ], self::HOSTILE));

        $xpath = new \DOMXPath($document);

        foreach ($xpath->query('//script') as $script) {
            $this->assertSame('', trim($script->textContent), 'Inline script injected');
        }

        $this->assertSame(0, $xpath->query('//img')->length, 'Image injected');
        $this->assertSame(0, $xpath->query('//@*[starts-with(name(), "on")]')->length, 'Event handler injected');
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
        yield 'tags with option keys' => ['tags', ['options' => [self::HOSTILE => self::HOSTILE]]];
        yield 'select' => ['select', ['options' => [self::HOSTILE => self::HOSTILE, 'plain' => 'Plain']]];
    }

    public function testTextValueIsRenderedAsAnAttributeValue(): void
    {
        $document = $this->document($this->render('text', [], self::HOSTILE));

        $input = (new \DOMXPath($document))->query('//input')->item(0);

        $this->assertSame(self::HOSTILE, $input?->getAttribute('value'));
    }

    public function testTagsAreRenderedAsACommaSeparatedValue(): void
    {
        $document = $this->document($this->render('tags', [], ['first', self::HOSTILE]));

        $input = (new \DOMXPath($document))->query('//input')->item(0);

        $this->assertSame('first, ' . self::HOSTILE, $input?->getAttribute('value'));
    }

    public function testTagOptionsAreRenderedAsEscapedJson(): void
    {
        $document = $this->document($this->render('tags', ['options' => ['plain', self::HOSTILE]], []));

        $input = (new \DOMXPath($document))->query('//input')->item(0);

        $this->assertSame(['plain', self::HOSTILE], json_decode((string) $input?->getAttribute('data-options'), true));
    }

    public function testTextareaValueIsRenderedAsText(): void
    {
        $document = $this->document($this->render('textarea', [], self::HOSTILE));

        $textarea = (new \DOMXPath($document))->query('//textarea')->item(0);

        $this->assertSame(self::HOSTILE, $textarea?->textContent);
    }

    public function testLabelIsRenderedAsText(): void
    {
        $document = $this->document($this->render('text', ['label' => self::HOSTILE], ''));

        $label = (new \DOMXPath($document))->query('//label')->item(0);

        $this->assertSame(self::HOSTILE . ':', $label?->textContent);
    }

    public function testSelectOptionLabelsAreRenderedAsText(): void
    {
        $document = $this->document($this->render('select', ['options' => ['a' => self::HOSTILE]], 'a'));

        $option = (new \DOMXPath($document))->query('//option')->item(0);

        $this->assertSame(self::HOSTILE, $option?->textContent);
        $this->assertSame('a', $option?->getAttribute('value'));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function render(string $type, array $config, mixed $value): string
    {
        $field = $this->app->getService(FieldFactory::class)->make('field', ['type' => $type, ...$config]);
        $field->set('value', $value);

        $panel = (new \ReflectionClass(Panel::class))->newInstanceWithoutConstructor();

        $methods = [
            ...(require SYSTEM_PATH . '/config/views/methods.php')($this->app),
            ...(require ROOT_PATH . '/panel/config/views/methods.php')($this->app, $panel),
            // Icons are loaded from the panel assets, which are not needed here
            'icon' => static fn(string $icon): string => '',
        ];

        $view = new View(
            "@panel.fields.{$type}",
            ['field' => $field, 'app' => $this->app],
            ['' => ROOT_PATH . '/site/templates', 'panel' => ROOT_PATH . '/panel/views'],
            $methods,
        );

        return $view->render();
    }

    private function document(string $html): \DOMDocument
    {
        $document = new \DOMDocument();
        $document->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NOERROR);

        return $document;
    }
}
