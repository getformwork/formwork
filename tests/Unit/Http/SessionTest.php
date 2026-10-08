<?php

namespace Formwork\Tests\Unit\Http;

use Closure;
use Formwork\Http\Request;
use Formwork\Http\Session\Session;
use Formwork\Tests\PhpServer;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

#[CoversClass(Session::class)]
final class SessionTest extends TestCase
{
    private static PhpServer $server;

    public static function setUpBeforeClass(): void
    {
        self::$server = PhpServer::start(__DIR__ . '/Fixtures/endpoint.php');
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

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

    public function testSessionCookiesAreSessionCookiesWithoutADuration(): void
    {
        $cookie = (string) $this->sessionCookie($this->sessionRequest('write')['headers']);

        $this->assertStringNotContainsString('expires=', $cookie);
        $this->assertStringNotContainsString('Max-Age', $cookie);
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

    public function testDestroyRemovesTheSessionFileAndExpiresTheCookie(): void
    {
        $id = $this->sessionId($this->sessionRequest('write'));
        $this->assertFileExists(TESTS_TMP_PATH . '/sessions/sess_' . $id);

        $response = $this->sessionRequest('destroy', $id);

        $this->assertFileDoesNotExist(TESTS_TMP_PATH . '/sessions/sess_' . $id);
        $this->assertStringContainsString('expires=Thu, 01 Jan 1970', (string) $this->sessionCookie($response['headers']));
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

    public function testNewVisitorsReceiveAnHttpOnlyStrictSessionCookie(): void
    {
        $response = $this->sessionRequest('write');
        $id = $this->sessionId($response);
        $cookie = $this->sessionCookie($response['headers']);

        $this->assertMatchesRegularExpression('/^[a-z0-9,-]{22,256}$/i', $id);
        $this->assertNotNull($cookie);
        $this->assertStringStartsWith('formwork_session=' . $id, $cookie);
        $this->assertStringContainsString('HttpOnly', $cookie);
        $this->assertStringContainsString('SameSite=Strict', $cookie);
        $this->assertContains('Cache-Control: no-store, no-cache, must-revalidate', $response['headers']);
        $this->assertFileExists(TESTS_TMP_PATH . '/sessions/sess_' . $id);
    }

    public function testDataAndFlashMessagesPersistAcrossRequests(): void
    {
        $id = $this->sessionId($this->sessionRequest('write'));

        $second = $this->sessionRequest('read', $id);
        $third = $this->sessionRequest('read', $id);

        $this->assertSame($id, $this->sessionId($second));
        $this->assertSame('Alice', json_decode($second['body'], true)['user']);
        $this->assertSame(['info' => ['Saved']], json_decode($second['body'], true)['messages']);
        $this->assertSame($id, $this->sessionId($third));
        $this->assertSame('Alice', json_decode($third['body'], true)['user']);
        $this->assertSame([], json_decode($third['body'], true)['messages']);
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
        yield 'path traversal' => ['../../etc/passwd-0000000000000'];
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

    public function testDurationSetsTheExpirationOfTheSessionCookie(): void
    {
        $response = $this->sessionRequest('write', duration: 3600);
        $cookie = (string) $this->sessionCookie($response['headers']);

        $this->assertStringContainsString('expires=', $cookie);
        $this->assertMatchesRegularExpression('/Max-Age=(3[56][0-9]{2})/', $cookie);
    }

    /**
     * @param array{status: int, headers: list<string>, body: string} $response
     */
    private function sessionId(array $response): string
    {
        return json_decode($response['body'], true)['id'];
    }

    /**
     * @param list<string> $headers
     */
    private function sessionCookie(array $headers): ?string
    {
        foreach ($headers as $header) {
            if (str_starts_with($header, 'Set-Cookie: formwork_session=')) {
                return substr($header, strlen('Set-Cookie: '));
            }
        }

        return null;
    }

    /**
     * @return array{status: int, headers: list<string>, body: string}
     */
    private function sessionRequest(string $action, ?string $cookieId = null, ?int $duration = null): array
    {
        if (!FileSystem::isDirectory(TESTS_TMP_PATH . '/sessions', assertExists: false)) {
            FileSystem::createDirectory(TESTS_TMP_PATH . '/sessions');
        }

        $query = http_build_query(array_filter([
            'action'   => 'session',
            'do'       => $action,
            'path'     => TESTS_TMP_PATH . '/sessions',
            'duration' => $duration,
        ], static fn(mixed $value): bool => $value !== null));

        return self::$server->request($query, $cookieId !== null ? ['Cookie' => 'formwork_session=' . $cookieId] : []);
    }

    private function session(): Session
    {
        return new Session(new Request([], [], [], [], [
            'REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80',
        ]));
    }
}
