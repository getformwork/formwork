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

    protected function tearDownTempDirectory(): void
    {
        if (FileSystem::isDirectory(TESTS_TMP_PATH, assertExists: false)) {
            FileSystem::deleteDirectory(TESTS_TMP_PATH, recursive: true);
        }
    }
}
