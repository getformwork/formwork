<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Session\Handler\FileSessionHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(FileSessionHandler::class)]
#[UsesTempDirectory]
final class FileSessionHandlerTest extends TestCase
{
    public function testHandlerReadsWritesValidatesAndDestroysSessions(): void
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'sessions');
        FileSystem::copyDirectory(__DIR__ . '/Fixtures/sessions', $path);
        $fileSessionHandler = new FileSessionHandler($path);

        $this->assertTrue($fileSessionHandler->open('', 'formwork'));
        $this->assertTrue($fileSessionHandler->validateId('existing'));
        $this->assertSame("user|s:9:\"Test User\";\n", $fileSessionHandler->read('existing'));
        $this->assertTrue($fileSessionHandler->write('created', 'user|s:3:"Another User";'));
        $this->assertSame('user|s:3:"Another User";', $fileSessionHandler->read('created'));
        $this->assertTrue($fileSessionHandler->updateTimestamp('created', 'ignored'));
        $this->assertTrue($fileSessionHandler->destroy('created'));
        $this->assertFalse($fileSessionHandler->validateId('created'));
        $this->assertTrue($fileSessionHandler->close());
    }

    public function testDefaultPathIsTheLastPartOfTheSavePath(): void
    {
        $savePath = ini_set('session.save_path', '2;' . TESTS_TMP_PATH . '/default');

        try {
            $fileSessionHandler = new FileSessionHandler();
        } finally {
            ini_set('session.save_path', (string) $savePath);
        }

        $this->assertTrue($fileSessionHandler->open('', 'formwork'));
        $this->assertTrue($fileSessionHandler->write('id', 'data'));
        $this->assertFileExists(TESTS_TMP_PATH . '/default/sess_id');
    }

    public function testDefaultPathIsTheTemporaryDirectoryWithoutSavePath(): void
    {
        $savePath = ini_set('session.save_path', '');

        try {
            $fileSessionHandler = new FileSessionHandler();
        } finally {
            ini_set('session.save_path', (string) $savePath);
        }

        $this->assertSame(FileSystem::normalizePath(sys_get_temp_dir()), (new ReflectionProperty($fileSessionHandler, 'path'))->getValue($fileSessionHandler));
    }

    public function testSessionsCannotBeOpenedIfTheirDirectoryCannotBeCreated(): void
    {
        FileSystem::write(TESTS_TMP_PATH . '/file', '');

        $this->assertFalse((new FileSessionHandler(TESTS_TMP_PATH . '/file/sessions'))->open('', 'formwork'));
    }

    public function testSessionsCannotBeReadOrWrittenWithoutTheirDirectory(): void
    {
        $fileSessionHandler = new FileSessionHandler(TESTS_TMP_PATH . '/missing');

        // An empty session is started, as PHP does when the session data cannot be read
        $this->assertSame('', $fileSessionHandler->read('id'));
        $this->assertFalse($fileSessionHandler->write('id', 'data'));
    }

    public function testSessionsReadAgainSeeTheirLatestData(): void
    {
        $fileSessionHandler = new FileSessionHandler(TESTS_TMP_PATH);
        FileSystem::write(TESTS_TMP_PATH . '/sess_id', 'first');

        $this->assertSame('first', $fileSessionHandler->read('id'));

        // The locked handle is reused, while the data is read again
        file_put_contents(TESTS_TMP_PATH . '/sess_id', 'second');
        $this->assertSame('second', $fileSessionHandler->read('id'));
        $this->assertTrue($fileSessionHandler->close());
    }

    public function testSessionFilesAreReadableOnlyByTheirOwner(): void
    {
        $fileSessionHandler = new FileSessionHandler(TESTS_TMP_PATH);

        $fileSessionHandler->write('id', 'data');

        $this->assertSame(0o600 & ~umask(), fileperms(TESTS_TMP_PATH . '/sess_id') & 0o777);
    }

    public function testSessionIdsCannotLeaveTheDirectory(): void
    {
        $fileSessionHandler = new FileSessionHandler(TESTS_TMP_PATH . '/sessions');
        $fileSessionHandler->open('', 'formwork');

        $fileSessionHandler->write('../escaped', 'data');

        $this->assertFileExists(TESTS_TMP_PATH . '/sessions/sess_escaped');
        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/sess_escaped');
    }

    public function testDestroyingMissingSessionsSucceeds(): void
    {
        $this->assertTrue((new FileSessionHandler(TESTS_TMP_PATH))->destroy('missing'));
    }

    public function testTimestampOfMissingSessionsIsUpdatedByWritingThem(): void
    {
        $fileSessionHandler = new FileSessionHandler(TESTS_TMP_PATH);

        $this->assertTrue($fileSessionHandler->updateTimestamp('id', 'data'));
        $this->assertSame('data', FileSystem::read(TESTS_TMP_PATH . '/sess_id'));
    }

    public function testTimestampOfSessionsIsUpdatedWithoutChangingTheirData(): void
    {
        $fileSessionHandler = new FileSessionHandler(TESTS_TMP_PATH);
        FileSystem::write(TESTS_TMP_PATH . '/sess_id', 'data');
        touch(TESTS_TMP_PATH . '/sess_id', time() - 3600);

        $this->assertTrue($fileSessionHandler->updateTimestamp('id', 'ignored'));

        clearstatcache();
        $this->assertSame('data', FileSystem::read(TESTS_TMP_PATH . '/sess_id'));
        $this->assertGreaterThan(time() - 60, filemtime(TESTS_TMP_PATH . '/sess_id'));
    }

    public function testGarbageCollectionDeletesOnlyExpiredSessions(): void
    {
        $fileSessionHandler = new FileSessionHandler(TESTS_TMP_PATH);
        FileSystem::write(TESTS_TMP_PATH . '/sess_expired', '');
        FileSystem::write(TESTS_TMP_PATH . '/sess_recent', '');
        FileSystem::write(TESTS_TMP_PATH . '/other', '');
        touch(TESTS_TMP_PATH . '/sess_expired', time() - 3600);
        touch(TESTS_TMP_PATH . '/other', time() - 3600);

        $this->assertSame(1, $fileSessionHandler->gc(1800));

        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/sess_expired');
        $this->assertFileExists(TESTS_TMP_PATH . '/sess_recent');
        $this->assertFileExists(TESTS_TMP_PATH . '/other');
    }

    public function testGarbageCollectionWithoutTheDirectoryDeletesNothing(): void
    {
        $this->assertSame(0, (new FileSessionHandler(TESTS_TMP_PATH . '/missing'))->gc(0));
    }

    public function testSessionsThatCannotBeDeletedAreReported(): void
    {
        $fileSessionHandler = new FileSessionHandler(TESTS_TMP_PATH . '/sessions');
        $fileSessionHandler->open('', 'formwork');
        $fileSessionHandler->write('id', 'data');
        touch(TESTS_TMP_PATH . '/sessions/sess_id', time() - 3600);
        chmod(TESTS_TMP_PATH . '/sessions', 0o500);

        try {
            if (is_writable(TESTS_TMP_PATH . '/sessions')) {
                $this->markTestSkipped('Directories without write permission are still writable by the current user.');
            }

            $this->assertFalse($fileSessionHandler->destroy('id'));
            $this->assertSame(0, $fileSessionHandler->gc(1800));
        } finally {
            chmod(TESTS_TMP_PATH . '/sessions', 0o700);
        }
    }
}
