<?php

namespace Formwork\Users;

use Formwork\Utils\Str;
use InvalidArgumentException;
use UnexpectedValueException;

class InitialsImageGenerator
{
    /**
     * Cache for generated images
     *
     * @var array<string, string>
     */
    private static array $cache = [];

    /**
     * Generate a svg data URI for a user image with the initials of the given name
     */
    public static function generate(string $name): string
    {
        $backgroundColor = self::getBackgroundColor($name);
        $textColor = self::getTextColor($backgroundColor);
        $uri = 'data:image/svg+xml,' . rawurlencode(self::generateSvg(self::getInitials(Str::escapeAttr($name)), $backgroundColor, $textColor));
        return self::$cache[$uri] ?? self::$cache[$uri] = $uri;
    }

    private static function getInitials(string $name, int $length = 2): string
    {
        $initials = preg_replace('/\P{Lu}/u', '', mb_convert_case($name, MB_CASE_TITLE))
            ?? throw new UnexpectedValueException(sprintf('Cannot extract initials from name "%s"', $name));
        return mb_substr($initials, 0, $length);
    }

    private static function getBackgroundColor(string $name): string
    {
        return '#' . substr(sha1($name), 0, 6);
    }

    private static function getTextColor(string $color): string
    {
        [$r, $g, $b] = sscanf($color, '#%2x%2x%2x')
            ?? throw new InvalidArgumentException(sprintf('Invalid color format: "%s"', $color));
        $luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;
        return $luminance > 149 ? '#000000' : '#ffffff';
    }

    private static function generateSvg(string $initials, string $backgroundColor, string $textColor): string
    {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 512 512"><circle cx="50%%" cy="50%%" r="50%%" fill="%s"/><text fill="%s" x="50%%" y="50%%" text-anchor="middle" style="font-size: 224px; font-family: ui-rounded, system-ui, sans-serif; dominant-baseline: central;">%s</text></svg>',
            $backgroundColor,
            $textColor,
            $initials
        );
    }
}
