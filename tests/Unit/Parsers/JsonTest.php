<?php

namespace Formwork\Tests\Unit\Parsers;

use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Json::class)]
class JsonTest extends TestCase
{
    protected function setUp(): void
    {
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/files/json', TESTS_TMP_PATH, overwrite: true);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    public function testParse(): void
    {
        $json = <<<JSON
            {
                "title": "Test",
                "description": "This is a test.",
                "tags": ["php", "json", "parser"]
            }
            JSON;

        $expected = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'json', 'parser'],
        ];

        $this->assertSame($expected, Json::parse($json));
    }

    public function testParseFile(): void
    {
        $jsonFilePath = TESTS_TMP_PATH . '/test.json';

        $expected = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'json', 'parser'],
        ];

        $this->assertSame($expected, Json::parseFile($jsonFilePath));
    }

    public function testParseRejectsInvalidJson(): void
    {
        $this->expectException(JsonException::class);
        Json::parse('{"title": ');
    }

    public function testEncode(): void
    {
        $data = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'json', 'parser'],
        ];

        $expected = '{"title":"Test","description":"This is a test.","tags":["php","json","parser"]}';

        $this->assertJsonStringEqualsJsonString($expected, Json::encode($data));
    }

    public function testEncodeWithPrettyPrint(): void
    {
        $data = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'json', 'parser'],
        ];

        $expected = <<<JSON
            {
                "title": "Test",
                "description": "This is a test.",
                "tags": [
                    "php",
                    "json",
                    "parser"
                ]
            }
            JSON;

        $this->assertJsonStringEqualsJsonString($expected, Json::encode($data, ['prettyPrint' => true]));
    }

    public function testEncodeEmptyArrayAlwaysProducesAnObject(): void
    {
        $this->assertSame('{}', Json::encode([]));
        $this->assertSame('{}', Json::encode([], ['forceObject' => true]));
    }

    public function testEncodeKeepsSlashesAndUnicodeUnlessEscapingIsRequested(): void
    {
        $this->assertSame('{"path":"/a/b","name":"è"}', Json::encode(['path' => '/a/b', 'name' => 'è']));
        $this->assertSame('{"name":"\\u00e8"}', Json::encode(['name' => 'è'], ['escapeUnicode' => true]));
    }

    public function testEncodePreservesZeroFractions(): void
    {
        $this->assertSame('[1.0,2]', Json::encode([1.0, 2]));
    }

    public function testNestedEmptyArraysAreStillEncodedAsLists(): void
    {
        $this->assertSame('{"items":[],"map":{"a":[]}}', Json::encode(['items' => [], 'map' => ['a' => []]]));
    }

    public function testForceObjectEncodesListsAsObjects(): void
    {
        $this->assertSame('[1,2]', Json::encode([1, 2]));
        $this->assertSame('{"0":1,"1":2}', Json::encode([1, 2], ['forceObject' => true]));
    }

    public function testRoundTripPreservesMeaningfulScalarTypes(): void
    {
        $data = [
            'null'     => null,
            'false'    => false,
            'zero'     => 0,
            'fraction' => 1.0,
            'empty'    => '',
            'list'     => [],
            'nested'   => ['value' => 'text'],
        ];

        $this->assertSame($data, Json::parse(Json::encode($data)));
    }
}
