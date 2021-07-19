<?php

namespace Formwork\Utils;

use InvalidArgumentException;

class Notification
{
    /**
     * Info notification type
     */
    public const INFO = 'info';

    /**
     * Success notification type
     */
    public const SUCCESS = 'success';

    /**
     * Warning notification type
     */
    public const WARNING = 'warning';

    /**
     * Error notification type
     */
    public const ERROR = 'error';

    /**
     * Session key to store the notification
     */
    protected const SESSION_KEY = 'FORMWORK_NOTIFICATION';

    /**
     * Default notification interval
     */
    protected const DEFAULT_INTERVAL = 5000;

    /**
     * Default notification icons by type
     */
    protected const ICON = [
        self::INFO    => 'info-circle',
        self::SUCCESS => 'check-circle',
        self::WARNING => 'exclamation-triangle',
        self::ERROR   => 'exclamation-octagon'
    ];

    /**
     * Send a notification
     */
    public static function send(string $text, string $type = self::INFO, string $interval = self::DEFAULT_INTERVAL): void
    {
        if (!in_array($type, [self::INFO, self::SUCCESS, self::WARNING, self::ERROR], true)) {
            throw new InvalidArgumentException(sprintf('Invalid notification type "%s"', $type));
        }
        Session::set(self::SESSION_KEY, ['text' => $text, 'type' => $type, 'interval' => $interval, 'icon' => self::ICON[$type]]);
    }

    /**
     * Return whether a notification has been sent
     */
    public static function exists(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    /**
     * Get notification from session data
     *
     * @param bool $remove Whether to remove the notification
     */
    public static function get(bool $remove = true): array
    {
        $notification = Session::get(self::SESSION_KEY);
        if ($remove) {
            static::remove();
        }
        return $notification;
    }

    /**
     * Remove notification from session data
     */
    public static function remove(): void
    {
        Session::remove(self::SESSION_KEY);
    }
}
