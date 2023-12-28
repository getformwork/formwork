<?php

namespace Formwork\Images\Exif;

use Formwork\Data\Contracts\Arrayable;
use Generator;

class ExifData implements Arrayable
{
    protected ExifReader $reader;

    /**
     * @var array<string, mixed>
     */
    protected array $tags;

    public function __construct(protected string $data)
    {
        $this->reader = new ExifReader();
        $this->tags = $this->reader->read($this->data);
    }

    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function toArray(): array
    {
        return iterator_to_array($this->parsedTags());
    }

    /**
     * @return Generator<string, mixed>
     */
    public function parsedTags(): Generator
    {
        foreach ($this->tags as $key => $value) {
            yield $key => $value[1] ?? $value[0];
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->tags);
    }

    /**
     * @param list<string> $keys
     */
    public function hasMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    public function getRaw(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->tags[$key][0] : $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key)
            ? $this->tags[$key][1] ?? $this->tags[$key][0]
            : $default;
    }
}
