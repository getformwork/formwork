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
     * View variables
     *
     * @var array
     */
    protected $vars = array();

    /**
     * View assets instance
     *
     * @var Assets
     */
    protected $assets;

    /**
     * Create a new View instance
     *
     * @param string $name
     * @param array  $vars
     */
    public function __construct($name, array $vars = array())
    {
        $this->name = $name;
        $this->vars = array_merge($this->defaults(), $vars);
    }

    /**
     * Insert a view
     *
     * @param string $name
     * @param array  $vars
     */
    public function insert($name, array $vars = array())
    {
        $file = Admin::VIEWS_PATH . str_replace('.', DS, $name) . '.php';

        if (!FileSystem::exists($file)) {
            throw new RuntimeException('View ' . $name . ' not found');
        }

        Renderer::load($file, array_merge($this->vars, $vars), $this, static::class);
    }

    /**
     * Render a view
     *
     * @param bool $return Whether to return or render the view
     *
     * @return string|void
     */
    public function render($return = false)
    {
        ob_start();

        $this->insert($this->name);

        if ($return) {
            return ob_get_clean();
        }
        ob_end_flush();
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

    /**
     * @see Formwork\Utils\Str::escape()
     *
     * @param string $string
     */
    protected function escape($string)
    {
        return Str::escape($string);
    }
}
