<?php

namespace Formwork\View;

use Formwork\Formwork;
use Formwork\Parsers\PHP;
use Formwork\Utils\FileSystem;
use BadMethodCallException;
use RuntimeException;

class View
{
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
     * Helper functions to be used in views
     */
    protected array $helpers = [];

    /**
     * Whether the view is being rendered
     */
    protected bool $rendering = false;

    /**
     * Create a new View instance
     */
    public function __construct(string $name, array $vars = [], string $path = null, array $helpers = [])
    {
        $this->name = $name;
        $this->vars = array_merge($this->defaults(), $vars);
        $this->path = $path ?? Formwork::instance()->config()->get('views.paths.system');
        $this->helpers = array_merge(PHP::parseFile(FORMWORK_PATH . 'helpers.php'), $helpers);
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
            throw new RuntimeException(sprintf('The layout for the %s "%s" is already set', static::TYPE, $this->name));
        }
        $this->layout = $this->createLayoutView($name);
    }

    /**
     * Insert a view
     */
    public function insert(string $name, array $vars = []): void
    {
        if (!$this->rendering) {
            throw new RuntimeException(sprintf('%s() is allowed only in rendering context', __METHOD__));
        }

        $file = $this->path . str_replace('.', DS, $name) . '.php';

        if (!FileSystem::exists($file)) {
            throw new RuntimeException(sprintf('%s "%s" not found', ucfirst(static::TYPE), $name));
        }

        Renderer::load($file, array_merge($this->vars, $vars), $this);
    }

    /**
     * Render the view
     */
    public function render(bool $return = false)
    {
        if ($this->rendering) {
            throw new RuntimeException(sprintf('%s() not allowed while rendering', __METHOD__));
        }

        ob_start();

        $this->rendering = true;

        $this->insert($this->name);

        if (isset($this->layout)) {
            $this->layout->vars = $this->vars;
            $this->layout->blocks['content'] = ob_get_contents();
            ob_clean(); // Clean but don't end output buffer
            $this->layout->render();
        }

        $this->rendering = false;

        if ($this->incompleteBlocks !== []) {
            throw new RuntimeException(sprintf('Incomplete blocks found: "%s". Use "$this->end()" to properly close them', implode('", "', $this->incompleteBlocks)));
        }

        if ($return) {
            return ob_get_clean();
        }
        ob_end_flush();
    }

    /**
     * Start the capturing of a block
     */
    public function define(string $block): void
    {
        if (!$this->rendering) {
            throw new RuntimeException(sprintf('%s() is allowed only in rendering context', __METHOD__));
        }

        if ($block === 'content') {
            throw new RuntimeException('The block "content" is reserved');
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
            throw new RuntimeException(sprintf('%s() is allowed only in rendering context', __METHOD__));
        }
        if ($this->incompleteBlocks === []) {
            throw new RuntimeException('There are no blocks to end');
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
            throw new RuntimeException(sprintf('%s() is allowed only in rendering context', __METHOD__));
        }
        if (!isset($this->blocks[$name])) {
            throw new RuntimeException(sprintf('The block "%s" is undefined', $name));
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
     * Return an array containing the default data
     */
    protected function defaults(): array
    {
        return [
            'formwork' => Formwork::instance(),
            'site'     => Formwork::instance()->site()
        ];
    }

    /**
     * Return the layout view instance
     */
    protected function createLayoutView(string $name): View
    {
        return new static('layouts' . DS . $name, $this->vars, $this->path, $this->helpers);
    }

    public function __call(string $name, array $arguments)
    {
        if ($this->rendering && isset($this->helpers[$name])) {
            return $this->helpers[$name](...$arguments);
        }
        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
    }
}
