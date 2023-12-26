<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\TransformCollection;
use GdImage;
use RuntimeException;

interface HandlerInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(string $data, array $options = []);

    public static function fromPath(string $path): HandlerInterface;

    /**
     * @param array<string, mixed> $options
     */
    public static function fromGdImage(GdImage $image, array $options = []): HandlerInterface;

    /**
     * Get image info as an array
     */
    public function getInfo(): ImageInfo;

    public static function supportsColorProfile(): bool;

    /**
     * Return whether the image has a color profile
     */
    public function hasColorProfile(): bool;

    /**
     * Get color profile
     *
     * @throws RuntimeException if the image has no color profile
     */
    public function getColorProfile(): ?ColorProfile;

    /**
     * Set color profile
     *
     * @throws RuntimeException if the image has no color profile
     */
    public function setColorProfile(ColorProfile $profile): void;

    /**
     * Remove color profile
     *
     * @throws RuntimeException if the image has no color profile
     */
    public function removeColorProfile(): void;

    public static function supportsExifData(): bool;

    /**
     * Return whether the image has Exif data
     */
    public function hasExifData(): bool;

    /**
     * Get EXIF data
     *
     * @throws RuntimeException if the image does not support EXIF data
     */
    public function getExifData(): ?ExifData;

    /**
     * Set EXIF data
     *
     * @throws RuntimeException if the image does not support EXIF data
     */
    public function setExifData(ExifData $data): void;

    /**
     * Remove EXIF data
     *
     * @throws RuntimeException if the image does not support EXIF data
     */
    public function removeExifData(): void;

    public function getData(): string;

    public function getSize(): int;

    /**
     * Save image in a different path
     */
    public function saveAs(string $path): void;

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array;

    public function process(?TransformCollection $transforms = null, ?string $handler = null): self;
}
