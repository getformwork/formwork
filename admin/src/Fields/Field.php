<?php
namespace Formwork\Admin\Fields;

use Formwork\Data\DataSetter;
use LogicException;

class Field extends DataSetter
{
    protected $name;

    public function __construct($name, $data = array(), Fields $parent = null)
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

    public function isEmpty()
    {
        return empty($this->value());
    }

    public function name()
    {
        return $this->name;
    }

    public function type()
    {
        return $this->get('type');
    }

    public function label()
    {
        return $this->get('label', $this->name());
    }

    public function placeholder()
    {
        return $this->get('placeholder');
    }

    public function value()
    {
        return $this->get('value', $this->get('default'));
    }

    protected function importData()
    {
        foreach ((array) $this->data['import'] as $key => $value) {
            if ($key === 'import') {
                throw new LogicException('Invalid key for import');
            }
            $callback = explode('::', $value, 2);
            if (!is_callable($callback)) {
                throw new LogicException('Invalid import callback');
            }
            $this->data[$key] = $callback();
        }
    }

    public function __debugInfo()
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
