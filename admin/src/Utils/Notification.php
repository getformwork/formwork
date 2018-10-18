<?php

namespace Formwork\Admin\Utils;

class Notification
{
    /**
     * Send a notification
     *
     * @param string $text
     * @param string $type Notification type ('error', 'info', 'success', 'warning')
     */
    public static function send($text, $type = '')
    {
        Session::set('FORMWORK_NOTIFICATION', array('text' => $text, 'type' => $type));
    }

    /**
     * Return whether a notification has been sent
     *
     * @return bool
     */
    public static function exists()
    {
        return Session::has('FORMWORK_NOTIFICATION');
    }

    /**
     * Get notification from session data
     *
     * @param bool $remove Whether to remove the notification
     *
     * @return array
     */
    public static function get($remove = true)
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
    public static function remove()
    {
        Session::remove('FORMWORK_NOTIFICATION');
    }
}
