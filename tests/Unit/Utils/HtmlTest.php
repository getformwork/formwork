<?php

namespace Formwork\Tests\Unit\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Html;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Html::class)]
final class HtmlTest extends TestCase
{
    public function testClassesIncludeOnlyTheEnabledOnes(): void
    {
        $this->assertSame('class1 class2', Html::classes(['class1', 'class2']));
        $this->assertSame('class1 class3', Html::classes(['class1' => true, 'class2' => false, 'class3' => true]));
        $this->assertSame('', Html::classes([]));
    }

    public function testAttributeFormatsTheValueByType(): void
    {
        $this->assertSame('disabled', Html::attribute('disabled', true));
        $this->assertSame('data-value="123"', Html::attribute('data-value', 123));
        $this->assertSame('data-list="item1 item2 item3"', Html::attribute('data-list', ['item1', 'item2', 'item3']));
        $this->assertSame('', Html::attribute('hidden', false));
    }

    public function testAttributesSkipFalseValues(): void
    {
        $attributes = [
            'disabled'   => true,
            'data-value' => 123,
            'data-list'  => ['item1', 'item2', 'item3'],
            'hidden'     => false,
        ];
        $this->assertSame('disabled data-value="123" data-list="item1 item2 item3"', Html::attributes($this->unchecked($attributes)));
    }

    public function testTagRendersElementsAndVoidElements(): void
    {
        $this->assertSame('<div class="container">Content</div>', Html::tag('div', ['class' => 'container'], 'Content'));
        $this->assertSame('<input type="text" disabled>', Html::tag('input', ['type' => 'text', 'disabled' => true]));
    }

    public function testTagRejectsVoidElementsWithContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set tag content, <img> is a void element');
        Html::tag('img', [], 'Content');
    }

    public function testTagLowercasesItsNameAndJoinsMultipleContents(): void
    {
        $this->assertSame('<p>one<b>two</b></p>', Html::tag('P', [], 'one', Html::tag('b', [], 'two')));
        $this->assertSame('<br>', Html::tag('BR'));
        $this->assertSame('<p></p>', Html::tag('p'));
    }

    public function testTagEscapesQuotesAndAmpersandsInAttributeValues(): void
    {
        $this->assertSame('<a title="a &quot;quoted&quot; &amp; <tagged> title"></a>', Html::tag('a', ['title' => 'a "quoted" & <tagged> title']));
        $this->assertSame('<a title="x &amp; y"></a>', Html::tag('a', ['title' => 'x &amp; y']));
    }

    #[DataProvider('voidElementProvider')]
    public function testIsVoidRecognizesVoidElements(string $tag, bool $expected): void
    {
        $this->assertSame($expected, Html::isVoid($tag));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function voidElementProvider(): iterable
    {
        foreach (['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'] as $tag) {
            yield $tag => [$tag, true];
        }

        yield 'uppercase void element' => ['IMG', true];
        yield 'div' => ['div', false];
        yield 'paragraph' => ['p', false];
        yield 'script' => ['script', false];
        yield 'unknown tag' => ['custom-element', false];
    }
}
