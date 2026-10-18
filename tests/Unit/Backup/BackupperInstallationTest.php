<?php

namespace Formwork\Tests\Unit\Backup;

use Formwork\Backup\Backupper;
use Formwork\Cms\App;
use Formwork\Http\Request;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use ZipArchive;

#[CoversClass(Backupper::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class BackupperInstallationTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TESTS_TMP_PATH . '/installation';
        FileSystem::createDirectory($this->root . '/site/pages', recursive: true);
        FileSystem::createDirectory($this->root . '/cache', recursive: true);
        FileSystem::write($this->root . '/index.php', '<?php // index');
        FileSystem::write($this->root . '/site/pages/page.md', 'content');
        FileSystem::write($this->root . '/cache/data.php', 'cached');
    }

    public function testTheWholeInstallationIsArchivedExceptIgnoredFiles(): void
    {
        $destination = $this->backupper(['ignore' => ['cache/*']])->backup('installation', 'example.com');

        $this->assertEqualsCanonicalizing(['index.php', 'site/pages/page.md'], $this->entries($destination));
    }

    public function testTheArchiveIsWrittenInTheConfiguredPath(): void
    {
        $destination = $this->backupper()->backup('installation', 'example.com');

        $this->assertSame(TESTS_TMP_PATH . '/backups', dirname($destination));
    }

    public function testSiteTitleComesFromTheApplication(): void
    {
        $destination = $this->backupper()->backup('{{site}}', 'example.com');

        $this->assertStringStartsWith(Str::slug((string) App::instance()->site()->title()) . '-', basename($destination));
    }

    public function testFilesOfOtherInstallationsAreNeverArchived(): void
    {
        $destination = $this->backupper()->backup('installation', 'example.com');

        $this->assertNotContains('composer.json', $this->entries($destination));
        $this->assertNotContains('formwork/src/Cms/App.php', $this->entries($destination));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function backupper(array $options = []): Backupper
    {
        return new Backupper(new Request([], [], [], [], []), [
            'path'             => TESTS_TMP_PATH . '/backups',
            'name'             => '{{hostname}}-backup',
            'maxExecutionTime' => '180',
            'maxFiles'         => 10,
            'ignore'           => [],
            ...$options,
        ], $this->root, App::instance());
    }

    /**
     * @return list<string>
     */
    private function entries(string $archive): array
    {
        $zipArchive = new ZipArchive();
        $this->assertTrue($zipArchive->open($archive));

        $entries = [];
        for ($i = 0; $i < $zipArchive->numFiles; $i++) {
            $entries[] = (string) $zipArchive->getNameIndex($i);
        }
        $zipArchive->close();

        return $entries;
    }
}
