<?php

return [
    'assets' => static fn () => \Formwork\Formwork::instance()->admin()->assets(),
    'icon' => static fn (string $icon) => \Formwork\Utils\FileSystem::read(ADMIN_PATH . 'assets' . DS . 'icons' . DS . 'svg' . DS . $icon . '.svg')
];
