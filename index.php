<?php

use Formwork\Core\Formwork;

const DS = DIRECTORY_SEPARATOR;
const ROOT_PATH = __DIR__ . DS;
const FORMWORK_PATH = ROOT_PATH . 'formwork' . DS;
const SITE_PATH = ROOT_PATH . 'site' . DS;
const CONFIG_PATH = SITE_PATH . 'config' . DS;
const ADMIN_PATH = ROOT_PATH . 'admin' . DS;

require ROOT_PATH . 'vendor' . DS . 'autoload.php';

$formwork = new Formwork();
$formwork->run();
