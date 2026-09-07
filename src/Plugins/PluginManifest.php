<?php

namespace Formwork\Plugins;

use Formwork\Data\Contracts\Arrayable;
use UnexpectedValueException;

/**
 * @since 2.3.0
 */
class PluginManifest implements Arrayable
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $author = null;

    protected ?string $homepage = null;

    protected ?string $license = null;

    protected ?string $version = null;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new UnexpectedValueException(sprintf('Invalid property "%s"', $key));
            }

            $this->{$key} = $value;
        }
    }

    /**
     * Get the plugin title
     */
    public function title(): ?string
    {
        return $this->title;
    }

    /**
     * Get the plugin description
     */
    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * Get the plugin author
     */
    public function author(): ?string
    {
        return $this->author;
    }

    /**
     * Get the plugin homepage
     */
    public function homepage(): ?string
    {
        return $this->homepage;
    }

    /**
     * Get the plugin license
     */
    public function license(): ?string
    {
        return $this->license;
    }

    /**
     * Get the plugin version
     */
    public function version(): ?string
    {
        return $this->version;
    }

    /**
     * Get the plugin default config
     *
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'author'      => $this->author,
            'homepage'    => $this->homepage,
            'license'     => $this->license,
            'version'     => $this->version,
            'config'      => $this->config,
        ];
    }
}
