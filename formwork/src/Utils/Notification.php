<?php

namespace Formwork\Utils;

use InvalidArgumentException;

class Notification
{
    /**
     * Info notification type
     *
     * @var string
     */
    public const INFO = 'info';

    /**
     * Success notification type
     *
     * @var string
     */
    public const SUCCESS = 'success';

    /**
     * Warning notification type
     *
     * @var string
     */
    public const WARNING = 'warning';

    /**
     * Error notification type
     *
     * @var string
     */
    public const ERROR = 'error';

    /**
     * Session key to store the notification
     *
     * @var string
     */
    protected const SESSION_KEY = 'FORMWORK_NOTIFICATION';

    /**
     * Send a notification
     */
    public static function send(string $text, string $type = self::INFO): void
    {
        if (!in_array($type, [self::INFO, self::SUCCESS, self::WARNING, self::ERROR], true)) {
            throw new InvalidArgumentException('Invalid notification type: ' . $type);
        }
        Session::set(self::SESSION_KEY, ['text' => $text, 'type' => $type]);
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
