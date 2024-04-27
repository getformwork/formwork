<?php

namespace Formwork\Images;

use Formwork\Files\File;
use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\AbstractHandler;
use Formwork\Images\Handler\GifHandler;
use Formwork\Images\Handler\JpegHandler;
use Formwork\Images\Handler\PngHandler;
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
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use Formwork\Utils\Uri;
use RuntimeException;

class Image extends File
{
    protected string $path;

    protected AbstractHandler $handler;

    protected ImageInfo $info;

    protected TransformCollection $transforms;

    protected string $mimeType;

    protected ?string $type = 'image';

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(string $path, protected array $options)
    {
        parent::__construct($path);
        $this->transforms = new TransformCollection();
    }

    public function path(): string
    {
        return $this->process()->path;
    }

    public function absoluteUri(): string
    {
        return Uri::resolveRelative($this->uri());
    }

    public function mimeType(): string
    {
        if (!isset($this->mimeType)) {
            $info = getimagesize($this->path);

            if ($info === false) {
                throw new RuntimeException('Failed to get image info');
            }

            $this->mimeType = $info['mime'];
        }

        return $this->mimeType;
    }

    public function rotate(float $angle): self
    {
        $this->transforms->add(new Rotate($angle));
        return $this;
    }

    public function flipHorizontal(): self
    {
        $this->transforms->add(new Flip(FlipDirection::Horizontal));
        return $this;
    }

    public function flipVertical(): self
    {
        $this->transforms->add(new Flip(FlipDirection::Vertical));
        return $this;
    }

    public function flipBoth(): self
    {
        $this->transforms->add(new Flip(FlipDirection::Both));
        return $this;
    }

    public function scale(float $factor): self
    {
        $this->transforms->add(new Scale($factor));
        return $this;
    }

    public function resize(int $width, int $height, ResizeMode|string $mode = ResizeMode::Cover): self
    {
        if (is_string($mode)) {
            $mode = ResizeMode::from($mode);
        }
        $this->transforms->add(new Resize($width, $height, $mode));
        return $this;
    }

    public function square(?int $size = null, ResizeMode|string $mode = ResizeMode::Cover): self
    {
        $size ??= min($this->info()->width(), $this->info()->height());
        return $this->resize($size, $size, $mode);
    }

    public function crop(int $originX, int $originY, int $width, int $height): self
    {
        $this->transforms->add(new Crop($originX, $originY, $width, $height));
        return $this;
    }

    public function blur(int $amount, BlurMode $blurMode = BlurMode::Mean): self
    {
        $this->transforms->add(new Blur($amount, $blurMode));
        return $this;
    }

    public function brightness(int $amount): self
    {
        $this->transforms->add(new Brightness($amount));
        return $this;
    }

    public function colorize(int $red, int $green, int $blue, int $alpha = 0): self
    {
        $this->transforms->add(new Colorize($red, $green, $blue, $alpha));
        return $this;
    }

    public function contrast(int $amount): self
    {
        $this->transforms->add(new Contrast($amount));
        return $this;
    }

    public function desaturate(): self
    {
        $this->transforms->add(new Desaturate());
        return $this;
    }

    public function edgedetect(): self
    {
        $this->transforms->add(new EdgeDetect());
        return $this;
    }

    public function emboss(): self
    {
        $this->transforms->add(new Emboss());
        return $this;
    }

    public function invert(): self
    {
        $this->transforms->add(new Invert());
        return $this;
    }

    public function pixelate(int $amount): self
    {
        $this->transforms->add(new Pixelate($amount));
        return $this;
    }

    public function sharpen(): self
    {
        $this->transforms->add(new Sharpen());
        return $this;
    }

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
     * @throws RuntimeException if the image has no color profile
     */
    public function getColorProfile(): ?ColorProfile
    {
        return $this->handler()->getColorProfile();
    }

    /**
     * Set color profile
     *
     * @throws RuntimeException if the image has no color profile
     */
    public function setColorProfile(ColorProfile $colorProfile): void
    {
        $this->handler()->setColorProfile($colorProfile);
    }

    /**
     * Remove color profile
     *
     * @throws RuntimeException if the image has no color profile
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
     * @throws RuntimeException if the image does not support EXIF data
     */
    public function getExifData(): ?ExifData
    {
        return $this->handler()->getExifData();
    }

    /**
     * Set EXIF data
     *
     * @throws RuntimeException if the image does not support EXIF data
     */
    public function setExifData(ExifData $exifData): void
    {
        $this->handler()->setExifData($exifData);
    }

    /**
     * Remove EXIF data
     *
     * @throws RuntimeException if the image does not support EXIF data
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

    public function process(?string $mimeType = null, bool $forceCache = false): Image
    {
        $mimeType ??= $this->mimeType();

        if (!$forceCache && $mimeType === $this->mimeType() && $this->transforms->isEmpty()) {
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
        $image->uriGenerator = $this->uriGenerator;
        return $image;
    }

    public function toGif(): Image
    {
        return $this->process('image/gif');
    }

    public function toJpeg(): Image
    {
        return $this->process('image/jpeg');
    }

    public function toPng(): Image
    {
        return $this->process('image/png');
    }

    public function toWebp(): Image
    {
        return $this->process('image/webp');
    }

    public function saveAs(string $path, ?string $mimeType = null): void
    {
        $handler = match ($mimeType ?? $this->mimeType()) {
            'image/jpeg' => JpegHandler::class,
            'image/png'  => PngHandler::class,
            'image/gif'  => GifHandler::class,
            'image/webp' => WebpHandler::class,
            default      => throw new RuntimeException(sprintf('Unsupported image type %s', $mimeType))
        };

        $this->handler()->process($this->transforms, $handler)->saveAs($path);
    }

    /**
     * Get image info as an array
     */
    public function info(): ImageInfo
    {
        return $this->handler()->getInfo();
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'imageInfo'    => $this->info()->toArray(),
            'exif'         => $this->getExifData()?->toArray(),
            'colorProfile' => $this->getColorProfile()?->name(),
            'uri'          => $this->uri(),
        ];
    }

    protected function getHash(?string $mimeType = null): string
    {
        $mimeType ??= $this->mimeType();

        $format = match ($mimeType) {
            'image/jpeg' => $mimeType . $this->options['jpegQuality'] . $this->options['jpegProgressive'] . $this->options['preserveColorProfile'] . $this->options['preserveExifData'],
            'image/png'  => $mimeType . $this->options['pngCompression'] . $this->options['preserveColorProfile'] . $this->options['preserveExifData'],
            'image/webp' => $mimeType . $this->options['webpQuality'] . $this->options['preserveColorProfile'] . $this->options['preserveExifData'],
            'image/gif'  => $mimeType . $this->options['gifColors'],
            default      => throw new RuntimeException(sprintf('Unsupported image type %s', $mimeType))
        };

        return substr(hash('sha256', $this->path . $this->transforms->getSpecifier() . $format . FileSystem::lastModifiedTime($this->path)), 0, 32);
    }

    protected function handler(): AbstractHandler
    {
        if (!isset($this->handler)) {
            $this->handler = $this->getHandler();
        }
        return $this->handler;
    }

    /**
     * Get handler for the image according to its MIME type
     */
    protected function getHandler(): AbstractHandler
    {
        return match ($this->mimeType()) {
            'image/jpeg' => JpegHandler::fromPath($this->path),
            'image/png'  => PngHandler::fromPath($this->path),
            'image/gif'  => GifHandler::fromPath($this->path),
            'image/webp' => WebpHandler::fromPath($this->path),
            default      => throw new RuntimeException('Unsupported image type'),
        };
    }

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
