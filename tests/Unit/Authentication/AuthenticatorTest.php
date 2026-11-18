<?php

namespace Formwork\Tests\Unit\Authentication;

use Formwork\Authentication\Authenticator;
use Formwork\Authentication\Exceptions\AuthenticationFailedException;
use Formwork\Authentication\Exceptions\RateLimitExceededException;
use Formwork\Authentication\Exceptions\UserNotLoggedException;
use Formwork\Authentication\RateLimiter;
use Formwork\Http\Request;
use Formwork\Log\Registry;
use Formwork\Parsers\Yaml;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Authentication\Fixtures\InMemorySessionFixture;
use Formwork\Tests\Unit\Authentication\Fixtures\NativeFunctionsFixture;
use Formwork\Tests\Unit\Users\BuildsPasswordHashes;
use Formwork\Tests\Unit\Users\BuildsUsers;
use Formwork\Tests\Unit\Users\Fixtures\TestableUserFixture;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Users\Users;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[CoversClass(Authenticator::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class AuthenticatorTest extends TestCase
{
    use BuildsUsers;
    use BuildsPasswordHashes;

    private InMemorySessionFixture $session;

    private Users $users;

    /**
     * @var array<string, TestableUserFixture>
     */
    private array $testUsers = [];

    /**
     * @var list<Registry>
     */
    private array $registries = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        NativeFunctionsFixture::disable('usleep');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUsers();
        $this->session = new InMemorySessionFixture();
        $this->users = $this->users();
        $this->testUsers = array_map(fn(array $data): TestableUserFixture => $this->makeUser($data, $this->users), [
            'user'         => ['username' => 'user', 'email' => 'user@example.test', 'role' => 'admin', 'hash' => self::hashPassword('correct-password')],
            'another-user' => ['username' => 'another-user', 'email' => 'another-user@example.test', 'role' => 'editor', 'hash' => self::hashPassword('another-users-password')],
        ]);
    }

    protected function tearDown(): void
    {
        $this->registries = [];
        parent::tearDown();
    }

    public function testUsersCanLogInWithTheirUsername(): void
    {
        $user = $this->authenticator()->login('user', 'correct-password');

        $this->assertSame($this->testUsers['user'], $user);
    }

    public function testUsersCanLogInWithTheirEmail(): void
    {
        $user = $this->authenticator()->login('user@example.test', 'correct-password');

        $this->assertSame($this->testUsers['user'], $user);
    }

    public function testLoginStoresTheUsernameInTheSession(): void
    {
        $this->authenticator()->login('user@example.test', 'correct-password');

        $this->assertSame('user', $this->session->get(Authenticator::SESSION_LOGGED_USER_KEY));
    }

    public function testLoginRegeneratesTheSessionToPreventFixation(): void
    {
        $this->authenticator()->login('user', 'correct-password');

        $this->assertSame(1, $this->session->regenerations);
    }

    public function testLoginRecordsTheLastAccessTime(): void
    {
        $before = time();

        $this->authenticator()->login('user', 'correct-password');

        $this->assertGreaterThanOrEqual($before, $this->testUsers['user']->data()['lastAccess']);
        $this->assertGreaterThanOrEqual($before, Yaml::parseFile($this->accountsPath . '/user.yaml')['lastAccess'] ?? 0);
    }

    public function testLoginResetsTheFailedAttempts(): void
    {
        $authenticator = $this->authenticator();

        $this->captureException(AuthenticationFailedException::class, static fn() => $authenticator->login('user', 'wrong'));

        $this->assertSame(1, $this->attempts());

        $authenticator->login('user', 'correct-password');

        $this->assertSame(0, $this->attempts());
    }

    public function testWrongPasswordsAreRejected(): void
    {
        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('Authentication failed for "user"');
        $this->authenticator()->login('user', 'wrong-password');
    }

    public function testFailedLoginsDoNotStartASession(): void
    {
        $this->captureException(AuthenticationFailedException::class, fn() => $this->authenticator()->login('user', 'wrong-password'));

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
        $this->authenticator()->login('user', 'another-users-password');
    }

    #[RunInSeparateProcess]
    public function testFailedLoginsAreSlowedDown(): void
    {
        NativeFunctionsFixture::enable('usleep');

        $start = microtime(true);

        $this->captureException(AuthenticationFailedException::class, fn() => $this->authenticator()->login('user', 'wrong-password'));

        $this->assertGreaterThanOrEqual(0.45, microtime(true) - $start);
    }

    public function testUsersWithoutAPasswordCannotLogIn(): void
    {
        $this->makeUser(['username' => 'editor', 'email' => 'editor@example.test', 'role' => 'editor'], $this->users);

        $this->expectException(AuthenticationFailedException::class);
        $this->authenticator()->login('editor', 'anything');
    }

    public function testLoginAttemptsBeyondTheLimitAreRejectedEvenWithTheRightPassword(): void
    {
        $authenticator = $this->authenticator(limit: 1);

        $this->captureException(AuthenticationFailedException::class, static fn() => $authenticator->login('user', 'wrong'));

        $this->expectException(RateLimitExceededException::class);
        $authenticator->login('user', 'correct-password');
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
        $authenticator->login('user', 'correct-password');

        $this->assertTrue($authenticator->isLoggedIn());
        $this->assertSame($this->testUsers['user'], $authenticator->getUser());
    }

    public function testLoggedInStateComesFromTheSession(): void
    {
        $this->session->set(Authenticator::SESSION_LOGGED_USER_KEY, 'another-user');

        $authenticator = $this->authenticator();

        $this->assertTrue($authenticator->isLoggedIn());
        $this->assertSame('another-user', $authenticator->getUser()?->username());
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
        $authenticator->login('user', 'correct-password');

        $authenticator->logout();

        $this->assertFalse($authenticator->isLoggedIn());
        $this->assertNull($authenticator->getUser());
        $this->assertFalse($this->session->has(Authenticator::SESSION_LOGGED_USER_KEY));
    }

    public function testLogoutRegeneratesTheSession(): void
    {
        $authenticator = $this->authenticator();
        $authenticator->login('user', 'correct-password');

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
        $authenticator->login('user', 'correct-password');
        $authenticator->logout();

        $this->expectException(UserNotLoggedException::class);
        $authenticator->logout();
    }

    private function authenticator(int $limit = 10): Authenticator
    {
        $request = new Request(server: [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR'    => '203.0.113.7',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
        ]);
        $registry = new Registry(FileSystem::joinPaths(TESTS_TMP_PATH, 'attempts.json'));
        $this->registries[] = $registry;

        return new Authenticator($this->users, $this->session, new RateLimiter($registry, $limit, 300, TESTS_TMP_PATH, $request));
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
