<?php

namespace Formwork\Panel\Modals;

use Formwork\Config\Config;
use Formwork\Fields\FieldFactory;
use Formwork\Parsers\Yaml;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;

final class ModalFactory
{
    public function __construct(
        private Config $config,
        private Translations $translations,
        private FieldFactory $fieldFactory,
    ) {}

    /**
     * Create a new Modal instance
     */
    public function make(string $id): Modal
    {
        $path = FileSystem::joinPaths($this->config->getString('system.panel.paths.modals'), $id . '.yaml');

        $data = FileSystem::exists($path) ? Yaml::parseFile($path) : [];

        return new Modal($id, $data, $this->translations->getCurrent(), $this->fieldFactory);
    }
}
