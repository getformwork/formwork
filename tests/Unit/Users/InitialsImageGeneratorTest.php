<?php

namespace Formwork\Tests\Unit\Users;

use Formwork\Tests\TestCase;
use Formwork\Users\InitialsImageGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(InitialsImageGenerator::class)]
final class InitialsImageGeneratorTest extends TestCase
{
    public function testImagesAreSvgDataUris(): void
    {
        $uri = InitialsImageGenerator::generate('Alice Smith');

        $this->assertStringStartsWith('data:image/svg+xml,', $uri);
        $this->assertSame(1, preg_match('/^data:image\/svg\+xml,[A-Za-z0-9%._~-]+$/', $uri), 'The payload must be fully percent encoded');
    }

    public function testImagesAreValidXml(): void
    {
        $document = new \DOMDocument();

        $this->assertTrue($document->loadXML($this->svg('Alice Smith')));
        $this->assertSame('svg', $document->documentElement?->nodeName);
        $this->assertSame('http://www.w3.org/2000/svg', $document->documentElement?->namespaceURI);
    }

    #[DataProvider('initialsProvider')]
    public function testInitialsAreTheFirstUppercaseLettersOfTheName(string $name, string $expected): void
    {
        $this->assertSame($expected, $this->initials($name));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function initialsProvider(): iterable
    {
        yield 'two words' => ['Alice Smith', 'AS'];
        yield 'lowercase words' => ['alice smith', 'AS'];
        yield 'one word' => ['Alice', 'A'];
        yield 'three words use only two initials' => ['Alice Beth Carter', 'AB'];
        yield 'extra whitespace' => ['  alice    smith  ', 'AS'];
        yield 'hyphenated name' => ['Anne-Marie', 'AM'];
        yield 'accented letters' => ['Élodie Ångström', 'ÉÅ'];
        yield 'digits are ignored' => ['user42', 'U'];
        yield 'empty name' => ['', ''];
        yield 'only digits' => ['12345', ''];
        yield 'non cased script' => ['日本語', ''];
        yield 'apostrophe' => ["O'Brien", 'OB'];
    }

    #[DataProvider('entityNameProvider')]
    public function testInitialsAreNotInfluencedByEscapingEntities(string $name, string $expected): void
    {
        $this->assertSame($expected, $this->initials($name));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function entityNameProvider(): iterable
    {
        yield 'ampersand' => ['Tom & Jerry', 'TJ'];
        yield 'double quotes' => ['"Alice" Smith', 'AS'];
        yield 'single quotes' => ["'Alice' Smith", 'AS'];
        yield 'quotes around the whole name' => ['"Alice Smith"', 'AS'];
        yield 'ampersand without spaces' => ['Alice&Bob', 'AB'];
    }

    #[DataProvider('hostileNameProvider')]
    public function testHostileNamesCannotInjectMarkup(string $name): void
    {
        $svg = $this->svg($name);

        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($svg), 'The SVG must stay well formed: ' . $svg);

        $elements = [];
        foreach ($document->getElementsByTagName('*') as $element) {
            $elements[] = $element->nodeName;
        }

        $this->assertSame(['svg', 'circle', 'text'], $elements);
        $this->assertStringNotContainsString('script', strtolower($svg));
        $this->assertStringNotContainsString('onload', strtolower($svg));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostileNameProvider(): iterable
    {
        yield 'script element' => ['<script>alert(1)</script>'];
        yield 'closing the text element' => ['</text><script>alert(1)</script>'];
        yield 'event handler' => ['" onload="alert(1)'];
        yield 'single quote event handler' => ["' onload='alert(1)"];
        yield 'image element' => ['<img src=x onerror=alert(1)>'];
        yield 'entities' => ['&lt;script&gt; &amp; &#60;'];
        yield 'CDATA' => [']]><script>alert(1)</script>'];
        yield 'null byte' => ["Alice\0Smith"];
        yield 'invalid unicode' => ["Alice \xff Smith"];
    }

    public function testColorsAreDerivedFromTheName(): void
    {
        $this->assertSame($this->svg('Alice Smith'), $this->svg('Alice Smith'));
        $this->assertNotSame($this->color('Alice Smith'), $this->color('Bob Jones'));
    }

    public function testColorsAreHexadecimal(): void
    {
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $this->color('Alice Smith'));
    }

    #[DataProvider('contrastProvider')]
    public function testTextColorContrastsWithTheBackground(string $name): void
    {
        $svg = $this->svg($name);
        preg_match('/<circle[^>]*fill="(#[0-9a-f]{6})"/', $svg, $background);
        preg_match('/<text fill="(#[0-9a-f]{6})"/', $svg, $text);
        [$r, $g, $b] = sscanf($background[1], '#%2x%2x%2x');
        $luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;

        $this->assertSame($luminance > 149 ? '#000000' : '#ffffff', $text[1]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function contrastProvider(): iterable
    {
        foreach (['Alice', 'Bob', 'Carol', 'Dave', 'Erin', 'Frank', 'Grace', 'Heidi', 'Ivan', 'Judy', 'Mallory', 'Olivia'] as $name) {
            yield $name => [$name];
        }
    }

    public function testDifferentNamesWithTheSameInitialsHaveDifferentColors(): void
    {
        $colors = [];
        foreach (['Anna Smith', 'Adam Stone', 'Alba Sun', 'Alex Shaw', 'Ally Snow'] as $name) {
            $colors[] = $this->color($name);
        }

        $this->assertGreaterThan(1, count(array_unique($colors)));
    }

    private function svg(string $name): string
    {
        return rawurldecode(substr(InitialsImageGenerator::generate($name), strlen('data:image/svg+xml,')));
    }

    private function initials(string $name): string
    {
        preg_match('#<text[^>]*>(.*?)</text>#su', $this->svg($name), $matches);

        return $matches[1] ?? '';
    }

    private function color(string $name): string
    {
        preg_match('/<circle[^>]*fill="(#[0-9a-f]{6})"/', $this->svg($name), $matches);

        return $matches[1];
    }
}
