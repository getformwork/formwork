<?php
namespace Formwork\Admin\Fields;

use Formwork\Admin\AdminView;
use Formwork\Data\DataSetter;
use UnexpectedValueException;

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
    public function __construct(string $name, array $data = [], string $language = null)
    {
        $this->name = $name;
        parent::__construct($data);
        if ($this->has('import')) {
            $this->importData();
        }
        if ($this->has('fields')) {
            $this->data['fields'] = new Fields($this->data['fields'], $language);
        }
        Translator::translate($this, $language);
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
        return $this->get('value', $this->defaultValue());
    }

    /**
     * Get field default value
     */
    public function defaultValue()
    {
        return $this->get('default');
    }

    /**
     * Return whether field is empty
     */
    public function isEmpty(): bool
    {
        return in_array($this->value(), Validator::EMPTY_VALUES, true);
    }

    /**
     * Return whether field is required
     */
    public function isRequired(): bool
    {
        return $this->is('required');
    }

    /**
     * Return whether field is disabled
     */
    public function isDisabled(): bool
    {
        return $this->is('disabled');
    }

    /**
     * Return whether the field is visible
     */
    public function isVisible(): bool
    {
        return $this->is('visible', true);
    }

    /**
     * Get a value by key and return whether it is equal to boolean `true`
     */
    public function is(string $key, bool $default = false): bool
    {
        return $this->get($key, $default) === true;
    }

    /**
     * Render the field
     *
     * @param bool $return Whether to return or render the field
     */
    public function render(bool $return = false)
    {
        if ($this->isVisible()) {
            $view = new AdminView('fields.' . $this->type(), ['field' => $this]);
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
                throw new UnexpectedValueException('Invalid key for import');
            }
            $callback = explode('::', $value, 2);
            if (!is_callable($callback)) {
                throw new UnexpectedValueException('Invalid import callback "' . $value . '"');
            }
            $this->data[$key] = $callback();
        }
    }

    public function __debugInfo(): array
    {
        $return['name'] = $this->name;
        if ($this->has('type')) {
            $return['type'] = $this->type();
        }
        if ($this->has('default')) {
            $return['default'] = $this->defaultValue();
        }
        if ($this->has('value')) {
            $return['value'] = $this->value();
        }
        if ($this->has('fields')) {
            $return['fields'] = $this->get('fields');
        }
        return $return;
    }
}
