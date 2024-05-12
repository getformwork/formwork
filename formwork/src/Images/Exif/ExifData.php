<?php

namespace Formwork\Images\Exif;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Utils\Str;
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

    public function hasPositionData(): bool
    {
        return $this->hasMultiple(['GPSLatitude', 'GPSLongitude']);
    }

    public function dateTimeOriginal(): ?ExifDateTime
    {
        /** @var ExifDateTime|null */
        return $this->get('DateTimeOriginal');
    }

    public function makeAndModel(): ?string
    {
        $make = (string) $this->get('Make');
        $model = (string) $this->get('Model');

        if ($model === '') {
            return $make ?: null;
        }

        return $make . ' ' . Str::after($model, $make . ' ');
    }

    public function lensModel(): ?string
    {
        return $this->get('LensModel') ? str_replace('f/', 'ƒ/', (string) $this->get('LensModel')) : null;
    }

    public function focalLength(): ?string
    {
        return $this->get('FocalLength') ? $this->get('FocalLength') . ' mm' : null;
    }

    public function exposureTime(): ?string
    {
        return $this->get('ExposureTime') ? $this->get('ExposureTime') . ' s' : null;
    }

    public function aperture(): ?string
    {
        return $this->get('FNumber') ? 'ƒ/' . $this->get('FNumber') : null;
    }

    public function photographicSensitivity(): ?string
    {
        return $this->get('PhotographicSensitivity') ? 'ISO ' . $this->get('PhotographicSensitivity') : null;
    }

    public function exposureCompensation(): ?string
    {
        /** @var float|null */
        $compensation = $this->get('ExposureBiasValue');
        return $compensation ? round($compensation, 2) . ' EV' : null;
    }

    public function exposureProgram(): ?string
    {
        /** @var int */
        $exposureProgram = $this->getRaw('ExposureProgram', 0);

        if ($exposureProgram < 0) {
            return null;
        }

        return match ($exposureProgram) {
            2       => 'P',
            3       => 'A',
            4       => 'S',
            1       => 'M',
            default => 'AUTO',
        };
    }

    public function hasAutoWhiteBalance(): ?bool
    {
        return $this->has('WhiteBalance') ? $this->getRaw('WhiteBalance') === 0 : null;
    }

    public function hasFlashFired(): ?bool
    {
        return $this->has('Flash') ? (bool) ($this->getRaw('Flash') % 2) : null;
    }

    /**
     * @return 'average'|'evaluative'|'partial'|'spot'|null
     */
    public function meteringMode(): ?string
    {
        /** @var int|null */
        $meteringMode = $this->getRaw('MeteringMode');
        if ($meteringMode === null) {
            return null;
        }
        if ($meteringMode <= 2 || $meteringMode > 6) {
            return 'average';
        }
        if ($meteringMode === 3) {
            return 'spot';
        }
        if ($meteringMode === 4 || $meteringMode == 5) {
            return 'evaluative';
        }
        return 'partial';
    }

    public function colorSpace(): ?string
    {
        return $this->get('ColorSpace');
    }
}
