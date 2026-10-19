<?php

namespace Formwork\Tests;

use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;
use Throwable;

class TestCase extends BaseTestCase
{
    #[Before]
    protected function bootApplication(): void
    {
        if ($this->uses(UsesApplication::class)) {
            TestApplication::boot();
        }
    }

    /**
     * Run before `setUp()`, so that it can already write into the temporary directory
     */
    #[Before(1)]
    protected function createTempDirectory(): void
    {
        if ($this->uses(UsesTempDirectory::class) && !FileSystem::isDirectory(TESTS_TMP_PATH, assertExists: false)) {
            FileSystem::createDirectory(TESTS_TMP_PATH);
        }
    }

    /**
     * Run after `tearDown()`, so that it can still read the temporary directory
     */
    #[After(-1)]
    protected function deleteTempDirectory(): void
    {
        if ($this->uses(UsesTempDirectory::class) && FileSystem::isDirectory(TESTS_TMP_PATH, assertExists: false)) {
            FileSystem::deleteDirectory(TESTS_TMP_PATH, recursive: true);
        }
    }

    /**
     * Return the given value typed as `mixed`, to deliberately pass invalid arguments
     */
    protected function unchecked(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Run a callback that must throw an exception of the given class and return it
     *
     * @template T of Throwable
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function captureException(string $class, callable $callback): Throwable
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            if (!$exception instanceof $class) {
                throw $exception;
            }
            return $exception;
        }

        $this->fail(sprintf('Failed asserting that exception of type "%s" is thrown.', $class));
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

    /**
     * Whether the test class, or one of its parents, is marked with the given attribute
     *
     * @param class-string $attribute
     */
    private function uses(string $attribute): bool
    {
        for ($class = new ReflectionClass($this); $class !== false; $class = $class->getParentClass()) {
            if ($class->getAttributes($attribute) !== []) {
                return true;
            }
        }
        return false;
    }
}
