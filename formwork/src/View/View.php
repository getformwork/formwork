<?php

namespace Formwork\View;

use Formwork\Traits\Methods;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\View\Exceptions\RenderingException;
use Throwable;

class View
{
    use Methods;

    /**
     * View type
     */
    protected const TYPE = 'view';

    /**
     * View name
     */
    protected string $name;

    /**
     * View variables
     */
    protected array $vars = [];

    /**
     * View path
     */
    protected string $path;

    /**
     * View file
     */
    protected string $file;

    /**
     * View blocks
     */
    protected array $blocks = [];

    /**
     * View incomplete blocks
     */
    protected array $incompleteBlocks = [];

    /**
     * Layout view
     */
    protected self $layout;

    /**
     * Whether the view is being rendered
     */
    protected bool $rendering = false;

    /**
     * Whether it is allowed to call view methods
     */
    protected bool $allowMethods = false;

    /**
     * Create a new View instance
     */
    public function __construct(string $name, array $vars = [], ?string $path = null, array $methods = [])
    {
        $this->name = $name;
        $this->vars = $vars;
        $this->path = $path;
        $this->file = $this->getFile($this->name);
        $this->methods = $methods;

        if (!FileSystem::exists($this->file)) {
            throw new FileNotFoundException(sprintf('%s "%s" not found', ucfirst(static::TYPE), $this->name));
        }
    }

    /**
     * Get view name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get view path
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Set view layout
     */
    public function layout(string $name): void
    {
        if (isset($this->layout)) {
            throw new RenderingException(sprintf('The layout for the %s "%s" is already set', static::TYPE, $this->name));
        }
        $this->layout = $this->createLayoutView($name);
    }

    /**
     * Insert a view
     */
    public function insert(string $name, array $vars = []): void
    {
        if (!$this->rendering) {
            throw new RenderingException(sprintf('%s() is allowed only in rendering context', __METHOD__));
        }

        $view = new self($name, [...$this->vars, ...$vars], $this->path, $this->methods);

        $view->output();
    }

    /**
     * Render the view
     */
    public function render(): string
    {
        if ($this->rendering) {
            throw new RenderingException(sprintf('%s() not allowed while rendering', __METHOD__));
        }

        // Keep track of the output buffer level,
        // so we can revert to this level if an error occurs
        $level = ob_get_level();

        ob_start();

        try {
            $this->output();
        } catch (Throwable $e) {
            // Clean the output buffer until we get
            // to the level before rendering
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * Start the capturing of a block
     */
    public function define(string $block): void
    {
        if (!$this->rendering) {
            throw new RenderingException(sprintf('%s() is allowed only in rendering context', __METHOD__));
        }

        if ($block === 'content') {
            throw new RenderingException('The block "content" is reserved');
        }
        $this->incompleteBlocks[] = $block;
        ob_start();
    }

    /**
     * End the capturing of last block
     */
    public function end(): void
    {
        if (!$this->rendering) {
            throw new RenderingException(sprintf('%s() is allowed only in rendering context', __METHOD__));
        }
        if ($this->incompleteBlocks === []) {
            throw new RenderingException('There are no blocks to end');
        }
        $block = array_pop($this->incompleteBlocks);
        $this->blocks[$block] = ob_get_clean();
    }

    /**
     * Get the content of a given block
     */
    public function block(string $name): string
    {
        if (!$this->rendering) {
            throw new RenderingException(sprintf('%s() is allowed only in rendering context', __METHOD__));
        }
        if (!isset($this->blocks[$name])) {
            throw new RenderingException(sprintf('The block "%s" is undefined', $name));
        }
        return $this->blocks[$name];
    }

    /**
     * Get the layout content
     */
    public function content(): string
    {
        return $this->block('content');
    }

    /**
     * Get the view file
     */
    protected function getFile(string $name): string
    {
        if (Str::startsWith($name, '_')) {
            $name = 'partials/' . Str::removeStart($name, '_');
        }
        return FileSystem::joinPaths($this->path, str_replace('.', '/', $name) . '.php');
    }

    /**
     * Return the layout view instance
     */
    protected function createLayoutView(string $name, array $vars = []): View
    {
        return new self('layouts/' . $name, [...$this->vars, ...$vars], $this->path, $this->methods);
    }

    /**
     * Output the contents of the view
     */
    protected function output(): void
    {
        ob_start();

        $this->rendering = true;

        Renderer::load($this->file, $this->vars, $this);

        if (isset($this->layout)) {
            $this->layout->vars = $this->vars;
            $this->layout->blocks['content'] = ob_get_contents();
            ob_clean(); // Clean but don't end output buffer
            $this->layout->output();
        }

        $this->rendering = false;

        if ($this->incompleteBlocks !== []) {
            throw new RenderingException(sprintf('Incomplete blocks found: "%s". Use "$this->end()" to properly close them', implode('", "', $this->incompleteBlocks)));
        }

        ob_end_flush();
    }

    protected function callMethod($method, $arguments)
    {
        if (!$this->rendering && !$this->allowMethods) {
            throw new RenderingException(sprintf('%s::%s() is allowed only in rendering context', static::class, $method));
        }
        return $this->methods[$method](...$arguments);
    }
}
