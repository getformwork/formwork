<?php

namespace Formwork\Tests\Unit\Http;

use Closure;
use Formwork\Http\Request;
use Formwork\Http\Session\MessageType;
use Formwork\Http\Session\Session;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Session::class)]
#[UsesTempDirectory]
final class SessionTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalCookies = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCookies = $_COOKIE;
    }

    protected function tearDown(): void
    {
        $this->endRequest();
        $_COOKIE = $this->originalCookies;
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

    public function testSessionCannotBeStartedTwice(): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));
        $session->start();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Session already started');
            $session->start();
        } finally {
            $session->save();
        }
    }

    /**
     * @param Closure(Session): mixed $operation
     */
    #[DataProvider('reservedKeyOperationProvider')]
    public function testReservedMessageKeysAreRejected(Closure $operation): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('is reserved');
            $operation($session);
        } finally {
            $session->save();
        }
    }

    /**
     * @return iterable<string, array{Closure(Session): mixed}>
     */
    public static function reservedKeyOperationProvider(): iterable
    {
        yield 'get' => [static fn(Session $session) => $session->get('_formwork_messages.foo')];
        yield 'has' => [static fn(Session $session) => $session->has('_formwork_messages')];
        yield 'set' => [static fn(Session $session) => $session->set('_formwork_messages', ['forged'])];
        yield 'set nested key' => [static fn(Session $session) => $session->set('_formwork_messages.info', ['forged'])];
        yield 'remove' => [static fn(Session $session) => $session->remove('_formwork_messages')];
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

    /**
     * @param Closure(Session): mixed $change
     */
    #[DataProvider('configurationChangeProvider')]
    public function testSessionRejectsChangingNameAndPathAfterStart(Closure $change, string $message): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));
        $session->start();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);
            $change($session);
        } finally {
            $session->save();
        }
    }

    /**
     * @return iterable<string, array{Closure(Session): mixed, string}>
     */
    public static function configurationChangeProvider(): iterable
    {
        yield 'name' => [static fn(Session $session) => $session->setName('another'), 'Cannot set session name'];
        yield 'save path' => [static fn(Session $session) => $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'another-path')), 'Cannot set session save path'];
    }

    public function testRegenerateDestroysTheOldSessionAndMovesItsDataToTheNewOne(): void
    {
        $directory = FileSystem::joinPaths(TESTS_TMP_PATH, 'session-regenerate');
        FileSystem::createDirectory($directory);

        $session = $this->session();
        $session->setPath($directory);
        $session->start();
        $oldId = session_id();
        $session->set('value', 42);
        $session->save();

        $this->assertFileExists(FileSystem::joinPaths($directory, 'sess_' . $oldId));

        $session->start();
        $session->regenerate();
        $newId = session_id();
        $session->save();

        $this->assertNotSame($oldId, $newId);
        $this->assertFileDoesNotExist(FileSystem::joinPaths($directory, 'sess_' . $oldId));
        $this->assertFileExists(FileSystem::joinPaths($directory, 'sess_' . $newId));
        $this->assertStringContainsString('value|i:42;', (string) file_get_contents(FileSystem::joinPaths($directory, 'sess_' . $newId)));
    }

    public function testRegenerateCanDiscardTheSessionData(): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));
        $session->start();
        $session->set('value', 42);
        $session->regenerate(preserveData: false);

        $this->assertFalse($session->has('value'));
        $session->save();
    }

    public function testDestroyEndsTheSessionAndRemovesItsData(): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));
        $session->start();
        $session->set('value', 42);

        $session->destroy();

        $this->assertFalse($session->isStarted());
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testDestroyRemovesTheSessionFile(): void
    {
        $id = $this->sessionId($this->sessionRequest('write'));
        $this->assertFileExists(TESTS_TMP_PATH . '/sessions/sess_' . $id);

        $this->sessionRequest('destroy', $id);

        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/sessions/sess_' . $id);
    }

    public function testExistsComparesTheGivenIdWithTheCurrentOne(): void
    {
        $session = $this->session();
        $session->setPath(FileSystem::joinPaths(TESTS_TMP_PATH, 'session-data'));
        $session->start();
        $id = (string) session_id();

        $this->assertTrue($session->exists($id));
        $this->assertTrue($session->isStarted());

        $this->assertFalse($session->exists('another-session-id-0123456789'));
        $this->assertFalse($session->isStarted());
    }

    public function testNewVisitorsGetANewSessionStoredOnDisk(): void
    {
        $id = $this->sessionId($this->sessionRequest('write'));

        $this->assertMatchesRegularExpression('/^[a-z0-9,-]{22,256}$/i', $id);
        $this->assertFileExists(TESTS_TMP_PATH . '/sessions/sess_' . $id);
    }

    public function testDataAndFlashMessagesPersistAcrossRequests(): void
    {
        $id = $this->sessionId($this->sessionRequest('write'));

        $second = $this->sessionRequest('read', $id);
        $third = $this->sessionRequest('read', $id);

        $this->assertSame($id, $this->sessionId($second));
        $this->assertSame('Alice', $second['user']);
        $this->assertSame(['info' => ['Saved']], $second['messages']);
        $this->assertSame($id, $this->sessionId($third));
        $this->assertSame('Alice', $third['user']);
        $this->assertSame([], $third['messages']);
    }

    #[DataProvider('invalidSessionIdProvider')]
    public function testMalformedSessionIdsInTheCookieAreIgnored(string $suppliedId): void
    {
        $response = $this->sessionRequest('read', $suppliedId);
        $id = $this->sessionId($response);

        $this->assertNotSame($suppliedId, $id);
        $this->assertMatchesRegularExpression('/^[a-z0-9,-]{22,256}$/i', $id);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSessionIdProvider(): iterable
    {
        yield 'too short' => ['abc'];
        yield 'path traversal' => ['../../nonexistent/secret-0000000000000'];
        yield 'invalid characters' => ['abcdefghijklmnopqrstuvwxyz!@#$%'];
        yield 'too long' => [str_repeat('a', 257)];
    }

    public function testUnknownWellFormedSessionIdsAreReplacedByNewOnes(): void
    {
        $suppliedId = str_repeat('a', 32);

        $response = $this->sessionRequest('read', $suppliedId);

        $this->assertNotSame($suppliedId, $this->sessionId($response));
        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/sessions/sess_' . $suppliedId);
    }

    /**
     * @param array{id: string, user: mixed, messages: array<string, list<string>>} $response
     */
    private function sessionId(array $response): string
    {
        return $response['id'];
    }

    /**
     * Run a request against the session, as a separate script would do
     *
     * Every request starts with no session state and ends by closing the session
     *
     * @return array{id: string, user: mixed, messages: array<string, list<string>>}
     */
    private function sessionRequest(string $action, ?string $cookieId = null): array
    {
        if (!FileSystem::isDirectory(TESTS_TMP_PATH . '/sessions', assertExists: false)) {
            FileSystem::createDirectory(TESTS_TMP_PATH . '/sessions');
        }

        $this->endRequest();

        $cookies = $cookieId !== null ? ['formwork_session' => $cookieId] : [];
        $_COOKIE = $cookies;

        $session = new Session(new Request([], [], $cookies, [], [
            'REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80',
        ]));
        $session->setPath(TESTS_TMP_PATH . '/sessions');

        switch ($action) {
            case 'write':
                $session->set('user', 'Alice');
                $session->messages()->add(MessageType::Info, 'Saved');
                break;

            case 'read':
                $session->start();
                break;

            case 'destroy':
                $session->start();
                $session->destroy();
                break;
        }

        $response = ['id' => (string) session_id(), 'user' => null, 'messages' => []];

        if ($action === 'read') {
            $response['user'] = $session->get('user');
            $response['messages'] = $session->messages()->getAll();
        }

        $session->save();

        return $response;
    }

    /**
     * Close the session and forget its state, so that the next request starts from scratch
     */
    private function endRequest(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('');
        $_SESSION = [];
    }

    private function session(): Session
    {
        return new Session(new Request([], [], [], [], [
            'REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80',
        ]));
    }
}
