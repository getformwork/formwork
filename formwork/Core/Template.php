<?php

namespace Formwork\Core;

use Formwork\Utils\FileSystem;
use RuntimeException;

class Template
{
    /**
     * Template file path
     *
     * @var string
     */
    protected $path;

    /**
     * Template file extension
     *
     * @var string
     */
    protected $extension;

    /**
     * Template name
     *
     * @var string
     */
    protected $name;

    /**
     * Template variables
     *
     * @var array
     */
    protected $vars;

    /**
     * Template scheme
     *
     * @var Scheme
     */
    protected $scheme;

    /**
     * Whether template is being rendered
     *
     * @var bool
     */
    protected static $rendering = false;

    /**
     * Create a new Template instance
     *
     * @param string $template
     */
    public function __construct($template)
    {
        $this->path = Formwork::instance()->site()->templatesPath();
        $this->extension = Formwork::instance()->option('templates.extension');
        $this->name = $template;
        $this->vars = $this->defaults();
    }

    /**
     * Get template name
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get template Scheme
     *
     * @return Scheme
     */
    public function scheme()
    {
        if (!is_null($this->scheme)) {
            return $this->scheme;
        }
        return $this->scheme = new Scheme($this->name);
    }

    /**
     * Render a Page
     *
     * @param array $vars   Variable to pass to the template
     * @param bool  $return Whether to return rendered content
     *
     * @return string
     */
    public function renderPage(Page $page, $vars, $return = false)
    {
        if (static::$rendering) {
            throw new RuntimeException(__METHOD__ . ' not allowed while rendering');
        }

        $this->vars['page'] = $page;
        $this->vars = array_merge($this->vars, $vars);

        if (!is_null($this->controller())) {
            extract($this->vars);
            $this->vars = array_merge($this->vars, (array) include($this->controller()));
        }

        return $this->render($return);
    }

    /**
     * Return an array containing the default data
     *
     * @return array
     */
    protected function defaults()
    {
        return array(
            'params' => Formwork::instance()->router()->params(),
            'site'   => Formwork::instance()->site()
        );
    }

    /**
     * Load template controller if exists and return its path
     *
     * @return string|null
     */
    protected function controller()
    {
        if (static::$rendering) {
            throw new RuntimeException(__METHOD__ . ' not allowed while rendering');
        }
        $controllerFile = $this->path . 'controllers' . DS . $this->name . '.php';
        if (FileSystem::exists($controllerFile)) {
            return $controllerFile;
        }
        return null;
    }

    /**
     * Insert a partial template
     *
     * @param string $filename
     * @param array  $vars
     */
    protected function insert($filename, $vars = array())
    {
        extract(array_merge($this->vars, $vars));
        include $this->path . str_replace('_', 'partials' . DS, $filename) . $this->extension;
    }

    /**
     * Render template
     *
     * @param bool $return Whether to return rendered content or not
     *
     * @return string|null
     */
    protected function render($return = false)
    {
        if (static::$rendering) {
            throw new RuntimeException(__METHOD__ . ' not allowed while rendering');
        }

        ob_start();

        static::$rendering = true;
        $this->insert($this->name);
        static::$rendering = false;

        if ($return) {
            return ob_get_clean();
        }

        ob_end_flush();
    }

    public function __toString()
    {
        return $this->name;
    }
}
