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
        $pluginManifest = new PluginManifest([]);

        $this->assertNull($pluginManifest->title());
        $this->assertNull($pluginManifest->description());
        $this->assertNull($pluginManifest->author());
        $this->assertNull($pluginManifest->homepage());
        $this->assertNull($pluginManifest->license());
        $this->assertNull($pluginManifest->version());
        $this->assertSame([], $pluginManifest->config());
    }

    public function testMetadataIsExposed(): void
    {
        $pluginManifest = new PluginManifest([
            'title'       => 'Demo',
            'description' => 'A demo plugin',
            'author'      => 'Jane',
            'homepage'    => 'https://example.com',
            'license'     => 'MIT',
            'version'     => '1.2.3',
            'config'      => ['greeting' => 'hi', 'nested' => ['a' => 1]],
        ]);

        $this->assertSame('Demo', $pluginManifest->title());
        $this->assertSame('A demo plugin', $pluginManifest->description());
        $this->assertSame('Jane', $pluginManifest->author());
        $this->assertSame('https://example.com', $pluginManifest->homepage());
        $this->assertSame('MIT', $pluginManifest->license());
        $this->assertSame('1.2.3', $pluginManifest->version());
        $this->assertSame(['greeting' => 'hi', 'nested' => ['a' => 1]], $pluginManifest->config());
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
