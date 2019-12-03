<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\AdminTrait;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Users\User;
use Formwork\Admin\View\View;
use Formwork\Core\Formwork;
use Formwork\Core\Site;

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
     * All loaded modals
     *
     * @var array
     */
    protected $modals = [];

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
     * @param string $default
     */
    protected function option($option, $default = null)
    {
        return Formwork::instance()->option($option, $default);
    }

    /*
     * Return default data passed to views
     *
     * @return array
     */
    protected function defaults()
    {
        return [
            'location'  => $this->location,
            'csrfToken' => CSRFToken::get(),
            'modals'    => implode($this->modals)
        ];
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
     * Load a modal to be rendered later
     *
     * @param string $name Name of the modal
     * @param array  $data Data to pass to the modal
     */
    protected function modal($name, array $data = [])
    {
        $this->modals[] = $this->view('modals.' . $name, $data, true);
    }

    /**
     * Render a view
     *
     * @param string $name   Name of the view
     * @param array  $data   Data to pass to the view
     * @param bool   $return Whether to return or render the view
     *
     * @return string|void
     */
    protected function view($name, array $data = [], $return = false)
    {
        $view = new View($name, array_merge($this->defaults(), $data));
        return $view->render($return);
    }
}
