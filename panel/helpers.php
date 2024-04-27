<?php

use Formwork\Config\Config;
use Formwork\Panel\Panel;
use Formwork\Utils\FileSystem;

return function (Config $config, Panel $panel) {
    return [
        'assets' => $panel->assets(...),

        'icon' => fn (string $icon) => FileSystem::read(FileSystem::joinPaths($config->get('system.panel.paths.assets'), '/icons/svg/', $icon . '.svg')),
    ];
};
