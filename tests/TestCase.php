<?php

namespace Formwork\Tests;

use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;

class TestCase extends BaseTestCase
{
    #[BeforeClass(100)]
    public static function setUpEnvironment(): void
    {
        require_once TESTS_PATH . '/Environment.php';

        $dir = dirname((new ReflectionClass(static::class))->getFileName());

        if (FileSystem::exists($dir . '/fixtures/functions.php')) {
            require_once $dir . '/fixtures/functions.php';
        }
    }

    protected function setUpTempDirectory(): void
    {
        if (!FileSystem::isDirectory(TESTS_TMP_PATH, assertExists: false)) {
            FileSystem::createDirectory(TESTS_TMP_PATH);
        }
    }

    protected function tearDownTempDirectory(): void
    {
        if (FileSystem::isDirectory(TESTS_TMP_PATH, assertExists: false)) {
            FileSystem::deleteDirectory(TESTS_TMP_PATH, recursive: true);
        }
    }
}
