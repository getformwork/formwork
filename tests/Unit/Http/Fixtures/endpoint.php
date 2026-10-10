<?php

/**
 * Router script of the built-in PHP server used by the `Header` and `Cookie` utility tests
 *
 * The CLI SAPI does not keep track of the headers sent by `header()` and `setcookie()`,
 * so the tests ask the `cli-server` SAPI to send them and inspect the real response.
 */

use Formwork\Http\Request;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Http\Session\MessageType;
use Formwork\Http\Session\Session;
use Formwork\Http\Utils\Cookie;
use Formwork\Http\Utils\Header;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

if (($_GET['action'] ?? '') === 'session') {
    $session = new Session(Request::fromGlobals());
    $session->setPath($_GET['path']);

    if (isset($_GET['duration'])) {
        $session->setDuration((int) $_GET['duration']);
    }

    switch ($_GET['do']) {
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

    $response = ['id' => session_id()];

    if ($_GET['do'] === 'read') {
        $response['user'] = $session->get('user');
        $response['messages'] = $session->messages()->getAll();
    }

    $session->save();

    echo json_encode($response);
    return;
}

switch ($_GET['action'] ?? '') {
    case 'send':
        Header::send('X-Formwork-Test', ' value ');
        break;

    case 'replace':
        Header::send('X-Formwork-Test', 'first');
        Header::send('X-Formwork-Test', 'second');
        Header::send('X-Formwork-Test', 'third', replace: false);
        break;

    case 'content-type':
        Header::contentType('text/plain; charset=utf-8');
        break;

    case 'status':
        Header::sendStatus(ResponseStatus::Created);
        break;

    case 'not-found':
        Header::notFound();
        break;

    case 'redirect':
        Header::redirect('/target', ResponseStatus::SeeOther);

        // no break: `Header::redirect()` never returns
    case 'cookie-send':
        Cookie::send('first', 'one');
        break;

    case 'cookie-options':
        Cookie::send('options', 'value', [
            'expires'  => 2_000_000_000,
            'path'     => '/panel',
            'domain'   => 'example.test',
            'secure'   => true,
            'httpOnly' => true,
            'sameSite' => Cookie::SAMESITE_STRICT,
        ]);
        break;

    case 'cookie-defaults':
        Cookie::send('defaults', 'value');
        break;

    case 'cookie-multiple':
        Cookie::send('first', 'one');
        Cookie::send('second', 'two');
        Cookie::send('third', 'three');
        break;

    case 'cookie-replace':
        Cookie::send('first', 'one');
        Cookie::send('second', 'two');
        Cookie::send('first', 'uno');
        break;

    case 'cookie-remove-received':
        echo var_export(Cookie::remove('received'), true);
        break;

    case 'cookie-remove-unknown':
        echo var_export(Cookie::remove('unknown'), true);
        break;

    case 'cookie-remove-forced':
        echo var_export(Cookie::remove('unknown', ['path' => '/panel'], forceSend: true), true);
        break;

    case 'cookie-remove-pending':
        Cookie::send('pending', 'value');
        Cookie::send('other', 'value');
        echo var_export(Cookie::remove('pending'), true);
        break;

    case 'response':
        // Headers set before the response is sent must be kept (cookies) or merged (the others)
        header('X-Before: before');
        setcookie('before', '1');

        $response = new Response(
            $_GET['body'] ?? 'body',
            ResponseStatus::fromCode((int) ($_GET['status'] ?? 200)),
            json_decode($_GET['headers'] ?? '[]', true),
        );
        $response->prepare(Request::fromGlobals());
        $response->send();
        break;

    case 'after-output':
        echo 'output';
        flush();

        try {
            Header::send('X-Formwork-Test', 'late');
        } catch (RuntimeException $exception) {
            echo '|' . $exception->getMessage();
        }
        break;
}
