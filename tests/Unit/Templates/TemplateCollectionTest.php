<?php

namespace Formwork\Tests\Unit\Templates;

use Formwork\Templates\Template;
use Formwork\Templates\TemplateCollection;
use Formwork\Templates\Templates;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(TemplateCollection::class)]
#[CoversClass(Templates::class)]
final class TemplateCollectionTest extends TestCase
{
    public function testTemplatesAreIndexedByName(): void
    {
        $page = $this->createStub(Template::class);
        $post = $this->createStub(Template::class);

        $templates = new Templates(['page' => $page, 'post' => $post]);

        $this->assertSame(['page', 'post'], $templates->keys());
        $this->assertSame($post, $templates->get('post'));
        $this->assertTrue($templates->has('page'));
        $this->assertFalse($templates->has('blog'));
    }

    public function testOnlyTemplatesAreAccepted(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');

        new Templates($this->unchecked(['page' => 'not a template']));
    }

    public function testListsOfTemplatesAreRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Associative collections cannot be created from non-associative data');

        new TemplateCollection([$this->createStub(Template::class)]);
    }

    public function testItemsThatAreNotTemplatesAreRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Typed collections cannot be created from data of different types');

        new Templates($this->unchecked(['item' => new stdClass()]));
    }
}
