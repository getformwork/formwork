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

    public function __construct(
        protected string $data,
    ) {
        $this->reader = new ExifReader();
        $this->tags = $this->reader->read($this->data);
    }

    /**
     * Get raw EXIF data
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Get EXIF tags
     *
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
     * Get parsed EXIF tags
     *
     * @return Generator<string, mixed>
     */
    public function parsedTags(): Generator
    {
        foreach ($this->tags as $key => $value) {
            yield $key => $value[1] ?? $value[0];
        }
    }

    /**
     * Check if a tag exists
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->tags);
    }

    /**
     * Check if multiple tags exist
     *
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

    /**
     * Get raw value of a tag
     */
    public function getRaw(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->tags[$key][0] : $default;
    }

    /**
     * Get parsed value of a tag
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key)
            ? $this->tags[$key][1] ?? $this->tags[$key][0]
            : $default;
    }

    /**
     * Return whether the image has geolocation data
     */
    public function hasPositionData(): bool
    {
        return $this->hasMultiple(['GPSLatitude', 'GPSLongitude']);
    }

    /**
     * Get the original date and time of the image
     */
    public function dateTimeOriginal(): ?ExifDateTime
    {
        /** @var ExifDateTime|null */
        return $this->get('DateTimeOriginal');
    }

    /**
     * Get the make and model of the camera
     */
    public function makeAndModel(): ?string
    {
        $make = (string) $this->get('Make');
        $model = (string) $this->get('Model');

        if ($model === '') {
            return $make ?: null;
        }

        return $make . ' ' . Str::after($model, $make . ' ');
    }

    /**
     * Get the lens model
     */
    public function lensModel(): ?string
    {
        return $this->get('LensModel') ? str_replace('f/', 'ƒ/', (string) $this->get('LensModel')) : null;
    }

    /**
     * Get the lens focal length
     */
    public function focalLength(): ?string
    {
        return $this->get('FocalLength') ? $this->get('FocalLength') . ' mm' : null;
    }

    /**
     * Get the exposure time
     */
    public function exposureTime(): ?string
    {
        return $this->get('ExposureTime') ? "{$this->get('ExposureTime')} s" : null;
    }

    /**
     * Get the aperture
     */
    public function aperture(): ?string
    {
        return $this->get('FNumber') ? "ƒ/{$this->get('FNumber')}" : null;
    }

    /**
     * Get the ISO sensitivity
     */
    public function photographicSensitivity(): ?string
    {
        return $this->get('PhotographicSensitivity') ? "ISO {$this->get('PhotographicSensitivity')}" : null;
    }

    /**
     * Get the exposure compensation
     */
    public function exposureCompensation(): ?string
    {
        /** @var float|null */
        $compensation = $this->get('ExposureBiasValue');
        return $compensation ? round($compensation, 2) . ' EV' : null;
    }

    /**
     * Get the exposure program
     */
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

    /**
     * Get if the photo is captured with auto white balance
     */
    public function hasAutoWhiteBalance(): ?bool
    {
        return $this->has('WhiteBalance') ? $this->getRaw('WhiteBalance') === 0 : null;
    }

    /**
     * Get if the flash has fired
     */
    public function hasFlashFired(): ?bool
    {
        return $this->has('Flash') ? (bool) ($this->getRaw('Flash') % 2) : null;
    }

    /**
     * Get the metering mode
     *
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

    /**
     * Get the image color space
     */
    public function colorSpace(): ?string
    {
        return $this->get('ColorSpace');
    }
}
