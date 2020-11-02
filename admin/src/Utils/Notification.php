<?php

namespace Formwork\Admin\Utils;

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
     * Send a notification
     */
    public static function send(string $text, string $type = self::INFO): void
    {
        if (!in_array($type, [self::INFO, self::SUCCESS, self::WARNING, self::ERROR], true)) {
            throw new InvalidArgumentException('Invalid notification type: ' . $type);
        }
        Session::set('FORMWORK_NOTIFICATION', ['text' => $text, 'type' => $type]);
    }

    /**
     * Return whether a notification has been sent
     */
    public static function exists(): bool
    {
        return Session::has('FORMWORK_NOTIFICATION');
    }

    /**
     * Get notification from session data
     *
     * @param bool $remove Whether to remove the notification
     */
    public static function get(bool $remove = true): array
    {
        $notification = Session::get('FORMWORK_NOTIFICATION');
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
        Session::remove('FORMWORK_NOTIFICATION');
    }
}
