<?php
namespace Formwork\Admin\Fields;

use Formwork\Admin\Admin;
use Formwork\Core\Formwork;
use Formwork\Data\DataSetter;
use LogicException;

class Field extends DataSetter
{
    protected $name;

    protected $language;

    public function __construct($name, $data = array(), Fields $parent = null)
    {
        $this->name = $name;
        $this->language = Admin::instance()->language();
        parent::__construct($data);
        if ($this->has('import')) {
            $this->importData();
        }
        if ($this->has('fields')) {
            $this->data['fields'] = new Fields($this->data['fields']);
        }
    }

    public function get($key, $default = null)
    {
        $value = parent::get($key, $default);
        if ($this->translatable($key) && is_array($value)) {
            if (isset($value[$this->language])) {
                return $value[$this->language];
            }
        }
        return $value;
    }

    public function translatable($key)
    {
        $translate = parent::get('translate', true);
        if (is_array($translate)) {
            return in_array($key, $translate);
        }
        return $translate;
    }

    public function empty()
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

    public function default()
    {
        return $this->get('default');
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
            if ($key == 'import') {
                throw new LogicException('Invalid key for import');
            }
            $callback = explode('::', $value, 2);
            if (!is_callable($callback)) {
                throw new LogicException('Invalid import callback');
            }
            $this->data[$key] = call_user_func($callback);
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
