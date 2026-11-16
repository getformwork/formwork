<?php

namespace Formwork\Tests\Unit\Forms;

use Formwork\Forms\FormData;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FormData::class)]
final class FormDataTest extends TestCase
{
    public function testDataIsReadAndCounted(): void
    {
        $formData = new FormData(['title' => 'Title', 'tags' => ['a', 'b']]);

        $this->assertFalse($formData->isEmpty());
        $this->assertCount(2, $formData);
        $this->assertSame('Title', $formData->get('title'));
        $this->assertSame(['title' => 'Title', 'tags' => ['a', 'b']], $formData->toArray());
    }

    public function testFormDataWithoutValuesIsEmpty(): void
    {
        $this->assertTrue((new FormData([]))->isEmpty());
    }
}
