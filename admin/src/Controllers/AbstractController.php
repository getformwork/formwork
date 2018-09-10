<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\AdminTrait;
use Formwork\Admin\Fields\Field;
use Formwork\Admin\Fields\Fields;
use Formwork\Core\Scheme;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Uri;
use InvalidArgumentException;

abstract class AbstractController
{
    use AdminTrait;

    public function __construct()
    {
        $this->uri = Uri::path();
    }

    public function user()
    {
        return Admin::instance()->loggedUser();
    }

    protected function scheme($template)
    {
        return new Scheme($template);
    }

    protected function languages()
    {
        return Admin::languages();
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
