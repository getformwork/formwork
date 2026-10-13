<?php

namespace Formwork\Tests\Unit\Parsers;

use Formwork\Parsers\Yaml;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Yaml\Exception\ParseException;

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

    public function testParseRejectsInvalidYaml(): void
    {
        $this->expectException(ParseException::class);
        Yaml::parse('title: [unclosed');
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
        $this->assertSame([], Yaml::parse(''));
    }

    public function testRoundTripPreservesNestedData(): void
    {
        $data = [
            'title'   => 'Test',
            'enabled' => false,
            'count'   => 0,
            'tags'    => ['php', 'cms'],
            'nested'  => ['value' => 'text'],
        ];

        $this->assertSame($data, Yaml::parse(Yaml::encode($data)));
    }

    public function testRoundTripKeepsStringsThatLookLikeOtherTypes(): void
    {
        $data = [
            'number'    => '123',
            'boolean'   => 'true',
            'null'      => 'null',
            'tilde'     => '~',
            'yes'       => 'yes',
            'float'     => '1.0',
            'empty'     => '',
            'colon'     => 'with: colon',
            'dash'      => '- dash',
            'hash'      => '#hash',
            'padded'    => ' padded ',
            'tab'       => "tab\there",
            'multiline' => "first line\nsecond line\n",
        ];

        $this->assertSame($data, Yaml::parse(Yaml::encode($data)));
    }
}
