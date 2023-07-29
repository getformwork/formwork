<?php

namespace Formwork\Fields;

use Formwork\Config;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;

class FieldFactory
{
    public function __construct(protected Container $container, protected Config $config, protected Translations $translations)
    {

    }

    public function make(string $name, array $data = [], ?FieldCollection $parent = null): Field
    {
        $field = new Field($name, $data, $parent);

        $field->setTranslation($this->translations->getCurrent());

        $methods = FileSystem::joinPaths($this->config->get('system.fields.path'), $field->type() . '.php');

        if (FileSystem::exists($methods)) {
            $field->setMethods($this->container->call(require $methods));
        }

        return $field;
    }
}
