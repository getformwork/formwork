<?php

use Formwork\Panel\Panel;

return function (Panel $panel) {
    return [
        'assets' => $panel->assets(...),

        'modals' => $panel->modals(...),

        'icon' => fn(string $icon) => $panel->assets()->get('/icons/svg/' . $icon . '.svg')->content(),
    ];
};
