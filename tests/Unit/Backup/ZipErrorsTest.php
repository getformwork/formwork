<?php

namespace Formwork\Tests\Unit\Backup;

use Formwork\Backup\Utils\ZipErrors;
use Formwork\Parsers\Yaml;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ZipArchive;

#[CoversClass(ZipErrors::class)]
final class ZipErrorsTest extends TestCase
{
    public function testEveryErrorHasBothAMessageAndALanguageString(): void
    {
        $this->assertSame(array_keys(ZipErrors::ERROR_MESSAGES), array_keys(ZipErrors::ERROR_LANGUAGE_STRINGS));
    }

    public function testMessagesAreNotEmpty(): void
    {
        foreach (ZipErrors::ERROR_MESSAGES as $code => $message) {
            $this->assertNotSame('', trim($message), sprintf('Error %d has no message', $code));
        }
    }

    public function testLanguageStringsExistInEveryShippedTranslation(): void
    {
        $strings = array_unique(ZipErrors::ERROR_LANGUAGE_STRINGS);

        foreach (['en', 'it'] as $language) {
            /** @var array<string, string> $translation */
            $translation = Yaml::parseFile(SYSTEM_PATH . "/translations/{$language}.yaml");

            foreach ($strings as $string) {
                $this->assertArrayHasKey($string, $translation, sprintf('"%s" is missing from the "%s" translation', $string, $language));
            }
        }
    }

    public function testKnownZipArchiveErrorCodesAreCovered(): void
    {
        foreach ([ZipArchive::ER_NOZIP, ZipArchive::ER_OPEN, ZipArchive::ER_NOENT, ZipArchive::ER_READ, ZipArchive::ER_EXISTS, ZipArchive::ER_INCONS, ZipArchive::ER_MEMORY, ZipArchive::ER_INVAL] as $code) {
            $this->assertArrayHasKey($code, ZipErrors::ERROR_MESSAGES);
        }
    }

    public function testOpeningAFileThatIsNotAZipProducesACoveredCode(): void
    {
        $this->setUpTempDirectory();
        file_put_contents(TESTS_TMP_PATH . '/not-a-zip.zip', 'plain text');

        try {
            $status = (new ZipArchive())->open(TESTS_TMP_PATH . '/not-a-zip.zip', ZipArchive::RDONLY);
        } finally {
            $this->tearDownTempDirectory();
        }

        $this->assertNotTrue($status);
        $this->assertArrayHasKey($status, ZipErrors::ERROR_MESSAGES);
    }
}
