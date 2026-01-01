<?php

namespace Formwork\Users;

enum ColorScheme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case Auto = 'auto';

    /**
     * Get compatible schemes for the current scheme
     *
     * @return 'dark'|'light dark'|'light'
     */
    public function getCompatibleSchemes(): string
    {
        return match ($this) {
            self::Light => 'light',
            self::Dark  => 'dark',
            self::Auto  => 'light dark',
        };
    }
}
