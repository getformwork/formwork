<?php

namespace Formwork\Fields;

use Closure;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;

final class FieldFactory
{
    public function __construct(
        private Container $container,
        private Config $config,
        private Translations $translations,
    ) {}

    /**
     * Create a new Field instance
     *
     * @param array<string, mixed> $data
     */
    public function make(string $name, array $data = [], ?FieldCollection $parentFieldCollection = null): Field
    {
        $field = new Field($name, $data, $parentFieldCollection);

        $field->setTranslation($this->translations->getCurrent());

        $config = $this->getFieldConfig($field->type(), []);

        $type = $field->type();

        $extend = $config['extend'] ?? $type;

        while ($extend !== $type) {
            $baseConfig = $this->getFieldConfig($extend);

            $type = $extend;
            $extend = $baseConfig['extend'] ?? $type;

            unset($baseConfig['extend']);

            $config = array_replace_recursive($baseConfig, $config);
        }

        $field->setMethods($config['methods'] ?? []);

        return $field;
    }

    /**
     * @param array{extend?: string, methods?: array<string, Closure>} $default
     *
     * @return array{extend?: string, methods?: array<string, Closure>}
     */
    private function getFieldConfig(string $type, ?array $default = null): array
    {
        $configPath = FileSystem::joinPaths($this->config->get('system.fields.path'), $type . '.php');

        if (!FileSystem::exists($configPath)) {
            if ($default !== null) {
                return $default;
            }
            throw new InvalidArgumentException(sprintf('Field type "%s" does not exist', $type));
        }

        return $this->container->call(require $configPath);
    }
}
