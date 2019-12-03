<?php

namespace Formwork\Admin\View;

use Formwork\Admin\Admin;
use Formwork\Admin\AdminTrait;
use Formwork\Core\Assets;
use Formwork\Core\Formwork;
use Formwork\Template\Renderer;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use LogicException;
use RuntimeException;

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
    protected $vars = [];

    /**
     * View assets instance
     *
     * @var Assets
     */
    protected $assets;

    /**
     * Helper functions to be used in views
     *
     * @var array
     */
    protected static $helpers = [
        'escape'     => [Str::class, 'escape'],
        'removeHTML' => [Str::class, 'removeHTML']
    ];

    /**
     * Create a new View instance
     *
     * @param string $name
     * @param array  $vars
     */
    public function __construct($name, array $vars = [])
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
    public function insert($name, array $vars = [])
    {
        $file = Admin::VIEWS_PATH . str_replace('.', DS, $name) . '.php';

        if (!FileSystem::exists($file)) {
            throw new RuntimeException('View ' . $name . ' not found');
        }

        Renderer::load($file, array_merge($this->vars, $vars), $this);
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
        if ($this->assets !== null) {
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
        return [
            'formwork' => Formwork::instance(),
            'site'     => Formwork::instance()->site(),
            'admin'    => Admin::instance()
        ];
    }

    public function __call($name, $arguments)
    {
        if (method_exists(AdminTrait::class, $name)) {
            return $this->$name(...$arguments);
        }
        if (isset(static::$helpers[$name])) {
            return static::$helpers[$name](...$arguments);
        }
        throw new LogicException('Invalid method ' . static::class . '::' . $name);
    }
}
