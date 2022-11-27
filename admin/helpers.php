<?php

use Formwork\Formwork;
use Formwork\Utils\FileSystem;

return [
    'assets' => fn () => Formwork::instance()->admin()->assets(),
    'icon' => fn (string $icon) => FileSystem::read(ADMIN_PATH . 'assets' . DS . 'icons' . DS . 'svg' . DS . $icon . '.svg')
];
