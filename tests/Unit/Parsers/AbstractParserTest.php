<?php

namespace Formwork\Tests\Unit\Parsers;

use Formwork\Parsers\AbstractEncoder;
use Formwork\Parsers\AbstractParser;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Parsers\Fixtures\EchoEncoderFixture;
use Formwork\Tests\Unit\Parsers\Fixtures\EchoParserFixture;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractParser::class)]
#[CoversClass(AbstractEncoder::class)]
#[UsesTempDirectory]
final class AbstractParserTest extends TestCase
{
    public function testParseFileReadsTheFileAndForwardsOptions(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/input.txt', 'content');
        $this->assertSame(['content', ['flag' => true]], EchoParserFixture::parseFile(TESTS_TMP_PATH . '/input.txt', ['flag' => true]));
    }

    public function testParseFileOfMissingFilesFails(): void
    {
        $this->expectException(FileNotFoundException::class);

        EchoParserFixture::parseFile(TESTS_TMP_PATH . '/missing.txt');
    }

    public function testEncodeToFileWritesTheEncodedData(): void
    {
        $this->assertTrue(EchoEncoderFixture::encodeToFile(['a' => 1], TESTS_TMP_PATH . '/out.txt', ['pretty' => true]));
        $this->assertSame('encoded:{"a":1}:{"pretty":true}', FileSystem::read(TESTS_TMP_PATH . '/out.txt'));
    }

    public function testEncodeToFileReplacesExistingContent(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/out.txt', str_repeat('x', 1000));

        EchoEncoderFixture::encodeToFile([], TESTS_TMP_PATH . '/out.txt');

        $this->assertSame('encoded:[]:[]', FileSystem::read(TESTS_TMP_PATH . '/out.txt'));
    }
}
