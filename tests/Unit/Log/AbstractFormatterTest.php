<?php

namespace Formwork\Tests\Unit\Log;

use ArrayObject;
use DateTimeImmutable;
use Formwork\Http\RequestMethod;
use Formwork\Log\Formatter\AbstractFormatter;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Log\Fixtures\ExposedFormatter;
use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;
use Stringable;

#[CoversClass(AbstractFormatter::class)]
final class AbstractFormatterTest extends TestCase
{
    #[DataProvider('interpolationProvider')]
    public function testInterpolation(string $message, array $context, string $expected): void
    {
        $this->assertSame($expected, (new ExposedFormatter())->interpolateMessage($message, $context));
    }

    /**
     * @return iterable<string, array{string, array<mixed>, string}>
     */
    public static function interpolationProvider(): iterable
    {
        yield 'no placeholders' => ['plain message', ['a' => 1], 'plain message'];
        yield 'no context' => ['{a} stays', [], '{a} stays'];
        yield 'string value' => ['Hello {name}', ['name' => 'World'], 'Hello World'];
        yield 'integer value' => ['Count: {count}', ['count' => 5], 'Count: 5'];
        yield 'float value' => ['Ratio: {ratio}', ['ratio' => 1.5], 'Ratio: 1.5'];
        yield 'true value' => ['Flag: {flag}', ['flag' => true], 'Flag: 1'];
        yield 'false value' => ['Flag: {flag}', ['flag' => false], 'Flag: '];
        yield 'null value' => ['Value: {value}', ['value' => null], 'Value: '];
        yield 'repeated placeholder' => ['{a} and {a}', ['a' => 'x'], 'x and x'];
        yield 'several placeholders' => ['{a}-{b}-{c}', ['a' => 1, 'b' => 2, 'c' => 3], '1-2-3'];
        yield 'unknown placeholder' => ['{known} {unknown}', ['known' => 'yes'], 'yes {unknown}'];
        yield 'dotted key' => ['{user.name}', ['user.name' => 'alice'], 'alice'];
        yield 'underscore key' => ['{user_name}', ['user_name' => 'alice'], 'alice'];
        yield 'array value' => ['Data: {data}', ['data' => [1, 2]], 'Data: [array]'];
        yield 'object value' => ['Object: {object}', ['object' => new stdClass()], 'Object: [object stdClass]'];
        yield 'stringable object' => ['Name: {object}', ['object' => new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        }], 'Name: stringable'];
        yield 'backed enum' => ['Method: {method}', ['method' => RequestMethod::POST], 'Method: POST'];
        yield 'date time' => ['At: {when}', ['when' => new DateTimeImmutable('2025-01-02 03:04:05 UTC')], 'At: 2025-01-02T03:04:05+00:00'];
        yield 'resource' => ['Stream: {stream}', ['stream' => STDIN], 'Stream: [resource (stream)]'];
        yield 'placeholder with spaces is ignored' => ['{a b}', ['a b' => 'x'], '{a b}'];
        yield 'placeholder with a dash is ignored' => ['{a-b}', ['a-b' => 'x'], '{a-b}'];
        yield 'values are not interpolated again' => ['{a}', ['a' => '{b}', 'b' => 'B'], '{b}'];
        yield 'integer keys' => ['{0} {1}', [0 => 'zero', 1 => 'one'], 'zero one'];
        yield 'empty message' => ['', ['a' => 1], ''];
        yield 'unclosed placeholder' => ['{a', ['a' => 1], '{a'];
        yield 'braces without a name' => ['{} {{a}}', ['a' => 'x'], '{} {x}'];
    }

    public function testDateFormatIsConfigurable(): void
    {
        $formatter = new ExposedFormatter();
        $date = new DateTimeImmutable('2025-01-02 03:04:05 UTC');

        $this->assertSame('2025-01-02', $formatter->interpolateMessage('{when}', ['when' => $date], 'Y-m-d'));
    }

    public function testPlainEnumsAreInterpolatedByName(): void
    {
        $this->assertSame('Case', (new ExposedFormatter())->interpolateMessage('{e}', ['e' => PlainEnum::Case]));
    }

    #[DataProvider('scalarProvider')]
    public function testScalarsAreNormalizedUnchanged(mixed $value): void
    {
        $this->assertSame($value, (new ExposedFormatter())->normalizeData($value));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function scalarProvider(): iterable
    {
        yield 'string' => ['text'];
        yield 'integer' => [5];
        yield 'float' => [1.5];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
    }

    public function testArraysAreNormalizedRecursively(): void
    {
        $date = new DateTimeImmutable('2025-01-02 03:04:05 UTC');

        $result = (new ExposedFormatter())->normalizeData(['a' => 1, 'b' => ['c' => $date, 'd' => STDIN]]);

        $this->assertSame(['a' => 1, 'b' => ['c' => '2025-01-02T03:04:05+00:00', 'd' => '[resource (stream)]']], $result);
    }

    public function testDeeplyNestedStructuresAreTruncated(): void
    {
        $data = ['a' => ['b' => ['c' => ['d' => ['e' => 'deep']]]]];

        $this->assertSame(['a' => ['b' => ['c' => ['d' => '[array]']]]], (new ExposedFormatter())->normalizeData($data));
    }

    public function testDepthIsConfigurable(): void
    {
        $data = ['a' => ['b' => 'c']];

        $this->assertSame(['a' => '[array]'], (new ExposedFormatter())->normalizeData($data, depth: 1));
        $this->assertSame('[array]', (new ExposedFormatter())->normalizeData($data, depth: 0));
    }

    public function testObjectsBeyondTheDepthAreSummarized(): void
    {
        $this->assertSame('[object stdClass]', (new ExposedFormatter())->normalizeData(new stdClass(), depth: 0));
    }

    public function testDateTimesAreFormatted(): void
    {
        $date = new DateTimeImmutable('2025-01-02 03:04:05.5 UTC');

        $this->assertSame('2025-01-02', (new ExposedFormatter())->normalizeData($date, 'Y-m-d'));
    }

    public function testObjectsAreWrappedWithTheirClassName(): void
    {
        $object = new class {
            public string $name = 'public';
        };

        $result = (new ExposedFormatter())->normalizeData($object);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame($object, array_values($result)[0]);
    }

    public function testStringableObjectsAreConvertedToStrings(): void
    {
        $object = new class implements Stringable {
            public function __toString(): string
            {
                return 'as string';
            }
        };

        $this->assertSame(['as string'], array_values((array) (new ExposedFormatter())->normalizeData($object)));
    }

    public function testJsonSerializableObjectsUseTheirSerialization(): void
    {
        $object = new class implements JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['serialized' => true];
            }
        };

        $this->assertSame(['serialized' => true], array_values((array) (new ExposedFormatter())->normalizeData($object))[0]);
    }

    public function testArrayObjectsAreWrappedWithTheirClassName(): void
    {
        $result = (new ExposedFormatter())->normalizeData(new ArrayObject([1, 2]));

        $this->assertArrayHasKey(ArrayObject::class, (array) $result);
    }

    public function testThrowablesAreNormalizedWithoutLeakingArguments(): void
    {
        $result = (new ExposedFormatter())->normalizeData($this->failure('secret-argument'));

        $this->assertSame(RuntimeException::class, $result['class']);
        $this->assertSame('failure', $result['message']);
        $this->assertSame(7, $result['code']);
        $this->assertStringContainsString(__FILE__ . ':', $result['file']);
        $this->assertContainsOnlyString($result['trace']);
        $this->assertStringNotContainsString('secret-argument', (string) json_encode($result));
    }

    public function testPreviousThrowablesAreNormalized(): void
    {
        $exception = new RuntimeException('outer', 1, new \LogicException('inner', 2));

        $result = (new ExposedFormatter())->normalizeData($exception);

        $this->assertSame(\LogicException::class, $result['previous']['class']);
        $this->assertSame('inner', $result['previous']['message']);
        $this->assertArrayNotHasKey('previous', $result['previous']);
    }

    public function testResourcesAreSummarized(): void
    {
        $this->assertSame('[resource (stream)]', (new ExposedFormatter())->normalizeData(STDIN));
    }

    public function testClosedResourcesAreSummarized(): void
    {
        $resource = fopen('php://memory', 'r');
        fclose($resource);

        $this->assertSame('[unknown resource (closed)]', (new ExposedFormatter())->normalizeData($resource));
    }

    private function failure(string $secret): RuntimeException
    {
        return new RuntimeException('failure', 7);
    }
}

enum PlainEnum
{
    case Case;
}
