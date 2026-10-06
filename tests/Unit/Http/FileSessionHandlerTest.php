<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Session\Handler\FileSessionHandler;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FileSessionHandler::class)]
final class FileSessionHandlerTest extends TestCase
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

    public function testHandlerReadsWritesValidatesAndDestroysSessions(): void
    {
        $path = FileSystem::joinPaths(TESTS_TMP_PATH, 'sessions');
        FileSystem::copyDirectory(__DIR__ . '/fixtures/sessions', $path);
        $handler = new FileSessionHandler($path);

        $this->assertTrue($handler->open('', 'formwork'));
        $this->assertTrue($handler->validateId('existing'));
        $this->assertSame("user|s:8:\"Giuseppe\";\n", $handler->read('existing'));
        $this->assertTrue($handler->write('created', 'user|s:3:"Bob";'));
        $this->assertSame('user|s:3:"Bob";', $handler->read('created'));
        $this->assertTrue($handler->updateTimestamp('created', 'ignored'));
        $this->assertTrue($handler->destroy('created'));
        $this->assertFalse($handler->validateId('created'));
        $this->assertTrue($handler->close());
    }
}
