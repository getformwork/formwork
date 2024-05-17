<?php

namespace Formwork\Panel\Modals;

use Formwork\Config\Config;
use Formwork\Fields\FieldFactory;
use Formwork\Parsers\Yaml;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;

class ModalFactory
{
    public function __construct(protected Container $container, protected Config $config, protected Translations $translations, protected FieldFactory $fieldFactory)
    {
    }

    public function make(string $id): Modal
    {
        $path = FileSystem::joinPaths($this->config->get('system.panel.paths.modals'), $id . '.yaml');

        $data = FileSystem::exists($path) ? Yaml::parseFile($path) : [];

        return new Modal($id, $data, $this->translations->getCurrent(), $this->fieldFactory);
    }
}
