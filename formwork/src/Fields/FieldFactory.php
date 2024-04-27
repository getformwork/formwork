<?php

namespace Formwork\Fields;

use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;

class FieldFactory
{
    public function __construct(protected Container $container, protected Config $config, protected Translations $translations)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function make(string $name, array $data = [], ?FieldCollection $parentFieldCollection = null): Field
    {
        $field = new Field($name, $data, $parentFieldCollection);

        $field->setTranslation($this->translations->getCurrent());

        $methods = FileSystem::joinPaths($this->config->get('system.fields.path'), $field->type() . '.php');

        if (FileSystem::exists($methods)) {
            $field->setMethods($this->container->call(require $methods));
        }

        return $field;
    }
}
