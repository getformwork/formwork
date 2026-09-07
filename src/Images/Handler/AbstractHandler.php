<?php

namespace Formwork\Images\Handler;

use Formwork\Images\Decoder\DecoderInterface;
use Formwork\Images\Transform\TransformCollection;
use Formwork\Utils\FileSystem;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

abstract class AbstractHandler implements HandlerInterface
{
    protected DecoderInterface $decoder;

    /**
     * Handler options
     *
     * @var array<string, mixed>
     */
    protected array $options;

    public function __construct(
        protected string $data,
        array $options = [],
    ) {
        $this->decoder = $this->getDecoder();
        $this->options = [...$this->defaults(), ...$options];
    }

    public static function fromPath(string $path, array $options = []): static
    {
        // @phpstan-ignore new.staticInAbstractClassStaticMethod
        return new static(FileSystem::read($path), $options);
    }

    public static function fromGdImage(GdImage $gdImage, array $options = []): static
    {
        // @phpstan-ignore new.staticInAbstractClassStaticMethod
        $static = new static('', $options);
        $static->setDataFromGdImage($gdImage);
        return $static;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getSize(): int
    {
        return strlen($this->data);
    }

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
            'avifQuality'          => -1,
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

    /**
     * Set image data from a GD image
     */
    abstract protected function setDataFromGdImage(GdImage $gdImage): void;

    /**
     * Get image as a GD image
     */
    protected function toGdImage(): GdImage
    {
        if (($image = @imagecreatefromstring($this->data)) === false) {
            throw new UnexpectedValueException('Invalid image data');
        }

        return $image;
    }
}
