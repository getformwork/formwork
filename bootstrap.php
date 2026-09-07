<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('SYSTEM_PATH')) {
    define('SYSTEM_PATH', ROOT_PATH . '/formwork');
}

// Check PHP version requirements
if (!version_compare(PHP_VERSION, '8.3.0', '>=')) {
    if (PHP_SAPI === 'cli') {
        printf("Formwork requires PHP 8.3.0 or higher. You are running PHP %s.\n", PHP_VERSION);
        exit(1);
    }
    require __DIR__ . '/views/errors/phpversion.php';
    exit;
}

// Check if Composer autoloader is available
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
} else {
    if (PHP_SAPI === 'cli') {
        echo "Composer autoloader not found. Please run \"composer install\" in the root directory.\n";
        exit(1);
    }
    require __DIR__ . '/views/errors/install.php';
    exit;
}
