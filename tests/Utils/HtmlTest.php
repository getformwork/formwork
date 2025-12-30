<?php

namespace Formwork\Tests\Utils;

use Formwork\Tests\TestCase;
use Formwork\Utils\Html;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Html::class)]
final class HtmlTest extends TestCase
{
    public function testClasses(): void
    {
        $this->assertSame('class1 class2', Html::classes(['class1', 'class2']));
        $this->assertSame('class1 class3', Html::classes(['class1' => true, 'class2' => false, 'class3' => true]));
        $this->assertSame('', Html::classes([]));
    }

    public function testAttribute(): void
    {
        $this->assertSame('disabled', Html::attribute('disabled', true));
        $this->assertSame('data-value="123"', Html::attribute('data-value', 123));
        $this->assertSame('data-list="item1 item2 item3"', Html::attribute('data-list', ['item1', 'item2', 'item3']));
        $this->assertSame('', Html::attribute('hidden', false));
    }

    public function testAttributes(): void
    {
        $attributes = [
            'disabled'   => true,
            'data-value' => 123,
            'data-list'  => ['item1', 'item2', 'item3'],
            'hidden'     => false,
        ];
        $this->assertSame('disabled data-value="123" data-list="item1 item2 item3"', Html::attributes($attributes));
    }

    public function testTag(): void
    {
        $this->assertSame('<div class="container">Content</div>', Html::tag('div', ['class' => 'container'], 'Content'));
        $this->assertSame('<input type="text" disabled>', Html::tag('input', ['type' => 'text', 'disabled' => true]));
    }

    public function testTagThrowsOnVoidWithContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Html::tag('img', [], 'Content');
    }

    public function testIsVoid(): void
    {
        $this->assertTrue(Html::isVoid('img'));
        $this->assertFalse(Html::isVoid('div'));
    }
}
