<?php

use Formwork\Cms\App;
use Formwork\Tests\TestInstallation;

const TESTS_PATH = __DIR__;

const TESTS_TMP_PATH = TESTS_PATH . '/tmp';

require dirname(__DIR__) . '/vendor/autoload.php';

require TESTS_PATH . '/Environment.php';

require TESTS_PATH . '/Unit/Utils/Fixtures/functions.php';

// The application of the tests, over a temporary installation. It is created here, before
// any test can call `App::instance()`, and loaded by the tests that use it
new App(TestInstallation::create());
