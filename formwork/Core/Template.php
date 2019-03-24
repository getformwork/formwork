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
    protected $vars = array();

    /**
     * Template scheme
     *
     * @var Scheme
     */
    protected $scheme;

    /**
     * Template layout name
     *
     * @var string
     */
    protected $layout;

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
        $this->path = Formwork::instance()->option('templates.path');
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
     * Set template layout
     *
     * @param string $name
     */
    protected function layout($name)
    {
        if (!is_null($this->layout)) {
            throw new RuntimeException('The layout for ' . $this->name . ' template is already set');
        }
        $this->layout = $name;
    }

    /**
     * Insert a template
     *
     * @param string $name
     * @param array  $vars
     */
    protected function insert($name, $vars = array())
    {
        if ($name[0] === '_') {
            $name = 'partials' . DS . substr($name, 1);
        }

        $filename = $this->path . $name . $this->extension;

        if (!FileSystem::exists($filename)) {
            throw new RuntimeException('Template ' . $name . ' not found');
        }

        extract(array_merge($this->vars, $vars));

        include $filename;
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

        if (!is_null($this->layout)) {
            $layout = new Template('layouts' . DS . $this->layout);
            $layout->vars = array_merge($this->vars, array('content' => ob_get_contents()));
            ob_clean(); // Clean but don't end output buffer

            static::$rendering = false;

            $layout->render();
        }

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
