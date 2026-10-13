<?php

namespace Formwork\Tests\Unit\Interpolator;

use Formwork\Interpolator\Nodes\AbstractNode;
use Formwork\Interpolator\Nodes\ArgumentsNode;
use Formwork\Interpolator\Nodes\ArrayKeysNode;
use Formwork\Interpolator\Nodes\ArrayNode;
use Formwork\Interpolator\Nodes\IdentifierNode;
use Formwork\Interpolator\Nodes\ImplicitArrayKeyNode;
use Formwork\Interpolator\Nodes\NumberNode;
use Formwork\Interpolator\Nodes\StringNode;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

#[CoversClass(AbstractNode::class)]
#[CoversClass(ArgumentsNode::class)]
#[CoversClass(ArrayKeysNode::class)]
#[CoversClass(ArrayNode::class)]
#[CoversClass(IdentifierNode::class)]
#[CoversClass(ImplicitArrayKeyNode::class)]
#[CoversClass(NumberNode::class)]
#[CoversClass(StringNode::class)]
final class NodesTest extends TestCase
{
    #[DataProvider('nodeProvider')]
    public function testNodesExposeTheirTypeAndValue(AbstractNode $node, string $type, mixed $value): void
    {
        $this->assertSame($type, $node->type());
        $this->assertEquals($value, $node->value());
        $this->assertSame('node of type ' . $type, (string) $node);
    }

    /**
     * @return iterable<string, array{AbstractNode, string, mixed}>
     */
    public static function nodeProvider(): iterable
    {
        yield 'identifier' => [new IdentifierNode('name', null, null), 'identifier', 'name'];
        yield 'integer' => [new NumberNode(5), 'number', 5];
        yield 'float' => [new NumberNode(1.5), 'number', 1.5];
        yield 'string' => [new StringNode('text'), 'string', 'text'];
        yield 'empty string' => [new StringNode(''), 'string', ''];
        yield 'arguments' => [new ArgumentsNode([new NumberNode(1)]), 'arguments', [new NumberNode(1)]];
        yield 'empty arguments' => [new ArgumentsNode([]), 'arguments', []];
        yield 'array keys' => [new ArrayKeysNode([new ImplicitArrayKeyNode()]), 'array keys', [new ImplicitArrayKeyNode()]];
        yield 'array' => [new ArrayNode([new NumberNode(1)], new ArrayKeysNode([new ImplicitArrayKeyNode()])), 'array', [new NumberNode(1)]];
    }

    public function testNodeTypesAreDistinct(): void
    {
        $types = [
            IdentifierNode::TYPE,
            NumberNode::TYPE,
            StringNode::TYPE,
            ArgumentsNode::TYPE,
            ArrayKeysNode::TYPE,
            ArrayNode::TYPE,
            ImplicitArrayKeyNode::TYPE,
        ];

        $this->assertSame($types, array_values(array_unique($types)));
    }

    public function testIdentifierNodeExposesArgumentsAndTraversal(): void
    {
        $arguments = new ArgumentsNode([new StringNode('x')]);
        $traverse = new IdentifierNode('child', null, null);

        $node = new IdentifierNode('parent', $arguments, $traverse);

        $this->assertSame($arguments, $node->arguments());
        $this->assertSame($traverse, $node->traverse());
    }

    public function testIdentifierNodeWithoutArgumentsOrTraversal(): void
    {
        $node = new IdentifierNode('name', null, null);

        $this->assertNull($node->arguments());
        $this->assertNull($node->traverse());
    }

    public function testArrayNodeExposesItsKeys(): void
    {
        $keys = new ArrayKeysNode([new StringNode('key')]);

        $this->assertSame($keys, (new ArrayNode([new NumberNode(1)], $keys))->keys());
    }

    public function testImplicitArrayKeyNodeHasTheItsTypeAndNoValue(): void
    {
        $node = new ImplicitArrayKeyNode();

        $this->assertSame('implicit array key', $node->type());
        $this->assertSame('node of type implicit array key', (string) $node);
        $this->assertNull($node->value());
    }

    public function testNumberNodesRejectNonNumericValues(): void
    {
        $this->expectException(TypeError::class);
        new NumberNode('not a number');
    }

    public function testStringNodesRejectArrays(): void
    {
        $this->expectException(TypeError::class);
        new StringNode([]);
    }
}
