<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\TransformCollection;
use GdImage;

interface HandlerInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(string $data, array $options = []);

    /**
     * Create an image handler from the given file path
     *
     * @param array<string, mixed> $options
     */
    public static function fromPath(string $path, array $options = []): HandlerInterface;

    /**
     * Create an image handler from a GD image
     *
     * @param array<string, mixed> $options
     */
    public static function fromGdImage(GdImage $gdImage, array $options = []): HandlerInterface;

    /**
     * Get image info as an array
     */
    public function getInfo(): ImageInfo;

    /**
     * Return whether the image handler supports transforms
     */
    public function supportsTransforms(): bool;

    /**
     * Return whether the image supports color profiles
     */
    public static function supportsColorProfile(): bool;

    /**
     * Return whether the image has a color profile
     */
    public function hasColorProfile(): bool;

    /**
     * Get color profile
     *
     * @throws UnsupportedFeatureException If the image does not support color profile
     */
    public function getColorProfile(): ?ColorProfile;

    /**
     * Set color profile
     *
     * @throws UnsupportedFeatureException If the image does not support color profile
     */
    public function setColorProfile(ColorProfile $colorProfile): void;

    /**
     * Remove color profile
     *
     * @throws UnsupportedFeatureException If the image does not support color profile
     */
    public function removeColorProfile(): void;

    /**
     * Return whether the image handler supports EXIF data
     */
    public static function supportsExifData(): bool;

    /**
     * Return whether the image has Exif data
     */
    public function hasExifData(): bool;

    /**
     * Get EXIF data
     *
     * @throws UnsupportedFeatureException If the image does not support EXIF data
     */
    public function getExifData(): ?ExifData;

    /**
     * Set EXIF data
     *
     * @throws UnsupportedFeatureException If the image does not support EXIF data
     */
    public function setExifData(ExifData $exifData): void;

    /**
     * Remove EXIF data
     *
     * @throws UnsupportedFeatureException If the image does not support EXIF data
     */
    public function removeExifData(): void;

    /**
     * Get image data
     */
    public function getData(): string;

    /**
     * Get image size in bytes
     */
    public function getSize(): int;

    /**
     * Save image to a different path
     */
    public function saveAs(string $path): void;

    /**
     * Get handler default options
     *
     * @return array<string, mixed>
     */
    public function defaults(): array;

    /**
     * Process image with optional transforms and different handler
     */
    public function process(?TransformCollection $transformCollection = null, ?string $handler = null): self;
}
