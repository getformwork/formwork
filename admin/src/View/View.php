<?php

namespace Formwork\Admin\View;

use Formwork\Admin\Admin;
use Formwork\Admin\AdminTrait;
use Formwork\Core\Assets;
use Formwork\Core\Formwork;
use Formwork\Template\Renderer;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;

class View
{
    use AdminTrait;

    /**
     * View name
     *
     * @var string
     */
    protected $name;

    /**
     * View file
     *
     * @var string
     */
    protected $file;

    /**
     * View data
     *
     * @var array
     */
    protected $data;

    /**
     * Assets instance
     *
     * @var Assets
     */
    protected $assets;

    public function __construct($name, array $data = array())
    {
        $this->name = $name;
        $this->file = VIEWS_PATH . str_replace('.', DS, $name) . '.php';
        $this->data = array_merge($this->defaults(), $data);
    }

    /**
     * Render a view
     *
     * @param string $view   Name of the view
     * @param array  $data   Data to pass to the view
     * @param bool   $return Whether to return
     *
     * @return string|void
     */
    public function render($return = false)
    {
        FileSystem::assert($this->file);
        ob_start();
        Renderer::load($this->file, $this->data, $this, static::class);
        if (!$return) {
            ob_end_flush();
        } else {
            return ob_get_clean();
        }
    }

    public function insert($name, array $data = array(), $return = false)
    {
        $view = new static($name, array_merge($this->data, $data));
        return $view->render($return);
    }

    /**
     * Get Assets instance
     *
     * @return Assets
     */
    public function assets()
    {
        if (!is_null($this->assets)) {
            return $this->assets;
        }
        return $this->assets = new Assets(ADMIN_PATH . 'assets' . DS, $this->realUri('/assets/'));
    }

    /**
     * @see Formwork\Utils\Str::escape()
     *
     * @param string $string
     */
    protected function escape($string)
    {
        return Str::escape($string);
    }

    /**
     * Return an array containing the default data
     *
     * @return array
     */
    protected function defaults()
    {
        return array(
            'formwork' => Formwork::instance(),
            'site'     => Formwork::instance()->site(),
            'admin'    => Admin::instance()
        );
    }
}
