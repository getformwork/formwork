<?php

use Formwork\Formwork;
use Formwork\Utils\FileSystem;

return [
    'assets' => fn () => Formwork::instance()->panel()->assets(),

    'icon' => fn (string $icon) => FileSystem::read(PANEL_PATH . 'assets' . DS . 'icons' . DS . 'svg' . DS . $icon . '.svg')
];
