<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\Request;
use Formwork\Http\Session\Session;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Session::class)]
final class SessionTest extends TestCase
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

    public function testSessionDataAndMessagesAreMemoized(): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));
        $session->set('value', 42);

        $this->assertTrue($session->has('value'));
        $this->assertSame(42, $session->get('value'));
        $this->assertSame($session->messages(), $session->messages());
        $session->remove('value');
        $this->assertFalse($session->has('value'));
        $session->save();
    }

    public function testReservedMessageKeysAreRejected(): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));

        try {
            $session->get('_formwork_messages.foo');
            $this->fail('Expected reserved key exception');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        } finally {
            $session->save();
        }
    }

    public function testSessionCanBeConfiguredStartedAndSaved(): void
    {
        $session = $this->session();
        $session->setName('custom_session');
        $session->setDuration(60);
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));

        $this->assertFalse($session->isStarted());
        $this->assertSame('custom_session', $session->name());
        $session->start();
        $this->assertTrue($session->isStarted());
        $session->save();
        $this->assertFalse($session->isStarted());
    }

    public function testSessionCanRegenerateAndCheckItsCurrentId(): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));
        $session->start();
        $session->set('value', 42);
        $session->regenerate();
        $id = session_id();

        $this->assertTrue($session->exists($id));
        $this->assertSame(42, $session->get('value'));
        $session->save();
    }

    public function testSessionRejectsChangingConfigurationAfterStart(): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));
        $session->start();

        try {
            $session->setName('another');
            $this->fail('Expected setName exception');
        } catch (\RuntimeException) {
            $this->addToAssertionCount(1);
        }
        try {
            $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'another-path'));
            $this->fail('Expected setPath exception');
        } catch (\RuntimeException) {
            $this->addToAssertionCount(1);
        } finally {
            $session->save();
        }
    }

    private function session(): Session
    {
        return new Session(new Request([], [], [], [], [
            'REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80',
        ]));
    }
}
