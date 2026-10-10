<?php

namespace Formwork\Tests;

use Formwork\Utils\FileSystem;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUpTempDirectory(): void
    {
        if (!FileSystem::isDirectory(TESTS_TMP_PATH, assertExists: false)) {
            FileSystem::createDirectory(TESTS_TMP_PATH);
        }
    }

    /**
     * Run a callback and return the user deprecation messages it triggered
     *
     * The application installs its own error handler, so PHPUnit cannot observe user deprecations by itself
     *
     * @return list<string>
     */
    protected function captureDeprecations(callable $callback): array
    {
        $messages = [];

        set_error_handler(static function (int $severity, string $message) use (&$messages): bool {
            $messages[] = $message;
            return true;
        }, E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $messages;
    }

    protected function tearDownTempDirectory(): void
    {
        if (FileSystem::isDirectory(TESTS_TMP_PATH, assertExists: false)) {
            FileSystem::deleteDirectory(TESTS_TMP_PATH, recursive: true);
        }
    }
}
