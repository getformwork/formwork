<?php

namespace Formwork\Tests\Parsers;

use Formwork\Parsers\Yaml;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Yaml::class)]
final class YamlTest extends TestCase
{
    protected function setUp(): void
    {
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/files/yaml', TESTS_TMP_PATH, overwrite: true);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    public function testParse(): void
    {
        $yamlString = <<<YAML
            title: Test
            description: This is a test.
            tags:
              - php
              - yaml
              - parser
            YAML;

        $expected = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'yaml', 'parser'],
        ];

        $this->assertSame($expected, Yaml::parse($yamlString));
    }

    public function testParseFile(): void
    {
        $yamlFilePath = TESTS_TMP_PATH . '/test.yaml';

        $expected = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'yaml', 'parser'],
        ];

        $this->assertSame($expected, Yaml::parseFile($yamlFilePath));
    }

    public function testEncode(): void
    {
        $data = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'yaml', 'parser'],
        ];

        $expectedYamlString = <<<YAML
            title: Test
            description: 'This is a test.'
            tags:
                - php
                - yaml
                - parser

            YAML;

        $this->assertSame($expectedYamlString, Yaml::encode($data));
    }

    public function testEncodeToFile(): void
    {
        $data = [
            'title'       => 'Test',
            'description' => 'This is a test.',
            'tags'        => ['php', 'yaml', 'parser'],
        ];

        $yamlFilePath = TESTS_TMP_PATH . '/output.yaml';

        Yaml::encodeToFile($data, $yamlFilePath);

        $this->assertFileExists($yamlFilePath);
        $this->assertSame(Yaml::encode($data), FileSystem::read($yamlFilePath));
    }

    public function testEncodeReturnsEmptyStringForEmptyData(): void
    {
        $this->assertSame('', Yaml::encode([]));
    }
}
