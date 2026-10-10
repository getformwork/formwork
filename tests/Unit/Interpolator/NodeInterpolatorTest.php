<?php

namespace Formwork\Tests\Unit\Interpolator;

use Formwork\Interpolator\Exceptions\InterpolationException;
use Formwork\Interpolator\NodeInterpolator;
use Formwork\Interpolator\Nodes\ArgumentsNode;
use Formwork\Interpolator\Nodes\ArrayKeysNode;
use Formwork\Interpolator\Nodes\ArrayNode;
use Formwork\Interpolator\Nodes\IdentifierNode;
use Formwork\Interpolator\Nodes\ImplicitArrayKeyNode;
use Formwork\Interpolator\Nodes\NumberNode;
use Formwork\Interpolator\Nodes\StringNode;
use Formwork\Interpolator\Parser;
use Formwork\Interpolator\Tokenizer;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Interpolator\Fixtures\InterpolationTarget;
use Formwork\Tests\Unit\Interpolator\Fixtures\MagicGetter;
use Formwork\Tests\Unit\Interpolator\Fixtures\MagicTarget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(NodeInterpolator::class)]
final class NodeInterpolatorTest extends TestCase
{
    public function testVariablesAreReadFromTheGivenVars(): void
    {
        $this->assertSame(5, $this->interpolate('number', ['number' => 5]));
        $this->assertSame('text', $this->interpolate('string', ['string' => 'text']));
        $this->assertNull($this->interpolate('nothing', ['nothing' => null]));
        $this->assertFalse($this->interpolate('no', ['no' => false]));
        $this->assertSame([1, 2], $this->interpolate('list', ['list' => [1, 2]]));
    }

    public function testUndefinedVariablesAreReported(): void
    {
        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage('Undefined variable "missing"');
        $this->interpolate('missing', []);
    }

    public function testNullVariablesAreDefined(): void
    {
        $this->assertNull($this->interpolate('nothing', ['nothing' => null]));
    }

    public function testOnlyIdentifierNodesCanBeInterpolatedAtTheTopLevel(): void
    {
        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage('Unexpected node of type number');
        (new NodeInterpolator(new NumberNode(1), []))->interpolate();
    }

    #[DataProvider('arrayAccessProvider')]
    public function testArraysAreTraversed(string $expression, mixed $expected): void
    {
        $vars = ['data' => ['key' => 'value', 'nested' => ['deep' => 'down', 0 => 'zero'], 0 => 'first', '10' => 'ten']];

        $this->assertSame($expected, $this->interpolate($expression, $vars));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function arrayAccessProvider(): iterable
    {
        yield 'dot notation' => ['data.key', 'value'];
        yield 'nested dot notation' => ['data.nested.deep', 'down'];
        yield 'brackets with a string' => ['data["key"]', 'value'];
        yield 'brackets with single quotes' => ["data['key']", 'value'];
        yield 'brackets with an integer' => ['data[0]', 'first'];
        yield 'brackets with a numeric key' => ['data[10]', 'ten'];
        yield 'brackets after dot notation' => ['data.nested[0]', 'zero'];
        yield 'brackets with a string after dot notation' => ['data.nested["deep"]', 'down'];
        yield 'whole subtree' => ['data.nested', ['deep' => 'down', 0 => 'zero']];
    }

    #[DataProvider('undefinedKeyProvider')]
    public function testUndefinedArrayKeysAreReported(string $expression, string $message): void
    {
        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage($message);
        $this->interpolate($expression, ['data' => ['key' => 'value', 'nested' => []]]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function undefinedKeyProvider(): iterable
    {
        yield 'dot notation' => ['data.missing', 'Undefined array key "missing"'];
        yield 'brackets with a string' => ['data["missing"]', 'Undefined array key "missing"'];
        yield 'brackets with an integer' => ['data[5]', 'Undefined array key "5"'];
        yield 'nested' => ['data.nested.missing', 'Undefined array key "missing"'];
    }

    public function testNullValuesAreAccessibleThroughTheirKey(): void
    {
        $this->assertNull($this->interpolate('data.key', ['data' => ['key' => null]]));
    }

    #[DataProvider('scalarValueProvider')]
    public function testScalarsCannotBeTraversed(mixed $value, string $expression): void
    {
        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage('cannot be traversed');
        $this->interpolate($expression, ['value' => $value]);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function scalarValueProvider(): iterable
    {
        yield 'string with dot notation' => ['text', 'value.length'];
        yield 'integer with dot notation' => [5, 'value.x'];
        yield 'float with brackets' => [1.5, 'value[0]'];
        yield 'true with dot notation' => [true, 'value.x'];
        yield 'false with brackets' => [false, 'value["x"]'];
        yield 'zero with dot notation' => [0, 'value.x'];
    }

    public function testNullValuesCannotBeTraversedIntoTheGlobalVariables(): void
    {
        // A null value must never make the lookup fall back to the top level variables
        $vars = ['empty' => null, 'secret' => 'top level value'];

        try {
            $result = $this->interpolate('empty.secret', $vars);
            $this->fail('Traversing null should fail, got: ' . var_export($result, true));
        } catch (InterpolationException $exception) {
            $this->assertStringNotContainsString('top level value', $exception->getMessage());
        }
    }

    public function testNullValuesCannotBeTraversedWithBrackets(): void
    {
        $this->expectException(InterpolationException::class);
        $this->interpolate('empty["key"]', ['empty' => null]);
    }

    public function testResourcesCannotBeTraversed(): void
    {
        $resource = fopen('php://memory', 'r');

        try {
            $this->expectException(InterpolationException::class);
            $this->expectExceptionMessage('Resources cannot be traversed');
            $this->interpolate('stream.x', ['stream' => $resource]);
        } finally {
            fclose($resource);
        }
    }

    public function testObjectPropertiesMethodsAndConstantsAreAccessible(): void
    {
        $vars = ['object' => new InterpolationTarget()];

        $this->assertSame('public', $this->interpolate('object.publicProperty', $vars));
        $this->assertSame('method(null)', $this->interpolate('object.method', $vars));
        $this->assertSame('method(1)', $this->interpolate('object.method(1)', $vars));
        $this->assertSame('method("text")', $this->interpolate('object.method("text")', $vars));
        $this->assertSame('constant', $this->interpolate('object.PUBLIC_CONSTANT', $vars));
        $this->assertSame('static', $this->interpolate('object.staticMethod', $vars));
    }

    public function testMethodsReceiveInterpolatedArguments(): void
    {
        $vars = ['object' => new InterpolationTarget(), 'number' => 7, 'list' => ['a' => 'b']];

        $this->assertSame('method(7)', $this->interpolate('object.method(number)', $vars));
        $this->assertSame('method("b")', $this->interpolate('object.method(list.a)', $vars));
        $this->assertSame('method([1,2])', $this->interpolate('object.method([1, 2])', $vars));
        $this->assertSame(3, $this->interpolate('object.variadic(1, 2, 3)', $vars));
        $this->assertSame(0, $this->interpolate('object.variadic()', $vars));
    }

    public function testMethodResultsCanBeTraversed(): void
    {
        $vars = ['object' => new InterpolationTarget()];

        $this->assertSame('value', $this->interpolate('object.items.key', $vars));
        $this->assertSame('one', $this->interpolate('object.items[1]', $vars));
        $this->assertSame('public', $this->interpolate('object.self.publicProperty', $vars));
        $this->assertSame('public', $this->interpolate('object.self().self().publicProperty', $vars));
    }

    public function testNullPropertiesAreReturned(): void
    {
        $this->assertNull($this->interpolate('object.nullProperty', ['object' => new InterpolationTarget()]));
    }

    public function testNullMethodResultsCannotBeTraversedIntoTheGlobalVariables(): void
    {
        $vars = ['object' => new InterpolationTarget(), 'publicProperty' => 'global'];

        $this->expectException(InterpolationException::class);
        $this->interpolate('object.nothing.publicProperty', $vars);
    }

    #[DataProvider('inaccessibleMemberProvider')]
    public function testNonPublicMembersAreReportedAsInterpolationErrors(string $expression): void
    {
        $this->expectException(InterpolationException::class);
        $this->interpolate($expression, ['object' => new InterpolationTarget()]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function inaccessibleMemberProvider(): iterable
    {
        yield 'private method' => ['object.privateMethod'];
        yield 'protected method' => ['object.protectedMethod'];
        yield 'private method with arguments' => ['object.privateMethod()'];
        yield 'private property' => ['object.privateProperty'];
        yield 'protected property' => ['object.protectedProperty'];
        yield 'private constant' => ['object.PRIVATE_CONSTANT'];
        yield 'undefined member' => ['object.missing'];
    }

    public function testPropertiesCannotBeCalled(): void
    {
        $this->expectException(InterpolationException::class);
        $this->interpolate('object.publicProperty(1)', ['object' => new InterpolationTarget()]);
    }

    public function testMagicMethodsAreCalledForUndefinedMethods(): void
    {
        $vars = ['magic' => new MagicTarget()];

        $this->assertSame('called:1,2', $this->interpolate('magic.dynamic(1, 2)', $vars));
    }

    public function testMagicGettersAreUsedForUndefinedProperties(): void
    {
        $vars = ['magic' => new MagicGetter()];

        $this->assertSame('magic:anything', $this->interpolate('magic.anything', $vars));
    }

    public function testPublicPropertiesAreNotShadowedByMagicMethods(): void
    {
        $this->assertSame('real', $this->interpolate('magic.realProperty', ['magic' => new MagicTarget()]));
    }

    public function testClosuresAreReturnedWhenNoArgumentsAreGiven(): void
    {
        $closure = static fn(int $x = 0): int => $x + 1;

        $this->assertSame($closure, $this->interpolate('increment', ['increment' => $closure]));
    }

    public function testClosuresAreCalledWhenArgumentsAreGiven(): void
    {
        $vars = ['increment' => static fn(int $x = 0): int => $x + 1, 'number' => 4];

        $this->assertSame(1, $this->interpolate('increment()', $vars));
        $this->assertSame(6, $this->interpolate('increment(5)', $vars));
        $this->assertSame(5, $this->interpolate('increment(number)', $vars));
    }

    public function testClosuresInsideArraysAreCalled(): void
    {
        $vars = ['helpers' => ['double' => static fn(int $x): int => $x * 2]];

        $this->assertSame(8, $this->interpolate('helpers.double(4)', $vars));
    }

    public function testResultsOfClosuresCanBeTraversed(): void
    {
        $vars = ['build' => static fn(string $name): array => ['name' => $name, 'items' => [1, 2]]];

        $this->assertSame('example', $this->interpolate('build("example").name', $vars));
        $this->assertSame(2, $this->interpolate('build("example").items[1]', $vars));
    }

    public function testResultsOfClosuresWithoutArgumentsCanBeTraversed(): void
    {
        $vars = ['build' => static fn(): array => ['name' => 'built']];

        $this->assertSame('built', $this->interpolate('build().name', $vars));
    }

    public function testExceptionsThrownByCalledFunctionsPropagate(): void
    {
        $vars = ['fail' => static function (): never {
            throw new \DomainException('function failed');
        }];

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('function failed');
        $this->interpolate('fail()', $vars);
    }

    /**
     * @param array<array-key, mixed> $expected
     */
    #[DataProvider('arrayExpressionProvider')]
    public function testArrayExpressions(string $expression, array $expected): void
    {
        $vars = ['identity' => static fn(mixed $value): mixed => $value, 'key' => 'dynamic', 'value' => 'v'];

        $this->assertSame($expected, $this->interpolate("identity({$expression})", $vars));
    }

    /**
     * @return iterable<string, array{string, array<array-key, mixed>}>
     */
    public static function arrayExpressionProvider(): iterable
    {
        yield 'empty array' => ['[]', []];
        yield 'list' => ['[1, 2, 3]', [1, 2, 3]];
        yield 'list of strings' => ['["a", \'b\']', ['a', 'b']];
        yield 'explicit string keys' => ['["a" => 1, "b" => 2]', ['a' => 1, 'b' => 2]];
        yield 'explicit integer keys' => ['[5 => "a", 7 => "b"]', [5 => 'a', 7 => 'b']];
        yield 'implicit keys continue after an explicit integer key' => ['[5 => "a", "b"]', [5 => 'a', 6 => 'b']];
        yield 'implicit keys ignore string keys' => ['["k" => "a", "b"]', ['k' => 'a', 0 => 'b']];
        yield 'implicit keys continue after negative keys' => ['[-5 => "a", "b"]', [-5 => 'a', -4 => 'b']];
        yield 'implicit keys before explicit keys' => ['["a", "b", 5 => "c", "d"]', ['a', 'b', 5 => 'c', 6 => 'd']];
        yield 'later keys overwrite earlier ones' => ['[1 => "a", 1 => "b"]', [1 => 'b']];
        yield 'keys from variables' => ['[key => "x"]', ['dynamic' => 'x']];
        yield 'values from variables' => ['[value, key]', ['v', 'dynamic']];
        yield 'nested arrays' => ['[[1], ["a" => [2]]]', [[1], ['a' => [2]]]];
        yield 'numeric string key becomes an integer' => ['["5" => "a"]', [5 => 'a']];
        yield 'numeric string with a leading zero stays a string' => ['["05" => "a"]', ['05' => 'a']];
        yield 'float key is truncated' => ['[1.9 => "a"]', [1 => 'a']];
        yield 'values are interpolated' => ['[identity(1), identity("x")]', [1, 'x']];
    }

    #[DataProvider('invalidKeyProvider')]
    public function testNonScalarArrayKeysAreRejected(string $expression): void
    {
        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage('Invalid non-scalar array key');
        $this->interpolate($expression, ['identity' => static fn(mixed $value): mixed => $value, 'list' => [1], 'object' => new InterpolationTarget()]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidKeyProvider(): iterable
    {
        yield 'array variable' => ['identity([list => 1])'];
        yield 'object variable' => ['identity([object => 1])'];
    }

    public function testNullKeysBecomeEmptyStrings(): void
    {
        $result = $this->interpolate('identity([nothing => 1])', ['identity' => static fn(mixed $value): mixed => $value, 'nothing' => null]);

        $this->assertSame(['' => 1], $result);
    }

    public function testBooleanKeysBecomeIntegers(): void
    {
        $result = $this->interpolate('identity([yes => "a", no => "b"])', ['identity' => static fn(mixed $value): mixed => $value, 'yes' => true, 'no' => false]);

        $this->assertSame([1 => 'a', 0 => 'b'], $result);
    }

    public function testNodesCanBeInterpolatedWithoutParsing(): void
    {
        $node = new IdentifierNode(
            'join',
            new ArgumentsNode([
                new ArrayNode([new StringNode('a'), new NumberNode(2)], new ArrayKeysNode([new ImplicitArrayKeyNode(), new ImplicitArrayKeyNode()])),
            ]),
            null,
        );

        $result = (new NodeInterpolator($node, ['join' => static fn(array $parts): string => implode('-', $parts)]))->interpolate();

        $this->assertSame('a-2', $result);
    }

    public function testUnknownKeyNodeTypesAreRejected(): void
    {
        $node = new ArrayNode([new NumberNode(1)], new ArrayKeysNode([new ArgumentsNode([])]));
        $identifier = new IdentifierNode('identity', new ArgumentsNode([$node]), null);

        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage('Invalid array key type "arguments"');
        (new NodeInterpolator($identifier, ['identity' => static fn(mixed $value): mixed => $value]))->interpolate();
    }

    public function testTraversingWithAnInvalidNodeIsRejected(): void
    {
        $node = new IdentifierNode('data', null, new ArgumentsNode([]));

        $this->expectException(InterpolationException::class);
        $this->expectExceptionMessage('Invalid array key');
        (new NodeInterpolator($node, ['data' => [1]]))->interpolate();
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function interpolate(string $expression, array $vars): mixed
    {
        return (new NodeInterpolator(Parser::parseTokenStream(Tokenizer::tokenizeString($expression)), $vars))->interpolate();
    }
}
