<?php

namespace Formwork\Tests\Unit\Authentication;

use Formwork\Authentication\Exceptions\RateLimitExceededException;
use Formwork\Authentication\RateLimiter;
use Formwork\Http\Request;
use Formwork\Log\Registry;
use Formwork\Tests\TestCase;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(RateLimiter::class)]
final class RateLimiterTest extends TestCase
{
    private string $registryFile;

    /**
     * @var list<Registry>
     */
    private array $registries = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTempDirectory();
        $this->registryFile = FileSystem::joinPaths(TESTS_TMP_PATH, 'rate-limiter', 'attempts.json');
    }

    protected function tearDown(): void
    {
        // Registries save themselves when destroyed, so they must go before the directory does
        $this->registries = [];
        $this->tearDownTempDirectory();
        parent::tearDown();
    }

    public function testNewVisitorsHaveNotReachedTheLimit(): void
    {
        $limiter = $this->limiter(3);

        $this->assertFalse($limiter->hasReachedLimit());
        $limiter->assertAllowed();
    }

    public function testResetTimeIsExposed(): void
    {
        $this->assertSame(120, $this->limiter(3, 120)->getResetTime());
    }

    public function testAttemptsUpToTheLimitAreAllowed(): void
    {
        $limiter = $this->limiter(3);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $limiter->assertAllowed();
            $this->assertFalse($limiter->hasReachedLimit(), "Attempt $attempt");
        }
    }

    public function testTheAttemptAfterTheLimitIsRejectedWithTheResetTime(): void
    {
        $limiter = $this->limiter(3, 90);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $limiter->assertAllowed();
        }

        try {
            $limiter->assertAllowed();
            $this->fail('The attempt exceeding the limit should have been rejected.');
        } catch (RateLimitExceededException $exception) {
            $this->assertSame(90, $exception->getResetTime());
        }

        $this->assertTrue($limiter->hasReachedLimit());
    }

    public function testZeroLimitRejectsTheFirstAttempt(): void
    {
        $limiter = $this->limiter(0);

        $this->expectException(RateLimitExceededException::class);
        $limiter->assertAllowed();
    }

    public function testAttemptsAreNotRegisteredOnceTheLimitIsReached(): void
    {
        $limiter = $this->limiter(2);

        $limiter->registerAttempt();
        $limiter->registerAttempt();
        $limiter->registerAttempt();
        $this->assertTrue($limiter->hasReachedLimit());

        $before = $this->storedAttempts();

        for ($i = 0; $i < 5; $i++) {
            $limiter->registerAttempt();
        }

        $this->assertSame($before, $this->storedAttempts());
    }

    public function testAttemptsPersistAcrossRequests(): void
    {
        $this->limiter(2)->assertAllowed();
        $this->limiter(2)->assertAllowed();

        $this->assertFalse($this->limiter(2)->hasReachedLimit());

        $this->expectException(RateLimitExceededException::class);
        $this->limiter(2)->assertAllowed();
    }

    public function testDifferentAddressesHaveIndependentCounters(): void
    {
        $first = $this->limiter(1, ip: '203.0.113.1');
        $first->assertAllowed();

        try {
            $first->assertAllowed();
            $this->fail('The first address should be limited.');
        } catch (RateLimitExceededException) {
        }

        $this->limiter(1, ip: '203.0.113.2')->assertAllowed();
        $this->assertTrue($this->limiter(1, ip: '203.0.113.1')->hasReachedLimit());
    }

    #[DataProvider('hostProvider')]
    public function testHostHeaderDoesNotAffectTheCounter(string $host): void
    {
        $this->limiter(2, host: 'example.test')->assertAllowed();
        $this->limiter(2, host: $host)->assertAllowed();

        $this->expectException(RateLimitExceededException::class);
        $this->limiter(2, host: 'another.test')->assertAllowed();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostProvider(): iterable
    {
        yield 'different host' => ['attacker.test'];
        yield 'subdomain' => ['sub.example.test'];
        yield 'with port' => ['example.test:8080'];
        yield 'uppercase' => ['EXAMPLE.TEST'];
        yield 'IPv4 address' => ['192.0.2.1'];
        yield 'IPv6 address' => ['[2001:db8::1]'];
    }

    public function testRotatingHostHeadersCannotBypassTheLimit(): void
    {
        $limit = 3;
        $rejected = 0;

        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                $this->limiter($limit, host: "host-$attempt.test")->assertAllowed();
            } catch (RateLimitExceededException) {
                $rejected++;
            }
        }

        $this->assertSame(10 - $limit, $rejected);
    }

    public function testInvalidHostHeaderDoesNotBreakTheLimiter(): void
    {
        $limiter = $this->limiter(1, host: '../../nonexistent/secret');

        $limiter->assertAllowed();

        $this->expectException(RateLimitExceededException::class);
        $limiter->assertAllowed();
    }

    public function testUntrustedForwardedHeadersCannotChangeTheClientIdentity(): void
    {
        $this->limiter(1, ip: '203.0.113.1', server: ['HTTP_X_FORWARDED_FOR' => '198.51.100.1'])->assertAllowed();

        $this->expectException(RateLimitExceededException::class);
        $this->limiter(1, ip: '203.0.113.1', server: ['HTTP_X_FORWARDED_FOR' => '198.51.100.2'])->assertAllowed();
    }

    public function testTrustedProxiesProvideTheClientIdentity(): void
    {
        $proxy = '10.0.0.10';

        $this->limiter(1, ip: $proxy, server: ['HTTP_X_FORWARDED_FOR' => '198.51.100.1'], trustedProxies: [$proxy])->assertAllowed();
        $this->limiter(1, ip: $proxy, server: ['HTTP_X_FORWARDED_FOR' => '198.51.100.2'], trustedProxies: [$proxy])->assertAllowed();

        $this->expectException(RateLimitExceededException::class);
        $this->limiter(1, ip: $proxy, server: ['HTTP_X_FORWARDED_FOR' => '198.51.100.1'], trustedProxies: [$proxy])->assertAllowed();
    }

    public function testRegistryKeyDoesNotExposeTheAddress(): void
    {
        $this->limiter(1, ip: '203.0.113.1')->assertAllowed();

        $keys = array_keys($this->storedEntries());

        $this->assertCount(1, $keys);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $keys[0]);
    }

    public function testRegistryKeyDoesNotDependOnTheHost(): void
    {
        $this->limiter(5, host: 'one.test')->assertAllowed();
        $this->limiter(5, host: 'two.test')->assertAllowed();

        $this->assertCount(1, $this->storedEntries());
    }

    public function testAttemptsExpireAfterTheResetTime(): void
    {
        $limiter = $this->limiter(1, 60);
        $limiter->assertAllowed();

        $key = array_key_first($this->storedEntries());
        $this->writeRegistry([$key => [5, time() - 61]]);

        $limiter = $this->limiter(1, 60);

        $this->assertTrue($limiter->hasReachedLimit());
        $limiter->registerAttempt();
        $this->assertFalse($limiter->hasReachedLimit());
        $this->assertSame(1, $this->storedAttempts());
    }

    public function testAttemptsDoNotExpireBeforeTheResetTime(): void
    {
        $limiter = $this->limiter(1, 60);
        $limiter->assertAllowed();

        $key = array_key_first($this->storedEntries());
        $this->writeRegistry([$key => [5, time() - 30]]);

        $limiter = $this->limiter(1, 60);
        $limiter->registerAttempt();

        $this->assertTrue($limiter->hasReachedLimit());
        $this->assertSame(5, $this->storedAttempts());
    }

    public function testResetAttemptsClearsTheCounter(): void
    {
        $limiter = $this->limiter(1);
        $limiter->assertAllowed();

        try {
            $limiter->assertAllowed();
        } catch (RateLimitExceededException) {
        }

        $this->assertTrue($limiter->hasReachedLimit());

        $limiter->resetAttempts();

        $this->assertFalse($limiter->hasReachedLimit());
        $this->assertSame([], $this->storedEntries());
        $limiter->assertAllowed();
    }

    public function testResetAttemptsDoesNotAffectOtherAddresses(): void
    {
        $other = $this->limiter(1, ip: '203.0.113.2');
        $other->assertAllowed();

        $limiter = $this->limiter(1, ip: '203.0.113.1');
        $limiter->assertAllowed();
        $limiter->resetAttempts();

        $this->assertCount(1, $this->storedEntries());

        $this->expectException(RateLimitExceededException::class);
        $this->limiter(1, ip: '203.0.113.2')->assertAllowed();
    }

    /**
     * @param array<string, string> $server
     * @param list<string>          $trustedProxies
     */
    private function limiter(
        int $limit,
        int $resetTime = 300,
        string $ip = '203.0.113.1',
        string $host = 'example.test',
        array $server = [],
        array $trustedProxies = [],
    ): RateLimiter {
        $request = new Request([], [], [], [], $server + [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR'    => $ip,
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'HTTP_HOST'      => $host,
        ]);
        $request->setTrustedProxies($trustedProxies);

        // Registries are persisted when the request ends, so previous limiters are flushed first
        $this->endRequests();
        $this->registries[] = $registry = new Registry($this->registryFile);

        return new RateLimiter($registry, $limit, $resetTime, $request);
    }

    /**
     * Persist the registries of the limiters created so far
     */
    private function endRequests(): void
    {
        foreach ($this->registries as $registry) {
            $registry->save();
        }
        $this->registries = [];
    }

    /**
     * @return array<string, array{int, int}>
     */
    private function storedEntries(): array
    {
        $this->endRequests();

        return (new Registry($this->registryFile))->toArray();
    }

    private function storedAttempts(): int
    {
        $entries = $this->storedEntries();

        return (int) $entries[array_key_first($entries)][0];
    }

    /**
     * @param array<string, array{int, int}> $entries
     */
    private function writeRegistry(array $entries): void
    {
        $registry = new Registry($this->registryFile);
        foreach ($registry->toArray() as $key => $_) {
            $registry->remove($key);
        }
        foreach ($entries as $key => $entry) {
            $registry->set($key, $entry);
        }
        $registry->save();
    }
}
