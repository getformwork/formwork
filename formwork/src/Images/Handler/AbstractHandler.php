<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\Decoder\DecoderInterface;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\ImageInfo;
use Formwork\Images\Transform\TransformCollection;
use Formwork\Utils\FileSystem;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

abstract class AbstractHandler implements HandlerInterface
{
    protected DecoderInterface $decoder;

    /**
     * @var array<string, mixed>
     */
    protected array $options;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(protected string $data, array $options = [])
    {
        $this->decoder = $this->getDecoder();
        $this->options = [...$this->defaults(), ...$options];
    }

    public static function fromPath(string $path): static
    {
        return new static(FileSystem::read($path));
    }

    public static function fromGdImage(GdImage $gdImage, array $options = []): static
    {
        $static = new static('', $options);
        $static->setDataFromGdImage($gdImage);
        return $static;
    }

    /**
     * Get image info as an array
     */
    abstract public function getInfo(): ImageInfo;

    abstract public function supportsTransforms(): bool;

    abstract public static function supportsColorProfile(): bool;

    /**
     * Return whether the image has a color profile
     */
    abstract public function hasColorProfile(): bool;

    /**
     * Get color profile
     *
     * @throws RuntimeException if the image has no color profile
     */
    abstract public function getColorProfile(): ?ColorProfile;

    /**
     * Set color profile
     *
     * @throws RuntimeException if the image has no color profile
     */
    abstract public function setColorProfile(ColorProfile $colorProfile): void;

    /**
     * Remove color profile
     *
     * @throws RuntimeException if the image has no color profile
     */
    abstract public function removeColorProfile(): void;

    abstract public static function supportsExifData(): bool;

    /**
     * Return whether the image has Exif data
     */
    abstract public function hasExifData(): bool;

    /**
     * Get EXIF data
     *
     * @throws RuntimeException if the image does not support EXIF data
     */
    abstract public function getExifData(): ?ExifData;

    /**
     * Set EXIF data
     *
     * @throws RuntimeException if the image does not support EXIF data
     */
    abstract public function setExifData(ExifData $exifData): void;

    /**
     * Remove EXIF data
     *
     * @throws RuntimeException if the image does not support EXIF data
     */
    abstract public function removeExifData(): void;

    public function getData(): string
    {
        return $this->data;
    }

    public function getSize(): int
    {
        return strlen($this->data);
    }

    /**
     * Save image in a different path
     */
    public function saveAs(string $path): void
    {
        FileSystem::write($path, $this->data);
    }

    public function defaults(): array
    {
        return [
            'jpegQuality'          => -1,
            'jpegProgressive'      => false,
            'pngCompression'       => -1,
            'webpQuality'          => -1,
            'gifColors'            => 256,
            'preserveColorProfile' => true,
            'preserveExifData'     => true,
        ];
    }

    public function process(?TransformCollection $transformCollection = null, ?string $handler = null): AbstractHandler
    {
        if (!$this->supportsTransforms()) {
            throw new RuntimeException(sprintf('Image handler of type %s does not support transforms for the current image', static::class));
        }

        $handler ??= static::class;

        if (!is_subclass_of($handler, self::class)) {
            throw new UnexpectedValueException(sprintf('Invalid handler of type %s, only instances of %s are allowed', get_debug_type($handler), self::class));
        }

        if ($handler === static::class && $transformCollection === null) {
            return $this;
        }

        $imageInfo = $this->getInfo();

        if ($this->options['preserveColorProfile'] && $this->hasColorProfile() && $handler::supportsColorProfile()) {
            $colorProfile = $this->getColorProfile();
        }

        if ($this->options['preserveExifData'] && $this->hasExifData() && $handler::supportsExifData()) {
            $ExifData = $this->getExifData();
        }

        $image = $this->toGdImage();

        if ($transformCollection !== null) {
            foreach ($transformCollection as $transform) {
                $image = $transform->apply($image, $imageInfo);
            }
        }

        if ($handler === static::class) {
            $this->setDataFromGdImage($image);
            $instance = $this;
        } else {
            /**
             * @var AbstractHandler
             */
            $instance = $handler::fromGdImage($image, $this->options);
        }

        if (isset($colorProfile)) {
            $instance->setColorProfile($colorProfile);
        }

        if (isset($ExifData)) {
            $instance->setExifData($ExifData);
        }

        return $instance;
    }

    /**
     * Get image decoder
     */
    abstract protected function getDecoder(): DecoderInterface;

    abstract protected function setDataFromGdImage(GdImage $gdImage): void;

    protected function toGdImage(): GdImage
    {
        $image = imagecreatefromstring($this->data);

        if ($this->getInfo()->hasAlphaChannel()) {
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagecolortransparent($image, $transparent);
            imagefill($image, 0, 0, $transparent);
        }

        return $image;
    }
}
