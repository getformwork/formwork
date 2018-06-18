<?php

use Formwork\Core\Formwork;
use Formwork\Admin\Statistics;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . DS);
define('FORMWORK_PATH', ROOT_PATH . 'formwork' . DS);
define('CONFIG_PATH', ROOT_PATH . 'config' . DS);
define('LOGS_PATH', ROOT_PATH . 'admin' . DS . 'logs' . DS);

require FORMWORK_PATH . 'loader.php';

$formwork = new Formwork();
$formwork->run();

if (class_exists('Formwork\Admin\Statistics')) {
    $statistics = new Statistics();
    $statistics->trackVisit();
}
