<?php

use Formwork\Cms\App;

define('ROOT_PATH', dirname(__DIR__));

const SYSTEM_PATH = ROOT_PATH . '/formwork';

const TESTS_PATH = __DIR__;

const TESTS_TMP_PATH = TESTS_PATH . '/tmp';

require ROOT_PATH . '/vendor/autoload.php';

require TESTS_PATH . '/Environment.php';

require TESTS_PATH . '/Unit/Utils/Fixtures/functions.php';

(new App())->load();
