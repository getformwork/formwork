<?php

namespace Formwork\Tests\Unit\Panel;

use Formwork\Cms\App;
use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Parsers\Yaml;
use Formwork\Tests\Unit\Users\BuildsPasswordHashes;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

/**
 * Sends requests to the whole application (router, filters, controllers, views), as a browser would, keeping the session
 *
 * @mixin TestCase
 */
trait BuildsPanelClient
{
    use BuildsPasswordHashes;

    /**
     * @var array<string, string>
     */
    private array $panelCookies = [];

    /**
     * Leave a clean application to the following tests, as it keeps the services of the last request
     */
    #[After]
    protected function resetPanelClient(): void
    {
        App::instance()->unload();
        $this->panelCookies = [];
    }

    /**
     * Send a request to the application. Panel URIs start with `/panel/`
     *
     * @param array<string, mixed> $input Query string for GET requests, form data otherwise
     */
    protected function send(string $method, string $uri, array $input = [], bool $script = false): Response
    {
        $request = new Request($method === 'GET' ? [] : $input, $method === 'GET' ? $input : [], $this->panelCookies, [], [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $uri,
            'SCRIPT_NAME'    => '/index.php',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'REMOTE_ADDR'    => '203.0.113.7',
            ...($script ? ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'] : []),
        ]);

        $app = App::instance();

        $outputLevel = ob_get_level();
        $sessionName = session_name();

        try {
            return $app->handle($request);
        } finally {
            // Error pages discard all the output buffers, including the one of PHPUnit
            while (ob_get_level() < $outputLevel) {
                ob_start();
            }

            // PHP supports only one session per process: keep its id as a browser keeps its cookie
            session_name((string) $sessionName);

            // PHP supports only one session per process, so none must be left active
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            if (session_id() !== '') {
                $this->panelCookies = [$request->session()->name() => (string) session_id()];
                session_id('');
            }
        }
    }

    /**
     * @param array<string, mixed> $account
     */
    protected function createAccount(string $username, string $role = 'admin', string $password = 'password', array $account = []): void
    {
        $accounts = App::instance()->config()->getString('system.users.paths.accounts');

        if (!FileSystem::isDirectory($accounts, assertExists: false)) {
            FileSystem::createDirectory($accounts, recursive: true);
        }

        FileSystem::write("{$accounts}/{$username}.yaml", Yaml::encode([
            'username' => $username,
            'fullname' => ucfirst($username),
            'email'    => "{$username}@example.test",
            'role'     => $role,
            'language' => 'en',
            'hash'     => self::hashPassword($password),
            ...$account,
        ]));
    }

    /**
     * Log in through the login form, like a browser
     */
    protected function logIn(string $username, string $password = 'password'): Response
    {
        $form = $this->send('GET', '/panel/login/');

        preg_match('/name="csrf-token" value="([^"]+)"/', $form->content(), $matches);

        return $this->send('POST', '/panel/login/', ['login' => $username, 'password' => $password, 'csrf-token' => $matches[1] ?? '']);
    }
}
