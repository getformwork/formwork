<?php
namespace Formwork\Admin\Fields;

use Formwork\Admin\View\View;
use Formwork\Data\DataSetter;
use LogicException;

class Field extends DataSetter
{
    /**
     * Field name
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new Field instance
     */
    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        parent::__construct($data);
        if ($this->has('import')) {
            $this->importData();
        }
        if ($this->has('fields')) {
            $this->data['fields'] = new Fields($this->data['fields']);
        }
        Translator::translate($this);
    }

    /**
     * Return whether field is empty
     */
    public function isEmpty(): bool
    {
        return $this->value() === null || $this->value() === '' || $this->value() === [];
    }

    /**
     * Get field name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Return field name with correct syntax to be used in forms
     */
    public function formName(): string
    {
        $segments = explode('.', $this->name);
        $formName = array_shift($segments);
        foreach ($segments as $segment) {
            $formName .= '[' . $segment . ']';
        }
        return $formName;
    }

    /**
     * Get field type
     */
    public function type(): string
    {
        return $this->get('type');
    }

    /**
     * Get field label
     */
    public function label(): string
    {
        return $this->get('label', $this->name());
    }

    /**
     * Get field placeholder label
     */
    public function placeholder(): ?string
    {
        return $this->get('placeholder');
    }

    /**
     * Get field value
     */
    public function value()
    {
        return $this->get('value', $this->get('default'));
    }

    /**
     * Return whether the field is visible
     */
    public function isVisible(): bool
    {
        return $this->get('visible', true) === true;
    }

    /**
     * Render the field
     *
     * @param bool $return Whether to return or render the field
     */
    public function render(bool $return = false)
    {
        if ($this->isVisible()) {
            $view = new View('fields.' . $this->type(), ['field' => $this]);
            return $view->render($return);
        }
    }

    /**
     * Import data helper
     */
    protected function importData(): void
    {
        foreach ((array) $this->data['import'] as $key => $value) {
            if ($key === 'import') {
                throw new LogicException('Invalid key for import');
            }
            $callback = explode('::', $value, 2);
            if (!is_callable($callback)) {
                throw new LogicException('Invalid import callback "' . $value . '"');
            }
            $this->data[$key] = $callback();
        }
    }

    public function __debugInfo(): array
    {
        $return['name'] = $this->name;
        if ($this->has('type')) {
            $return['type'] = $this->get('type');
        }
        if ($this->has('default')) {
            $return['default'] = $this->get('default');
        }
        if ($this->has('value')) {
            $return['value'] = $this->get('value');
        }
        if ($this->has('fields')) {
            $return['fields'] = $this->get('fields');
        }
        return $return;
    }
}
