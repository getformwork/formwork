<?php

// Define constants
const FORMWORK_PATH = ROOT_PATH . 'formwork' . DS;
const SITE_PATH = ROOT_PATH . 'site' . DS;
const CONFIG_PATH = SITE_PATH . 'config' . DS;
const ADMIN_PATH = ROOT_PATH . 'admin' . DS;

// Check PHP version requirements
if (!version_compare(PHP_VERSION, '8.0.2', '>=')) {
    require __DIR__ . DS . 'views' . DS . 'errors' . DS . 'phpversion.php';
    exit;
}

// Check if Composer autoloader is available
if (file_exists($autoload = ROOT_PATH . 'vendor' . DS . 'autoload.php')) {
    require $autoload;
} else {
    require __DIR__ . DS . 'views' . DS . 'errors' . DS . 'install.php';
    exit;
}
