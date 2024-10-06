<?php

namespace Formwork\Users;

enum ColorScheme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case Auto = 'auto';

    public function getCompatibleSchemes(): string
    {
        return match ($this) {
            self::Light => 'light',
            self::Dark  => 'dark',
            self::Auto  => 'light dark',
        };
    }
}
