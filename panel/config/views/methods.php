<?php

use Formwork\Cms\App;
use Formwork\Panel\Panel;

return function (App $app, Panel $panel) {
    return [
        'modals' => $panel->modals(...),

        'icon' => fn(string $icon) => $app->assets()->get("@panel/icons/svg/{$icon}.svg")->content(),
    ];
};
