<?php

namespace Formwork\Images\Exif;

use Formwork\Data\Contracts\Arrayable;

class ExifData implements Arrayable
{
    protected ExifReader $reader;

    protected string $data;

    protected array $tags;

    public function __construct(string $data)
    {
        $this->reader = new ExifReader();
        $this->data = $data;
        $this->tags = $this->reader->read($this->data);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function toArray(): array
    {
        return iterator_to_array($this->parsedTags());
    }

    public function parsedTags()
    {
        foreach ($this->tags as $key => $value) {
            yield $key => $value[1] ?? $value[0];
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->tags);
    }

    public function hasMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    public function getRaw(string $key, $default = null)
    {
        return $this->has($key) ? $this->tags[$key][0] : $default;
    }

    public function get(string $key, $default = null)
    {
        return $this->has($key)
            ? $this->tags[$key][1] ?? $this->tags[$key][0]
            : $default;
    }
}
