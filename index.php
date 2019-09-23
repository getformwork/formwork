<?php

use Formwork\Core\Formwork;
use Formwork\Admin\Statistics;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . DS);
define('FORMWORK_PATH', ROOT_PATH . 'formwork' . DS);
define('CONFIG_PATH', ROOT_PATH . 'config' . DS);
define('ADMIN_PATH', ROOT_PATH . 'admin' . DS);
define('ACCOUNTS_PATH', ADMIN_PATH . 'accounts' . DS);
define('SCHEMES_PATH', ADMIN_PATH . 'schemes' . DS);
define('TRANSLATIONS_PATH', ADMIN_PATH . 'translations' . DS);
define('LOGS_PATH', ADMIN_PATH . 'logs' . DS);
define('VIEWS_PATH', ADMIN_PATH . 'views' . DS);

require ROOT_PATH . 'vendor' . DS . 'autoload.php';

$formwork = new Formwork();
$formwork->run();

if (class_exists('Formwork\Admin\Statistics')) {
    $statistics = new Statistics();
    $statistics->trackVisit();
}
