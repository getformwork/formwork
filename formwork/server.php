<?php

$root = $_SERVER['DOCUMENT_ROOT'];
$path = $_SERVER['SCRIPT_NAME'];

// Emulate the `mod_rewrite` rules defined in .htaccess
if ($path !== '/index.php' && is_file($root . $path)) {
    switch (true) {
        case preg_match('~^/site/templates/assets/.*~i', $path):
        case preg_match('~^/panel/assets/.*~i', $path):
            return false;

        case preg_match('~^/(panel|backup|bin|cache|formwork|site|vendor)/.*~i', $path):
        case preg_match('~^/(.*)\.(md|yaml|json|neon)/?$~i', $path):
        case preg_match('~^/(\.(.*)|LICENSE|composer\.lock)/?$~i', $path):
            break;

        default:
            return false;
    }
}

// Log requests to the console as if they were not rewritten
register_shutdown_function(fn () => error_log(sprintf(
    '%s:%d [%d]: %s %s',
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['REMOTE_PORT'],
    http_response_code(),
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI']
), 4));

$_SERVER['SCRIPT_FILENAME'] = $root . DIRECTORY_SEPARATOR . 'index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__ . '/index.php';
