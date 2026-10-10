<?php

namespace Formwork\Tests\Unit\Plugins;

use Formwork\Plugins\PluginManifest;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(PluginManifest::class)]
final class PluginManifestTest extends TestCase
{
    public function testEmptyManifestHasNoMetadata(): void
    {
        $manifest = new PluginManifest([]);

        $this->assertNull($manifest->title());
        $this->assertNull($manifest->description());
        $this->assertNull($manifest->author());
        $this->assertNull($manifest->homepage());
        $this->assertNull($manifest->license());
        $this->assertNull($manifest->version());
        $this->assertSame([], $manifest->config());
    }

    public function testMetadataIsExposed(): void
    {
        $manifest = new PluginManifest([
            'title'       => 'Demo',
            'description' => 'A demo plugin',
            'author'      => 'Jane',
            'homepage'    => 'https://example.com',
            'license'     => 'MIT',
            'version'     => '1.2.3',
            'config'      => ['greeting' => 'hi', 'nested' => ['a' => 1]],
        ]);

        $this->assertSame('Demo', $manifest->title());
        $this->assertSame('A demo plugin', $manifest->description());
        $this->assertSame('Jane', $manifest->author());
        $this->assertSame('https://example.com', $manifest->homepage());
        $this->assertSame('MIT', $manifest->license());
        $this->assertSame('1.2.3', $manifest->version());
        $this->assertSame(['greeting' => 'hi', 'nested' => ['a' => 1]], $manifest->config());
    }

    public function testToArrayExportsEveryField(): void
    {
        $this->assertSame([
            'title'       => 'Demo',
            'description' => null,
            'author'      => null,
            'homepage'    => null,
            'license'     => null,
            'version'     => '1.0.0',
            'config'      => [],
        ], (new PluginManifest(['title' => 'Demo', 'version' => '1.0.0']))->toArray());
    }

    public function testUnknownPropertiesAreRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid property "unknown"');

        new PluginManifest(['unknown' => 'value']);
    }

    public function testInvalidTypesAreReportedAsInvalidValues(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new PluginManifest(['config' => 'not-an-array']);
    }
}
