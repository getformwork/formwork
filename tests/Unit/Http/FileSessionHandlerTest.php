<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Session\Handler\FileSessionHandler;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

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
        $this->assertSame("user|s:8:\"Giuseppe\";\n", $fileSessionHandler->read('existing'));
        $this->assertTrue($fileSessionHandler->write('created', 'user|s:3:"Bob";'));
        $this->assertSame('user|s:3:"Bob";', $fileSessionHandler->read('created'));
        $this->assertTrue($fileSessionHandler->updateTimestamp('created', 'ignored'));
        $this->assertTrue($fileSessionHandler->destroy('created'));
        $this->assertFalse($fileSessionHandler->validateId('created'));
        $this->assertTrue($fileSessionHandler->close());
    }
}
