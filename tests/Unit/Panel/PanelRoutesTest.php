<?php

namespace Formwork\Tests\Unit\Panel;

use Formwork\Http\Response;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Requests to the whole application: routes, filters, permissions, controllers and views together
 */
#[CoversNothing]
#[UsesApplication]
#[UsesTempDirectory]
final class PanelRoutesTest extends TestCase
{
    use BuildsPanelClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAccount('alice');
        $this->createAccount('bob', 'editor');
    }

    /**
     * @param array<string, mixed> $expected `status`, and the `to` location or the `title` of the page
     */
    #[DataProvider('routeProvider')]
    public function testRoutesRespondAsExpected(?string $user, string $method, string $uri, array $expected): void
    {
        if ($user !== null) {
            $this->logIn($user);
        }

        $this->assertSame($expected, array_replace($expected, array_intersect_key($this->describe($this->send($method, $uri)), $expected)));
    }

    /**
     * @return iterable<string, array{?string, string, string, array<string, mixed>}>
     */
    public static function routeProvider(): iterable
    {
        yield 'anonymous visitors are sent to the login' => [null, 'GET', '/panel/pages/', ['status' => 302, 'to' => '/panel/login/']];
        yield 'the login page is public' => [null, 'GET', '/panel/login/', ['status' => 200, 'title' => 'Login | Formwork']];
        yield 'the panel index redirects to the dashboard' => ['alice', 'GET', '/panel/', ['status' => 302, 'to' => '/panel/dashboard/']];
        yield 'the dashboard' => ['alice', 'GET', '/panel/dashboard/', ['status' => 200, 'title' => 'Dashboard | Formwork']];
        yield 'the pages redirect to the tree' => ['alice', 'GET', '/panel/pages/', ['status' => 302, 'to' => '/panel/pages/tree/']];
        yield 'the users' => ['alice', 'GET', '/panel/users/', ['status' => 200, 'title' => 'Users | Formwork']];
        yield 'logged in users are sent away from the login' => ['alice', 'GET', '/panel/login/', ['status' => 302, 'to' => '/panel/']];
        yield 'unknown pages' => ['alice', 'GET', '/panel/nope/', ['status' => 404]];
        yield 'editors can open the dashboard' => ['bob', 'GET', '/panel/dashboard/', ['status' => 200]];
        yield 'editors cannot open the site options' => ['bob', 'GET', '/panel/options/site/', ['status' => 403]];
        yield 'editors cannot open the system options' => ['bob', 'GET', '/panel/options/system/', ['status' => 403]];
        yield 'editors cannot open the backups' => ['bob', 'GET', '/panel/tools/backups/', ['status' => 403]];
        yield 'editors cannot open the updates' => ['bob', 'GET', '/panel/tools/updates/', ['status' => 403]];
    }

    public function testWrongPasswordsDoNotLogIn(): void
    {
        $this->assertSame(200, $this->logIn('alice', 'wrong')->status()->code());
        $this->assertSame('/panel/login/', $this->describe($this->send('GET', '/panel/dashboard/'))['to'] ?? null);
    }

    public function testLoggingOutEndsTheSession(): void
    {
        $this->assertSame('/panel/', $this->describe($this->logIn('alice'))['to'] ?? null);
        $this->assertSame(200, $this->send('GET', '/panel/dashboard/')->status()->code());

        $this->send('GET', '/panel/logout/');

        $this->assertSame('/panel/login/', $this->describe($this->send('GET', '/panel/dashboard/'))['to'] ?? null);
    }

    /**
     * @return array{status: int, to?: string, title?: string}
     */
    private function describe(Response $response): array
    {
        $description = ['status' => $response->status()->code()];

        if (($location = $response->headers()->get('Location')) !== null) {
            $description['to'] = $location;
        }

        if (preg_match('~<title>([^<]*)</title>~', $response->content(), $matches) === 1) {
            $description['title'] = html_entity_decode($matches[1]);
        }

        return $description;
    }
}
