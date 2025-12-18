<?php

namespace Formwork\Images;

use Formwork\Files\File;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\AbstractHandler;
use Formwork\Images\Handler\GifHandler;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Images\Handler\PngHandler;
use Formwork\Images\Handler\SvgHandler;
use Formwork\Images\Handler\WebpHandler;
use Formwork\Images\Transform\Blur;
use Formwork\Images\Transform\BlurMode;
use Formwork\Images\Transform\Brightness;
use Formwork\Images\Transform\Colorize;
use Formwork\Images\Transform\Contrast;
use Formwork\Images\Transform\Crop;
use Formwork\Images\Transform\Desaturate;
use Formwork\Images\Transform\EdgeDetect;
use Formwork\Images\Transform\Emboss;
use Formwork\Images\Transform\Flip;
use Formwork\Images\Transform\FlipDirection;
use Formwork\Images\Transform\Invert;
use Formwork\Images\Transform\Pixelate;
use Formwork\Images\Transform\Resize;
use Formwork\Images\Transform\ResizeMode;
use Formwork\Images\Transform\Rotate;
use Formwork\Images\Transform\Scale;
use Formwork\Images\Transform\Sharpen;
use Formwork\Images\Transform\Smoothen;
use Formwork\Images\Transform\TransformCollection;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use RuntimeException;

class Image extends File
{
    public const string SCHEME_IDENTIFIER = 'files.image';

    protected const string MODEL_IDENTIFIER = 'image';

    #[ReadonlyModelProperty]
    protected AbstractHandler $handler;

    #[ReadonlyModelProperty]
    protected ImageInfo $info;

    #[ReadonlyModelProperty]
    protected TransformCollection $transforms;

    #[ReadonlyModelProperty]
    protected ?string $type = 'image';

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        string $path,
        protected array $options,
    ) {
        parent::__construct($path);
        $this->transforms = new TransformCollection();
    }

    public function path(): string
    {
        return $this->process()->path;
    }

    /**
     * Get image MIME type
     *
     * @throws RuntimeException If image info cannot be determined
     */
    public function mimeType(): string
    {
        if (!isset($this->mimeType)) {
            $info = getimagesize($this->path);

            if ($info !== false) {
                return $this->mimeType = $info['mime'];
            }

            $mimeTypeFromFile = MimeType::fromFile($this->path);

            if ($mimeTypeFromFile === 'image/svg+xml') {
                return $this->mimeType = $mimeTypeFromFile;
            }

            throw new RuntimeException('Failed to get image info');
        }

        return $this->mimeType;
    }

    /**
     * Rotate image by a given angle in degrees
     */
    public function rotate(float $angle): self
    {
        $this->transforms->add(new Rotate($angle));
        return $this;
    }

    /**
     * Flip image horizontally
     */
    public function flipHorizontal(): self
    {
        $this->transforms->add(new Flip(FlipDirection::Horizontal));
        return $this;
    }

    /**
     * Flip image vertically
     */
    public function flipVertical(): self
    {
        $this->transforms->add(new Flip(FlipDirection::Vertical));
        return $this;
    }

    /**
     * Flip image both horizontally and vertically
     */
    public function flipBoth(): self
    {
        $this->transforms->add(new Flip(FlipDirection::Both));
        return $this;
    }

    /**
     * Scale image by a given factor
     */
    public function scale(float $factor): self
    {
        $this->transforms->add(new Scale($factor));
        return $this;
    }

    /**
     * Resize image to a given width and height
     *
     * @param ?int              $width  Target width in pixels
     * @param ?int              $height Target height in pixels
     * @param ResizeMode|string $mode   Resize mode to use
     */
    public function resize(?int $width = null, ?int $height = null, ResizeMode|string $mode = ResizeMode::Cover): self
    {
        if (is_string($mode)) {
            $mode = ResizeMode::from($mode);
        }
        $this->transforms->add(new Resize($width, $height, $mode));
        return $this;
    }

    /**
     * Resize image to a square of a given size
     *
     * @param int|null          $size Target size in pixels (null for auto)
     * @param ResizeMode|string $mode Resize mode to use
     */
    public function square(?int $size = null, ResizeMode|string $mode = ResizeMode::Cover): self
    {
        $size ??= min($this->info()->width(), $this->info()->height());
        return $this->resize($size, $size, $mode);
    }

    /**
     * Crop image to a given width and height starting from a given origin
     *
     * @param int $originX Starting X coordinate
     * @param int $originY Starting Y coordinate
     * @param int $width   Width of the crop area
     * @param int $height  Height of the crop area
     */
    public function crop(int $originX, int $originY, int $width, int $height): self
    {
        $this->transforms->add(new Crop($originX, $originY, $width, $height));
        return $this;
    }

    /**
     * Blur image by a given amount using a given mode
     */
    public function blur(int $amount, BlurMode $blurMode = BlurMode::Mean): self
    {
        $this->transforms->add(new Blur($amount, $blurMode));
        return $this;
    }

    /**
     * Adjust image brightness by a given amount
     */
    public function brightness(int $amount): self
    {
        $this->transforms->add(new Brightness($amount));
        return $this;
    }

    /**
     * Colorize image with a given color
     */
    public function colorize(int $red, int $green, int $blue, int $alpha = 0): self
    {
        $this->transforms->add(new Colorize($red, $green, $blue, $alpha));
        return $this;
    }

    /**
     * Adjust image contrast by a given amount
     */
    public function contrast(int $amount): self
    {
        $this->transforms->add(new Contrast($amount));
        return $this;
    }

    /**
     * Desaturate image
     */
    public function desaturate(): self
    {
        $this->transforms->add(new Desaturate());
        return $this;
    }

    /**
     * Detect edges in the image
     */
    public function edgedetect(): self
    {
        $this->transforms->add(new EdgeDetect());
        return $this;
    }

    /**
     * Emboss image
     */
    public function emboss(): self
    {
        $this->transforms->add(new Emboss());
        return $this;
    }

    /**
     * Invert image colors
     */
    public function invert(): self
    {
        $this->transforms->add(new Invert());
        return $this;
    }

    /**
     * Pixelate image by a given amount
     */
    public function pixelate(int $amount): self
    {
        $this->transforms->add(new Pixelate($amount));
        return $this;
    }

    /**
     * Sharpen image
     */
    public function sharpen(): self
    {
        $this->transforms->add(new Sharpen());
        return $this;
    }

    /**
     * Smoothen image
     */
    public function smoothen(): self
    {
        $this->transforms->add(new Smoothen());
        return $this;
    }

    /**
     * Return whether the image has a color profile
     */
    public function hasColorProfile(): bool
    {
        return $this->handler()->hasColorProfile();
    }

    /**
     * Get color profile
     *
     * @throws RuntimeException If the image has no color profile
     */
    public function getColorProfile(): ?ColorProfile
    {
        return $this->handler()->getColorProfile();
    }

    /**
     * Set color profile
     *
     * @throws RuntimeException If the image has no color profile
     */
    public function setColorProfile(ColorProfile $colorProfile): void
    {
        $this->handler()->setColorProfile($colorProfile);
    }

    /**
     * Remove color profile
     *
     * @throws RuntimeException If the image has no color profile
     */
    public function removeColorProfile(): void
    {
        $this->handler()->removeColorProfile();
    }

    /**
     * Return whether the image has EXIF data
     */
    public function hasExifData(): bool
    {
        return $this->handler()->hasExifData();
    }

    /**
     * Get EXIF data
     *
     * @throws RuntimeException If the image does not support EXIF data
     */
    public function getExifData(): ?ExifData
    {
        return $this->handler()->getExifData();
    }

    /**
     * Set EXIF data
     *
     * @throws RuntimeException If the image does not support EXIF data
     */
    public function setExifData(ExifData $exifData): void
    {
        $this->handler()->setExifData($exifData);
    }

    /**
     * Remove EXIF data
     *
     * @throws RuntimeException If the image does not support EXIF data
     */
    public function removeExifData(): void
    {
        $this->handler()->removeExifData();
    }

    /**
     * Save image
     */
    public function save(): void
    {
        $this->saveAs($this->path);
    }

    /**
     * Perform image processing and return a new Image instance
     */
    public function process(?string $mimeType = null, bool $forceCache = false): Image
    {
        $mimeType ??= $this->mimeType();

        if (!$forceCache && $mimeType === $this->mimeType() && (!$this->handler()->supportsTransforms() || $this->transforms->isEmpty())) {
            return $this;
        }

        $dir = FileSystem::joinPaths($this->options['processPath'], $this->getHash($mimeType));

        if (!FileSystem::isDirectory($dir, assertExists: false)) {
            FileSystem::createDirectory($dir, recursive: true);
        }

        $path = FileSystem::joinPaths($dir, FileSystem::name($this->path) . '.' . MimeType::toExtension($mimeType));

        if (!FileSystem::exists($path)) {
            $this->saveAs($path, $mimeType);
        }

        $image = new Image($path, $this->options);
        $image->data = $this->data;
        $image->uriGenerator = $this->uriGenerator;

        if (isset($image->handler)) {
            $image->handler = $this->handler;
        }

        $this->transforms = new TransformCollection();
        unset($this->handler);

        return $image;
    }

    /**
     * Convert image to GIF
     */
    public function toGif(): Image
    {
        return $this->process('image/gif');
    }

    /**
     * Convert image to JPEG
     */
    public function toJpeg(): Image
    {
        return $this->process('image/jpeg');
    }

    /**
     * Convert image to PNG
     */
    public function toPng(): Image
    {
        return $this->process('image/png');
    }

    /**
     * Convert image to WebP
     */
    public function toWebp(): Image
    {
        return $this->process('image/webp');
    }

    /**
     * Save image to a given path with a given MIME type
     *
     * @throws RuntimeException If the image type is unsupported or conversion is not supported
     */
    public function saveAs(string $path, ?string $mimeType = null): void
    {
        $handler = match ($mimeType ?? $this->mimeType()) {
            'image/jpeg'    => JpegHandler::class,
            'image/png'     => PngHandler::class,
            'image/gif'     => GifHandler::class,
            'image/webp'    => WebpHandler::class,
            'image/svg+xml' => SvgHandler::class,
            default         => throw new RuntimeException(sprintf('Unsupported image type %s', $mimeType)),
        };

        if (!$this->handler()->supportsTransforms()) {
            if ($mimeType === $this->mimeType()) {
                $this->handler()->saveAs($path);
                return;
            }
            throw new RuntimeException(sprintf('Unsupported image conversion from %s to %s', $this->mimeType(), $mimeType));
        }

        $this->handler()->process($this->transforms, $handler)->saveAs($path);
    }

    /**
     * Get image info as an array
     */
    public function info(): ImageInfo
    {
        return $this->handler()->getInfo();
    }

    /**
     * Get image width. The width is computed after applying transforms (if any).
     * To get the original image width, use `info()->width()`.
     *
     * @since 2.1.0
     */
    public function width(): int
    {
        return $this->process()->info()->width();
    }

    /**
     * Get image height. The height is computed after applying transforms (if any).
     * To get the original image height, use `info()->height()`.
     *
     * @since 2.1.0
     */
    public function height(): int
    {
        return $this->process()->info()->height();
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'imageInfo' => $this->info()->toArray(),
            'uri'       => $this->uri(),
        ];
    }

    /**
     * Get image hash based on its path, transforms and format
     *
     * @throws RuntimeException If the image type is unsupported
     */
    protected function getHash(?string $mimeType = null): string
    {
        $mimeType ??= $this->mimeType();

        $format = match ($mimeType) {
            'image/jpeg'    => $mimeType . $this->options['jpegQuality'] . $this->options['jpegProgressive'] . $this->options['preserveColorProfile'] . $this->options['preserveExifData'],
            'image/png'     => $mimeType . $this->options['pngCompression'] . $this->options['preserveColorProfile'] . $this->options['preserveExifData'],
            'image/webp'    => $mimeType . $this->options['webpQuality'] . $this->options['preserveColorProfile'] . $this->options['preserveExifData'],
            'image/gif'     => $mimeType . $this->options['gifColors'],
            'image/svg+xml' => $mimeType,
            default         => throw new RuntimeException(sprintf('Unsupported image type %s', $mimeType)),
        };

        return substr(hash('sha256', $this->path . $this->transforms->getSpecifier() . $format . FileSystem::lastModifiedTime($this->path)), 0, 32);
    }

    /**
     * Get handler for the image
     */
    protected function handler(): AbstractHandler
    {
        if (!isset($this->handler)) {
            $this->handler = $this->getHandler();
        }
        return $this->handler;
    }

    /**
     * Get handler for the image according to its MIME type
     *
     * @throws RuntimeException If the image type is unsupported
     */
    protected function getHandler(): AbstractHandler
    {
        return match ($this->mimeType()) {
            'image/jpeg'    => JpegHandler::fromPath($this->path, $this->options),
            'image/png'     => PngHandler::fromPath($this->path, $this->options),
            'image/gif'     => GifHandler::fromPath($this->path, $this->options),
            'image/webp'    => WebpHandler::fromPath($this->path, $this->options),
            'image/svg+xml' => SvgHandler::fromPath($this->path, $this->options),
            default         => throw new RuntimeException('Unsupported image type'),
        };
    }

    /**
     * Initialize image
     *
     * @throws RuntimeException If `gd` extension is not loaded or image is not readable
     */
    protected function initialize(): void
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException(sprintf('Class %s requires the extension "gd" to be enabled', static::class));
        }

        if (!FileSystem::isReadable($this->path)) {
            throw new RuntimeException(sprintf('Image %s must be readable to be processed', $this->path));
        }
    }
}
