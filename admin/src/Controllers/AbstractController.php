<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\AdminTrait;
use Formwork\Admin\Fields\Field;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Language;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;

abstract class AbstractController
{
    use AdminTrait;

    protected $location;

    protected $modals = array();

    public function __construct()
    {
        $this->location = strtolower(substr(strrchr(static::class, '\\'), 1));
    }

    protected function formwork()
    {
        return Formwork::instance();
    }

    protected function option($option)
    {
        return Formwork::instance()->option($option);
    }

    protected function language()
    {
        return Admin::instance()->language()->code();
    }

    protected function languages()
    {
        return Language::availableLanguages();
    }

    protected function user()
    {
        return Admin::instance()->loggedUser();
    }

    protected function escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT | ENT_SUBSTITUTE);
    }

    protected function defaults()
    {
        return array(
            'location' => $this->location,
            'csrfToken' => CSRFToken::get(),
            'modals' => implode($this->modals)
        );
    }

    protected function field($field, $render = true)
    {
        if (!($field instanceof Field)) {
            throw new InvalidArgumentException(__METHOD__ . ' accepts only instances of ' . Field::class);
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

    protected function modal($modal, $data = array(), $render = false)
    {
        $this->modals[] = $this->view('modals.' . $modal, $data, $render);
    }

    protected function view($view, $data = array(), $render = true)
    {
        $file = ADMIN_PATH . 'views' . DS . str_replace('.', DS, $view) . '.php';
        FileSystem::assert($file);
        $output = $this->renderToString($file, array_merge($this->defaults(), $data));
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
