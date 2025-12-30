<?php

use Composer\Autoload\ClassLoader;

define('ROOT_PATH', dirname(__DIR__));

const SYSTEM_PATH = ROOT_PATH . '/formwork';

const TESTS_PATH = __DIR__;

const TESTS_TMP_PATH = TESTS_PATH . '/tmp';

require ROOT_PATH . '/vendor/autoload.php';

$autoloader = new ClassLoader();
$autoloader->addPsr4('Formwork\Tests\\', TESTS_PATH);
$autoloader->register();
