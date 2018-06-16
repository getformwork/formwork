<?php

namespace Formwork\Admin\Controllers;
use Formwork\Admin\Admin;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Utils\Language;
use Formwork\Admin\Utils\Notification;
use Formwork\Core\Scheme;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;

abstract class AbstractController {

    public function __construct() {
        $this->uri = Uri::path();
    }

    protected function scheme($template) {
        return new Scheme($template);
    }

    public function formwork() {
        return Formwork::instance();
    }

    protected function label($key) {
        return call_user_func_array(array(Language::class, 'get'), func_get_args());
    }

    protected function languages() {
        return Admin::languages();
    }

    protected function language() {
        return Admin::instance()->language();
    }

    public function notification() {
        return Notification::exists() ? Notification::get() : null;
    }

    public function notify($text, $type) {
        Notification::send($text, $type);
    }

    public function registry($name) {
        return Admin::instance()->registry($name);
    }

    public function log($name) {
        return Admin::instance()->log($name);
    }

    protected function option($option) {
        return Formwork::instance()->option($option);
    }

    public function pageUri($page) {
        return rtrim(FileSystem::dirname(HTTPRequest::root()), '/') . '/' . ltrim($page->slug(), '/');
    }

    public function redirect($uri, $code = 302, $exit = false) {
        Admin::instance()->redirect($uri, $code, $exit);
    }

    public function uri($subpath) {
        return Admin::instance()->uri($subpath);
    }

    public function user() {
        return Admin::instance()->loggedUser();
    }

    protected function field($field, $render = true) {
        return $this->view('fields.' . $field->type(), array('field' => $field), $render);
    }

    protected function fields($fields, $render = true) {
        $output = '';
        if ($fields instanceof Fields) {
            foreach ($fields as $field) {
                $output .= $this->field($field, false);
            }
        }
        if ($render) {
            echo $output;
        } else {
            return $output;
        }
    }

    protected function view($view, $data = array(), $render = true) {
        $file = ADMIN_PATH . 'views' . DS . str_replace('.', DS, $view) . '.php';
        FileSystem::assert($file);
        $output = $this->__renderToString($file, $data);
        if ($render) {
            echo $output;
        } else {
            return $output;
        }
    }

    private function __renderToString($file, $data) {
        ob_start();
        extract($data);
        include $file;
        return ob_get_clean();
    }

    private function __render($file, $data) {
        echo $this->__renderToString($file, $data);
    }

}
