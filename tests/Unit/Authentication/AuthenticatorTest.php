<?php

namespace Formwork\Tests\Unit\Authentication;

use Formwork\Authentication\Authenticator;
use Formwork\Authentication\Exceptions\AuthenticationFailedException;
use Formwork\Authentication\Exceptions\RateLimitExceededException;
use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Authentication\RateLimiter;
use Formwork\Http\Request;
use Formwork\Log\Registry;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Authentication\Fixtures\InMemorySession;
use Formwork\Tests\Unit\Users\Fixtures\BuildsUsers;
use Formwork\Tests\Unit\Users\Fixtures\TestableUser;
use Formwork\Users\Users;
use Formwork\Users\Utils\Password;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Authenticator::class)]
final class AuthenticatorTest extends TestCase
{
    use BuildsUsers;

    private InMemorySession $session;

    private Users $users;

    private TestableUser $alice;

    /**
     * @var list<Registry>
     */
    private array $registries = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUsers();
        $this->session = new InMemorySession();
        $this->users = $this->users();
        $this->alice = $this->makeUser([
            'username' => 'alice',
            'email'    => 'alice@example.test',
            'role'     => 'admin',
            'hash'     => Password::hash('correct-password'),
        ], $this->users);
        $this->makeUser([
            'username' => 'bob',
            'email'    => 'bob@example.test',
            'role'     => 'editor',
            'hash'     => Password::hash('bobs-password'),
        ], $this->users);
    }

    protected function tearDown(): void
    {
        $this->registries = [];
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testUsersCanLogInWithTheirUsername(): void
    {
        $user = $this->authenticator()->login('alice', 'correct-password');

        $this->assertSame($this->alice, $user);
    }

    public function testUsersCanLogInWithTheirEmail(): void
    {
        $user = $this->authenticator()->login('alice@example.test', 'correct-password');

        $this->assertSame($this->alice, $user);
    }

    public function testLoginStoresTheUsernameInTheSession(): void
    {
        $this->authenticator()->login('alice@example.test', 'correct-password');

        $this->assertSame('alice', $this->session->get(Authenticator::SESSION_LOGGED_USER_KEY));
    }

    public function testLoginRegeneratesTheSessionToPreventFixation(): void
    {
        $this->authenticator()->login('alice', 'correct-password');

        $this->assertSame(1, $this->session->regenerations);
    }

    public function testLoginRecordsTheLastAccessTime(): void
    {
        $before = time();

        $this->authenticator()->login('alice', 'correct-password');

        $this->assertGreaterThanOrEqual($before, $this->alice->data()['lastAccess']);
        $this->assertFileExists($this->accountsPath . '/alice.yaml');
    }

    public function testLoginResetsTheFailedAttempts(): void
    {
        $authenticator = $this->authenticator();

        try {
            $authenticator->login('alice', 'wrong');
        } catch (AuthenticationFailedException) {
        }

        $this->assertSame(1, $this->attempts());

        $authenticator->login('alice', 'correct-password');

        $this->assertSame(0, $this->attempts());
    }

    public function testWrongPasswordsAreRejected(): void
    {
        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('Authentication failed for "alice"');
        $this->authenticator()->login('alice', 'wrong-password');
    }

    public function testFailedLoginsDoNotStartASession(): void
    {
        try {
            $this->authenticator()->login('alice', 'wrong-password');
        } catch (AuthenticationFailedException) {
        }

        $this->assertFalse($this->session->has(Authenticator::SESSION_LOGGED_USER_KEY));
        $this->assertSame(0, $this->session->regenerations);
    }

    public function testUnknownUsersAreRejectedLikeWrongPasswords(): void
    {
        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('Authentication failed for "nobody"');
        $this->authenticator()->login('nobody', 'whatever');
    }

    public function testPasswordsOfOtherUsersAreRejected(): void
    {
        $this->expectException(AuthenticationFailedException::class);
        $this->authenticator()->login('alice', 'bobs-password');
    }

    public function testFailedLoginsAreSlowedDown(): void
    {
        $start = microtime(true);

        try {
            $this->authenticator()->login('alice', 'wrong-password');
        } catch (AuthenticationFailedException) {
        }

        $this->assertGreaterThanOrEqual(0.45, microtime(true) - $start);
    }

    public function testUsersWithoutAPasswordCannotLogIn(): void
    {
        $this->makeUser(['username' => 'carol', 'email' => 'carol@example.test', 'role' => 'editor'], $this->users);

        $this->expectException(AuthenticationFailedException::class);
        $this->authenticator()->login('carol', 'anything');
    }

    public function testLoginAttemptsBeyondTheLimitAreRejectedEvenWithTheRightPassword(): void
    {
        $authenticator = $this->authenticator(limit: 1);

        try {
            $authenticator->login('alice', 'wrong');
        } catch (AuthenticationFailedException) {
        }

        $this->expectException(RateLimitExceededException::class);
        $authenticator->login('alice', 'correct-password');
    }

    public function testNobodyIsLoggedInInitially(): void
    {
        $authenticator = $this->authenticator();

        $this->assertFalse($authenticator->isLoggedIn());
        $this->assertNull($authenticator->getUser());
    }

    public function testLoggedInUsersAreRecognized(): void
    {
        $authenticator = $this->authenticator();
        $authenticator->login('alice', 'correct-password');

        $this->assertTrue($authenticator->isLoggedIn());
        $this->assertSame($this->alice, $authenticator->getUser());
    }

    public function testLoggedInStateComesFromTheSession(): void
    {
        $this->session->set(Authenticator::SESSION_LOGGED_USER_KEY, 'bob');

        $authenticator = $this->authenticator();

        $this->assertTrue($authenticator->isLoggedIn());
        $this->assertSame('bob', $authenticator->getUser()?->username());
    }

    public function testSessionsOfDeletedUsersAreNotLoggedIn(): void
    {
        $this->session->set(Authenticator::SESSION_LOGGED_USER_KEY, 'ghost');

        $authenticator = $this->authenticator();

        $this->assertNull($authenticator->getUser());
        $this->assertFalse($authenticator->isLoggedIn(), 'A session pointing to a missing user must not count as logged in');
    }

    public function testLogoutRemovesTheUserFromTheSession(): void
    {
        $authenticator = $this->authenticator();
        $authenticator->login('alice', 'correct-password');

        $authenticator->logout();

        $this->assertFalse($authenticator->isLoggedIn());
        $this->assertNull($authenticator->getUser());
        $this->assertFalse($this->session->has(Authenticator::SESSION_LOGGED_USER_KEY));
    }

    public function testLogoutRegeneratesTheSession(): void
    {
        $authenticator = $this->authenticator();
        $authenticator->login('alice', 'correct-password');

        $authenticator->logout();

        $this->assertSame(2, $this->session->regenerations);
    }

    public function testLoggingOutWithoutBeingLoggedInIsReported(): void
    {
        $this->expectException(UserNotLoggedException::class);
        $this->authenticator()->logout();
    }

    public function testLoggingOutTwiceIsReported(): void
    {
        $authenticator = $this->authenticator();
        $authenticator->login('alice', 'correct-password');
        $authenticator->logout();

        $this->expectException(UserNotLoggedException::class);
        $authenticator->logout();
    }

    private function authenticator(int $limit = 10): Authenticator
    {
        $request = new Request([], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR'    => '203.0.113.7',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
        ]);
        $registry = new Registry(FileSystem::joinPaths(TESTS_TMP_PATH, 'attempts.json'));
        $this->registries[] = $registry;

        return new Authenticator($this->users, $this->session, new RateLimiter($registry, $limit, 300, $request));
    }

    private function attempts(): int
    {
        foreach ($this->registries as $registry) {
            $entries = $registry->toArray();
            if ($entries !== []) {
                return (int) $entries[array_key_first($entries)][0];
            }
        }

        return 0;
    }
}
