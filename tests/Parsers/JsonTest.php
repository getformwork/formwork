<?php

namespace Formwork\Tests\Parsers;

use Formwork\Parsers\Json;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
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

    public function testEncodeEmptyArrayWithForceObjectOption(): void
    {
        $data = [];

        $this->assertJsonStringEqualsJsonString('{}', Json::encode($data, ['forceObject' => true]));
    }
}
