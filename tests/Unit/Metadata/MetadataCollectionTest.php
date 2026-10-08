<?php

namespace Formwork\Tests\Unit\Metadata;

use Formwork\Metadata\Metadata;
use Formwork\Metadata\MetadataCollection;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MetadataCollection::class)]
final class MetadataCollectionTest extends TestCase
{
    public function testCollectionIsBuiltFromNamesAndContents(): void
    {
        $collection = new MetadataCollection(['description' => 'A page', 'og:title' => 'Title']);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(Metadata::class, $collection->get('description'));
        $this->assertSame('A page', $collection->get('description')->content());
        $this->assertSame('og', $collection->get('og:title')->prefix());
    }

    public function testCollectionCanBeEmpty(): void
    {
        $this->assertCount(0, new MetadataCollection([]));
    }

    public function testMetadataCanBeIterated(): void
    {
        $collection = new MetadataCollection(['a' => '1', 'b' => '2']);

        $names = [];
        foreach ($collection as $metadata) {
            $names[] = $metadata->name();
        }

        $this->assertSame(['a', 'b'], $names);
    }

    public function testSettingAnExistingKeyReplacesIt(): void
    {
        $collection = new MetadataCollection(['description' => 'old']);

        $collection->set('description', 'new');

        $this->assertCount(1, $collection);
        $this->assertSame('new', $collection->get('description')->content());
    }

    public function testMultipleMetadataCanBeSetAtOnce(): void
    {
        $collection = new MetadataCollection(['a' => '1']);

        $collection->setMultiple(['a' => 'changed', 'b' => '2']);

        $this->assertSame('changed', $collection->get('a')->content());
        $this->assertSame('2', $collection->get('b')->content());
    }

    public function testCloneIsIndependentFromTheOriginal(): void
    {
        $collection = new MetadataCollection(['a' => '1']);

        $clone = $collection->clone();
        $clone->set('a', 'changed');
        $clone->set('b', '2');

        $this->assertSame('1', $collection->get('a')->content());
        $this->assertFalse($collection->has('b'));
    }

    public function testContentIsKeptVerbatim(): void
    {
        $collection = new MetadataCollection(['description' => '"><script>alert(1)</script>']);

        $this->assertSame('"><script>alert(1)</script>', $collection->get('description')->content());
    }
}
