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

abstract class AbstractHandler
{
    protected string $data;

    protected DecoderInterface $decoder;

    protected array $options;

    public function __construct(string $data, array $options = [])
    {
        $this->data = $data;
        $this->decoder = $this->getDecoder();
        $this->options = [...$this->defaults(), ...$options];
    }

    public static function fromPath(string $path): AbstractHandler
    {
        return new static(FileSystem::read($path));
    }

    public static function fromGdImage(GdImage $image, array $options = []): AbstractHandler
    {
        $handler = new static('', $options);
        $handler->setDataFromGdImage($image);
        return $handler;
    }

    /**
     * Get image info as an array
     */
    abstract public function getInfo(): ImageInfo;

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
    abstract public function setColorProfile(ColorProfile $profile): void;

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
    abstract public function setExifData(ExifData $data): void;

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

    public function process(?TransformCollection $transforms = null, ?string $handler = null): AbstractHandler
    {
        $handler ??= $this::class;

        if (!is_subclass_of($handler, self::class)) {
            throw new UnexpectedValueException(sprintf('Invalid handler of type %s, only instances of %s are allowed', get_debug_type($handler), self::class));
        }

        if ($handler === $this::class && $transforms === null) {
            return $this;
        }

        $info = $this->getInfo();

        if ($this->options['preserveColorProfile'] && $this->hasColorProfile() && $handler::supportsColorProfile()) {
            $colorProfile = $this->getColorProfile();
        }

        if ($this->options['preserveExifData'] && $this->hasExifData() && $handler::supportsExifData()) {
            $ExifData = $this->getExifData();
        }

        $image = $this->toGdImage();

        if ($transforms !== null) {
            foreach ($transforms as $transform) {
                $image = $transform->apply($image, $info);
            }
        }

        if ($handler === $this::class) {
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

    abstract protected function setDataFromGdImage(GdImage $image): void;

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
