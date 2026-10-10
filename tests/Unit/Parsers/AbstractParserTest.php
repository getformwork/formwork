<?php

namespace Formwork\Tests\Unit\Parsers;

use Formwork\Parsers\AbstractEncoder;
use Formwork\Parsers\AbstractParser;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Parsers\Fixtures\EchoEncoder;
use Formwork\Tests\Unit\Parsers\Fixtures\EchoParser;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractParser::class)]
#[CoversClass(AbstractEncoder::class)]
final class AbstractParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testParseFileReadsTheFileAndForwardsOptions(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/input.txt', 'content');
        $this->assertSame(['content', ['flag' => true]], EchoParser::parseFile(TESTS_TMP_PATH . '/input.txt', ['flag' => true]));
    }

    public function testParseFileOfMissingFilesFails(): void
    {
        $this->expectException(FileNotFoundException::class);

        EchoParser::parseFile(TESTS_TMP_PATH . '/missing.txt');
    }

    public function testEncodeToFileWritesTheEncodedData(): void
    {
        $this->assertTrue(EchoEncoder::encodeToFile(['a' => 1], TESTS_TMP_PATH . '/out.txt', ['pretty' => true]));
        $this->assertSame('encoded:{"a":1}:{"pretty":true}', FileSystem::read(TESTS_TMP_PATH . '/out.txt'));
    }

    public function testEncodeToFileReplacesExistingContent(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/out.txt', str_repeat('x', 1000));

        EchoEncoder::encodeToFile([], TESTS_TMP_PATH . '/out.txt');

        $this->assertSame('encoded:[]:[]', FileSystem::read(TESTS_TMP_PATH . '/out.txt'));
    }
}
