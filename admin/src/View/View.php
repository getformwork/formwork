<?php

namespace Formwork\Admin\View;

use Formwork\Admin\Admin;
use Formwork\Admin\AdminTrait;
use Formwork\Core\Assets;
use Formwork\Core\Formwork;
use Formwork\Template\Renderer;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTML;
use Formwork\Utils\Str;
use BadMethodCallException;
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
    protected static $helpers = [];

    /**
     * Create a new View instance
     */
    public function __construct(string $name, array $vars = [])
    {
        $this->name = $name;
        $this->vars = array_merge($this->defaults(), $vars);

        // Load helpers
        if (empty(static::$helpers)) {
            static::$helpers = $this->helpers();
        }
    }

    /**
     * Insert a view
     */
    public function insert(string $name, array $vars = []): void
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
    public function render(bool $return = false)
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
     */
    public function assets(): Assets
    {
        if ($this->assets !== null) {
            return $this->assets;
        }
        return $this->assets = new Assets(ADMIN_PATH . 'assets' . DS, $this->realUri('/assets/'));
    }

    /**
     * Return an array containing the default data
     */
    protected function defaults(): array
    {
        return [
            'formwork' => Formwork::instance(),
            'site'     => Formwork::instance()->site(),
            'admin'    => Admin::instance()
        ];
    }

    /**
     * Return an array containing the helper functions
     */
    protected function helpers(): array
    {
        return [
            'attr'       => [HTML::class, 'attributes'],
            'escape'     => [Str::class, 'escape'],
            'escapeAttr' => [Str::class, 'escapeAttr'],
            'removeHTML' => [Str::class, 'removeHTML']
        ];
    }

    public function __call(string $name, array $arguments)
    {
        if (method_exists(AdminTrait::class, $name)) {
            return $this->$name(...$arguments);
        }
        if (isset(static::$helpers[$name])) {
            return static::$helpers[$name](...$arguments);
        }
        throw new BadMethodCallException('Call to undefined method ' . static::class . '::' . $name . '()');
    }
}
