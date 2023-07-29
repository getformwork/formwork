<?php

use Formwork\App;

const DS = DIRECTORY_SEPARATOR;
const ROOT_PATH = __DIR__;
const SYSTEM_PATH = ROOT_PATH . '/formwork';

require SYSTEM_PATH . '/bootstrap.php';

$formwork = new App();
$formwork->run();
