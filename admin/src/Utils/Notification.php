<?php

namespace Formwork\Admin\Utils;

class Notification {

    public static function send($text, $type = '') {
        Session::set('FORMWORK_NOTIFICATION', array('text' => $text, 'type' => $type));
    }

    public static function exists() {
        return Session::has('FORMWORK_NOTIFICATION');
    }

    public static function get($remove = true) {
        $notification = Session::get('FORMWORK_NOTIFICATION');
        if ($remove) static::remove();
        return $notification;
    }

    public static function remove() {
        Session::remove('FORMWORK_NOTIFICATION');
    }

}
