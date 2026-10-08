<?php

namespace Formwork\Tests\Unit\Metadata;

use Formwork\Metadata\Metadata;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Metadata::class)]
final class MetadataTest extends TestCase
{
    public function testNameAndContentAreExposed(): void
    {
        $metadata = new Metadata('description', 'A page');

        $this->assertSame('description', $metadata->name());
        $this->assertSame('A page', $metadata->content());
    }

    public function testMetadataIsConvertedToItsContent(): void
    {
        $this->assertSame('A page', (string) new Metadata('description', 'A page'));
    }

    public function testNameIsLowercased(): void
    {
        $this->assertSame('og:title', (new Metadata('OG:Title', 'x'))->name());
    }

    public function testContentIsNotAltered(): void
    {
        $content = '<script>alert("x")</script> & "quoted"';

        $this->assertSame($content, (new Metadata('description', $content))->content());
    }

    #[DataProvider('prefixProvider')]
    public function testPrefixIsTheNamePartBeforeTheFirstColon(string $name, ?string $expected): void
    {
        $metadata = new Metadata($name, 'x');

        $this->assertSame($expected, $metadata->prefix());
        $this->assertSame($expected !== null, $metadata->hasPrefix());
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function prefixProvider(): iterable
    {
        yield 'no prefix' => ['description', null];
        yield 'open graph' => ['og:image', 'og'];
        yield 'twitter' => ['twitter:card', 'twitter'];
        yield 'nested prefix' => ['og:image:width', 'og'];
        yield 'empty prefix' => [':name', null];
        yield 'trailing colon' => ['og:', 'og'];
    }

    public function testPrefixIsLowercasedLikeTheName(): void
    {
        // Templates compare the prefix with lowercase values, e.g. `og`
        $metadata = new Metadata('OG:Title', 'x');

        $this->assertSame('og:title', $metadata->name());
        $this->assertSame('og', $metadata->prefix());
    }

    public function testCharsetIsRecognized(): void
    {
        $this->assertTrue((new Metadata('charset', 'utf-8'))->isCharset());
        $this->assertTrue((new Metadata('CHARSET', 'utf-8'))->isCharset());
        $this->assertFalse((new Metadata('description', 'utf-8'))->isCharset());
    }

    #[DataProvider('httpEquivProvider')]
    public function testHttpEquivDirectivesAreRecognized(string $name, bool $expected): void
    {
        $this->assertSame($expected, (new Metadata($name, 'x'))->isHTTPEquiv());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function httpEquivProvider(): iterable
    {
        yield 'content-type' => ['content-type', true];
        yield 'default-style' => ['default-style', true];
        yield 'refresh' => ['refresh', true];
        yield 'uppercase refresh' => ['REFRESH', true];
        yield 'description' => ['description', false];
        yield 'charset' => ['charset', false];
        yield 'robots' => ['robots', false];
        yield 'set-cookie' => ['set-cookie', false];
    }
}
