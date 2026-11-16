<?php

namespace Formwork\Tests\Unit\Images\Transform;

use Formwork\Images\Transform\Desaturate;
use Formwork\Images\Transform\Resize;
use Formwork\Images\Transform\ResizeMode;
use Formwork\Images\Transform\TransformCollection;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(TransformCollection::class)]
#[UsesClass(Resize::class)]
#[UsesClass(Desaturate::class)]
final class TransformCollectionTest extends TestCase
{
    public function testSpecifierJoinsTheSpecifiersOfTheTransformsInOrder(): void
    {
        $transformCollection = new TransformCollection();
        $transformCollection->add(new Resize(50, null, ResizeMode::Contain));
        $transformCollection->add(new Desaturate());

        $this->assertSame('Resize(width: 50, height: null, resizeMode: \Formwork\Images\Transform\ResizeMode::Contain)|Desaturate()', $transformCollection->getSpecifier());
    }

    public function testEmptyCollectionsHaveEmptySpecifiers(): void
    {
        $this->assertSame('', (new TransformCollection())->getSpecifier());
    }
}
