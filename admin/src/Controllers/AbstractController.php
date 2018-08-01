<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Fields\Field;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Utils\Language;
use Formwork\Admin\Utils\Notification;
use Formwork\Core\Scheme;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use InvalidArgumentException;

abstract class AbstractController
{
    public function __construct()
    {
        $this->uri = Uri::path();
    }

    public function formwork()
    {
        return Formwork::instance();
    }

    public function notification()
    {
        return Notification::exists() ? Notification::get() : null;
    }

    public function notify($text, $type)
    {
        Notification::send($text, $type);
    }

    public function registry($name)
    {
        return Admin::instance()->registry($name);
    }

    public function log($name)
    {
        return Admin::instance()->log($name);
    }

    public function redirect($uri, $code = 302, $exit = false)
    {
        Admin::instance()->redirect($uri, $code, $exit);
    }

    public function uri($subpath)
    {
        return Admin::instance()->uri($subpath);
    }

    public function siteUri()
    {
        return rtrim(FileSystem::dirname(HTTPRequest::root()), '/') . '/';
    }

    public function pageUri($page)
    {
        return $this->siteUri() . ltrim($page->slug(), '/');
    }

    public function user()
    {
        return Admin::instance()->loggedUser();
    }

    protected function scheme($template)
    {
        return new Scheme($template);
    }

    protected function label($key)
    {
        return call_user_func_array(array(Language::class, 'get'), func_get_args());
    }

    protected function languages()
    {
        return Admin::languages();
    }

    protected function language()
    {
        return Admin::instance()->language();
    }

    protected function option($option)
    {
        return Formwork::instance()->option($option);
    }

    protected function escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT | ENT_SUBSTITUTE);
    }

    protected function field($field, $render = true)
    {
        if (!($field instanceof Field)) {
            throw new InvalidArgumentException(__METHOD__ . ' accepts only instances of Formwork\Admin\Fields\Field');
        }
        return $this->view('fields.' . $field->type(), array('field' => $field), $render);
    }

    protected function fields(Fields $fields, $render = true)
    {
        $output = '';
        foreach ($fields as $field) {
            $output .= $this->field($field, false);
        }
        if ($render) {
            echo $output;
        } else {
            return $output;
        }
    }

    protected function view($view, $data = array(), $render = true)
    {
        $file = ADMIN_PATH . 'views' . DS . str_replace('.', DS, $view) . '.php';
        FileSystem::assert($file);
        $output = $this->renderToString($file, $data);
        if ($render) {
            echo $output;
        } else {
            return $output;
        }
    }

    private function renderToString($file, $data)
    {
        ob_start();
        extract($data);
        include $file;
        return ob_get_clean();
    }

    private function render($file, $data)
    {
        echo $this->renderToString($file, $data);
    }
}
