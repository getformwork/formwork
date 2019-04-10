<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\AdminTrait;
use Formwork\Admin\Fields\Field;
use Formwork\Admin\Fields\Fields;
use Formwork\Admin\Language;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Users\User;
use Formwork\Core\Assets;
use Formwork\Core\Formwork;
use Formwork\Core\Site;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;

abstract class AbstractController
{
    use AdminTrait;

    /**
     * Current panel location
     *
     * @var string
     */
    protected $location;

    /**
     * Assets instance
     *
     * @var Assets
     */
    protected $assets;

    /**
     * All loaded modals
     *
     * @var array
     */
    protected $modals = array();

    /**
     * Create a new Controller instance
     */
    public function __construct()
    {
        $this->location = strtolower(substr(strrchr(static::class, '\\'), 1));
    }

    /**
     * Return site instance
     *
     * @return Site
     */
    protected function site()
    {
        return Formwork::instance()->site();
    }

    /**
     * Get a system option
     *
     * @param string $option
     */
    protected function option($option)
    {
        return Formwork::instance()->option($option);
    }

    /**
     * Get current language code
     *
     * @return string
     */
    protected function language()
    {
        return Admin::instance()->language()->code();
    }

    /**
     * Get all available languages
     *
     * @return array
     */
    protected function languages()
    {
        return Language::availableLanguages();
    }

    /**
     * Get logged user
     *
     * @return User
     */
    protected function user()
    {
        return Admin::instance()->user();
    }

    /**
     * Get Assets instance
     *
     * @return Assets
     */
    protected function assets()
    {
        if (!is_null($this->assets)) {
            return $this->assets;
        }
        return $this->assets = new Assets(
            ADMIN_PATH . 'assets' . DS,
            $this->uri('/assets/')
        );
    }

    /**
     * @see Formwork\Utils\Str::escape()
     */
    protected function escape($string)
    {
        return Str::escape($string);
    }

    /**
     * Return default data passed to views
     *
     * @return array
     */
    protected function defaults()
    {
        return array(
            'location'  => $this->location,
            'csrfToken' => CSRFToken::get()
        );
    }

    /**
     * Ensure current user has a permission
     *
     * @param string $permission
     */
    protected function ensurePermission($permission)
    {
        if (!$this->user()->permissions()->has($permission)) {
            $errors = new Errors();
            $errors->forbidden();
            exit;
        }
    }

    /**
     * Render a field
     *
     * @param bool $render Whether to render or return the field
     *
     * @return string|void
     */
    protected function field(Field $field, $render = true)
    {
        return $this->view('fields.' . $field->type(), array('field' => $field), $render);
    }

    /**
     * Render multiple fields
     *
     * @param bool $render Whether to render or return the fields
     *
     * @return string|void
     */
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

    /**
     * Load a modal to be rendered later
     *
     * @param string $modal Name of the modal
     * @param array  $data  Data to pass to the modal
     */
    protected function modal($modal, $data = array())
    {
        $this->modals[] = $this->view('modals.' . $modal, $data, false);
    }

    /**
     * Get all rendered modals
     *
     * @return string
     */
    protected function modals()
    {
        return implode($this->modals);
    }

    /**
     * Render a view
     *
     * @param string $view   Name of the view
     * @param array  $data   Data to pass to the view
     * @param bool   $render Whether to render or return the view
     *
     * @return string|void
     */
    protected function view($view, $data = array(), $render = true)
    {
        $file = VIEWS_PATH . str_replace('.', DS, $view) . '.php';
        FileSystem::assert($file);
        $output = $this->renderToString($file, array_merge($this->defaults(), $data));
        if ($render) {
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     * Render a script
     *
     * @param string $file Path to the script
     * @param array  $data Data to pass to the view
     *
     * @return string
     */
    private function renderToString($file, $data)
    {
        ob_start();
        extract($data);
        include $file;
        return ob_get_clean();
    }
}
