<?php

use Formwork\Core\Formwork;
use Formwork\Admin\Statistics;

const DS = DIRECTORY_SEPARATOR;
const ROOT_PATH = __DIR__ . DS;
const FORMWORK_PATH = ROOT_PATH . 'formwork' . DS;
const CONFIG_PATH = ROOT_PATH . 'config' . DS;
const ADMIN_PATH = ROOT_PATH . 'admin' . DS;
const ACCOUNTS_PATH = ADMIN_PATH . 'accounts' . DS;
const SCHEMES_PATH = ADMIN_PATH . 'schemes' . DS;
const TRANSLATIONS_PATH = ADMIN_PATH . 'translations' . DS;
const LOGS_PATH = ADMIN_PATH . 'logs' . DS;
const VIEWS_PATH = ADMIN_PATH . 'views' . DS;

require ROOT_PATH . 'vendor' . DS . 'autoload.php';

$formwork = new Formwork();
$formwork->run();

if (class_exists('Formwork\Admin\Statistics')) {
    $statistics = new Statistics();
    $statistics->trackVisit();
}
