<?php

namespace Formwork\Tests\Unit\Templates;

use Formwork\Templates\Template;
use Formwork\Templates\TemplateCollection;
use Formwork\Templates\Templates;
use Formwork\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

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

    public function testCollectionIsAssociativeAndTyped(): void
    {
        $templates = new Templates([]);

        $this->assertTrue($templates->isAssociative());
        $this->assertSame(Template::class, $templates->dataType());
        $this->assertTrue($templates->isEmpty());
    }

    public function testOnlyTemplatesAreAccepted(): void
    {
        $logicException = $this->captureException(LogicException::class, fn() => new Templates($this->unchecked(['page' => 'not a template'])));

        $this->assertInstanceOf(LogicException::class, $logicException);
    }

    public function testListsOfTemplatesAreRejected(): void
    {
        $this->expectException(LogicException::class);

        new TemplateCollection([$this->createStub(Template::class)]);
    }
}
