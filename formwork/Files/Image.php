<?php

namespace Formwork\Files;

use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class Image extends File
{
    /**
     * Constant indicating the landscape orientation of an image
     *
     * @var string
     */
    public const ORIENTATION_LANDSCAPE = 'landscape';

    /**
     * Constant indicating the portrait orientation of an image
     *
     * @var string
     */
    public const ORIENTATION_PORTRAIT = 'portrait';

    /**
     * Constant indicating the 'cover' resize to fit mode
     *
     * @var string
     */
    public const RESIZE_FIT_COVER = 'cover';

    /**
     * Constant indicating the 'contain' resize to fit mode
     *
     * @var string
     */
    public const RESIZE_FIT_CONTAIN = 'contain';

    /**
     * Image formats supporting alpha
     *
     * @var array
     */
    protected const FORMATS_SUPPORTING_ALPHA = ['image/gif', 'image/png', 'image/webp'];

    /**
     * Array containing image information
     *
     * @var array
     */
    protected $info = [];

    /**
     * Image width
     *
     * @var int
     */
    protected $width;

    /**
     * Image height
     *
     * @var int
     */
    protected $height;

    /**
     * Image resource
     *
     * @var resource
     */
    protected $image;

    /**
     * Unmodified image resource
     *
     * @var resource
     */
    protected $sourceImage;

    /**
     * JPEG export quality (0-100)
     *
     * @var int
     */
    protected $JPEGQuality = 85;

    /**
     * Whether to save JPEG images as progressive
     *
     * @var bool
     */
    protected $JPEGSaveProgressive = true;

    /**
     * PNG compression level 0-9
     *
     * @var int
     */
    protected $PNGCompression = 9;

    /**
     * WEBP export quality (0-100)
     *
     * @var int
     */
    protected $WEBPQuality = 85;

    /**
     * Return image width
     */
    public function width(): int
    {
        if ($this->image === null) {
            $this->initialize();
        }
        return $this->width;
    }

    /**
     * Return image height
     */
    public function height(): int
    {
        if ($this->image === null) {
            $this->initialize();
        }
        return $this->height;
    }

    /**
     * Return image orientation
     */
    public function orientation(): string
    {
        if ($this->image === null) {
            $this->initialize();
        }
        if ($this->width >= $this->height) {
            return self::ORIENTATION_LANDSCAPE;
        }
        return self::ORIENTATION_PORTRAIT;
    }

    /**
     * Rotate image
     */
    public function rotate(int $angle): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        $backgroundColor = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        $this->image = imagerotate($this->image, $angle, $backgroundColor);
        return $this;
    }

    /**
     * Flip image horizontally
     */
    public function flipHorizontal(): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imageflip($this->image, IMG_FLIP_HORIZONTAL);
        return $this;
    }

    /**
     * Flip image vertically
     */
    public function flipVertical(): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imageflip($this->image, IMG_FLIP_VERTICAL);
        return $this;
    }

    /**
     * Flip image horizontally and vertically
     */
    public function flipBoth(): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imageflip($this->image, IMG_FLIP_BOTH);
        return $this;
    }

    /**
     * Resize image to a given width and height. If an argument is null its value will be chosen
     * preserving the aspect ratio
     */
    public function resize(int $destinationWidth = null, int $destinationHeight = null): self
    {
        if ($destinationWidth === null && $destinationHeight === null) {
            throw new InvalidArgumentException(__METHOD__ . ' must be called with at least one of $destinationWidth or $destinationHeight arguments');
        }

        if ($this->image === null) {
            $this->initialize();
        }

        $sourceWidth = $this->width;
        $sourceHeight = $this->height;

        $sourceRatio = $sourceWidth / $sourceHeight;

        if ($destinationWidth === null) {
            $destinationWidth = $destinationHeight * $sourceRatio;
        } elseif ($destinationHeight === null) {
            $destinationHeight = $destinationWidth / $sourceRatio;
        }

        $destinationImage = imagecreatetruecolor($destinationWidth, $destinationHeight);

        if (in_array($this->info['mime'], self::FORMATS_SUPPORTING_ALPHA, true)) {
            $this->enableTransparency($destinationImage);
        }

        imagecopyresampled(
            $destinationImage,
            $this->image,
            0,
            0,
            0,
            0,
            $destinationWidth,
            $destinationHeight,
            $sourceWidth,
            $sourceHeight
        );

        $this->image = $destinationImage;
        return $this;
    }

    /**
     * Scale image by a factor
     */
    public function scale(float $factor): self
    {
        return $this->resize($factor * $this->width, $factor * $this->height);
    }

    /**
     * Resize image keeping its aspect ratio
     *
     * @param string $mode self::RESIZE_FIT_COVER (default) or self::RESIZE_FIT_CONTAIN
     */
    public function resizeToFit(int $destinationWidth, int $destinationHeight, string $mode = self::RESIZE_FIT_COVER): self
    {
        if ($this->image === null) {
            $this->initialize();
        }

        $sourceWidth = $this->width;
        $sourceHeight = $this->height;

        $cropAreaWidth = $sourceWidth;
        $cropAreaHeight = $sourceHeight;

        $cropOriginX = 0;
        $cropOriginY = 0;

        $sourceRatio = $sourceWidth / $sourceHeight;
        $destinationRatio = $destinationWidth / $destinationHeight;

        if (($mode === self::RESIZE_FIT_COVER && $sourceRatio > $destinationRatio)
            || ($mode === self::RESIZE_FIT_CONTAIN && $sourceRatio < $destinationRatio)) {
            $cropAreaWidth = $sourceHeight * $destinationRatio;
            $cropOriginX = ($sourceWidth - $cropAreaWidth) / 2;
        } else {
            $cropAreaHeight = $sourceWidth / $destinationRatio;
            $cropOriginY = ($sourceHeight - $cropAreaHeight) / 2;
        }

        $destinationImage = imagecreatetruecolor($destinationWidth, $destinationHeight);

        if (in_array($this->info['mime'], self::FORMATS_SUPPORTING_ALPHA, true)) {
            $this->enableTransparency($destinationImage);
        }

        imagecopyresampled(
            $destinationImage,
            $this->image,
            0,
            0,
            $cropOriginX,
            $cropOriginY,
            $destinationWidth,
            $destinationHeight,
            $cropAreaWidth,
            $cropAreaHeight
        );

        $this->image = $destinationImage;

        return $this;
    }

    /**
     * Square image to a given size
     *
     * @param string $mode self::RESIZE_FIT_COVER (default) or self::RESIZE_FIT_CONTAIN
     */
    public function square(int $size, string $mode = self::RESIZE_FIT_COVER): self
    {
        return $this->resizeToFit($size, $size, $mode);
    }

    /**
     * Crop image to given size from origin coordinates
     */
    public function crop(int $originX, int $originY, int $width, int $height): self
    {
        if ($this->image === null) {
            $this->initialize();
        }

        $destinationImage = imagecreatetruecolor($width, $height);

        if (in_array($this->info['mime'], self::FORMATS_SUPPORTING_ALPHA, true)) {
            $this->enableTransparency($destinationImage);
        }

        imagecopy(
            $destinationImage,
            $this->image,
            0,
            0,
            $originX,
            $originY,
            $width,
            $height
        );

        $this->image = $destinationImage;

        return $this;
    }

    /**
     * Desaturate image
     */
    public function desaturate(): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * Invert image colors
     */
    public function invert(): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_NEGATE);
        return $this;
    }

    /**
     * Increase or decrease image brightness
     *
     * @param int $amount Amount of brightness from -255 to 255
     */
    public function brightness(int $amount): self
    {
        if ($amount < -255 || $amount > 255) {
            throw new InvalidArgumentException('$amount value must be in range -255-+255, ' . $amount . ' given');
        }
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $amount);
        return $this;
    }

    /**
     * Increase or decrease image contrast
     *
     * @param int $amount Amount of contrast from -100 to 100
     */
    public function contrast(int $amount): self
    {
        if ($amount < -100 || $amount > 100) {
            throw new InvalidArgumentException('$amount value must be in range -100-+100, ' . $amount . ' given');
        }
        if ($this->image === null) {
            $this->initialize();
        }
        // For GD -100 = max contrast, 100 = min contrast; we change $amount sign for a more predictable behavior
        imagefilter($this->image, IMG_FILTER_CONTRAST, -$amount);
        return $this;
    }

    /**
     * Colorize image with given RGBA values
     *
     * @param int $red   Red value from 0 to 255
     * @param int $green Green value from 0 to 255
     * @param int $blue  Blue value from 0 to 255
     * @param int $alpha Alpha value from 0 (opaque) to 127 (transparent)
     */
    public function colorize(int $red, int $green, int $blue, int $alpha = 0): self
    {
        if ($red < 0 || $red > 255) {
            throw new InvalidArgumentException('$red value must be in range 0-255, ' . $red . ' given');
        }
        if ($green < 0 || $green > 255) {
            throw new InvalidArgumentException('$green value must be in range 0-255, ' . $green . ' given');
        }
        if ($blue < 0 || $blue > 255) {
            throw new InvalidArgumentException('$blue value must be in range 0-255, ' . $blue . ' given');
        }
        if ($alpha < 0 || $alpha > 127) {
            throw new InvalidArgumentException('$alpha value must be in range 0-127, ' . $alpha . ' given');
        }
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_COLORIZE, $red, $green, $blue, $alpha);
        return $this;
    }

    /**
     * Apply sepia effect to image
     */
    public function sepia(): self
    {
        return $this->desaturate()->colorize(76, 48, 0);
    }

    /**
     * Apply edge detect effect to image
     */
    public function edgedetect(): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_EDGEDETECT);
        return $this;
    }

    /**
     * Apply emboss effect to image
     */
    public function emboss(): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_EMBOSS);
        return $this;
    }

    /**
     * Blur image
     *
     * @param int $amount Amount of blur from 0 to 100
     */
    public function blur(int $amount): self
    {
        if ($amount < 0 || $amount > 100) {
            throw new InvalidArgumentException('$amount value must be in range 0-100, ' . $amount . ' given');
        }
        if ($this->image === null) {
            $this->initialize();
        }
        for ($i = 0; $i < $amount; $i++) {
            imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
        }
        return $this;
    }

    /**
     * Sharpen image
     */
    public function sharpen(): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
        return $this;
    }

    /**
     * Smoothen image
     */
    public function smoothen(int $amount): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_SMOOTH, $amount);
        return $this;
    }

    /**
     * Pixelate image
     */
    public function pixelate(int $amount): self
    {
        if ($this->image === null) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_PIXELATE, $amount);
        return $this;
    }

    /**
     * Save image
     *
     * @param string $filename
     * @param bool   $destroy  Whether to destroy image after saving
     */
    public function save(string $filename = null, bool $destroy = true): void
    {
        if ($this->image === null) {
            $this->initialize();
        }

        if ($filename === null) {
            $filename = $this->path;
        }

        $extension = strtolower(FileSystem::extension($filename));
        $mimeType = MimeType::fromExtension($extension);

        switch ($mimeType) {
            case 'image/jpeg':
                imageinterlace($this->image, $this->JPEGSaveProgressive);
                imagejpeg($this->image, $filename, $this->JPEGQuality);
                break;
            case 'image/png':
                imagepng($this->image, $filename, $this->PNGCompression);
                break;
            case 'image/gif':
                imagegif($this->image, $filename);
                break;
            case 'image/webp':
                imagewebp($this->image, $filename, $this->WEBPQuality);
                break;
            default:
                throw new RuntimeException('Unknown image MIME type for .' . $filename . ' extension');
                break;
        }

        if ($destroy) {
            $this->destroy();
        }
    }

    /**
     * Save image with current quality/compression only if its size is smaller than the original
     *
     * @param string $filename
     * @param bool   $destroy  Whether to destroy image after saving
     */
    public function saveOptimized(string $filename = null, bool $destroy = true): void
    {
        if ($this->image === null) {
            $this->initialize();
        }

        if ($filename === null) {
            $filename = $this->path;
        }

        $tempFilename = FileSystem::joinPaths(dirname($filename), '.tmp-' . basename($filename));

        $this->save($tempFilename, false);

        $tempFilesize = FileSystem::size($tempFilename, false);
        $sourceFilesize = FileSystem::size($this->path, false);

        if ($tempFilesize < $sourceFilesize) {
            FileSystem::move($tempFilename, $this->path, true);
        } else {
            FileSystem::delete($tempFilename);
        }

        if ($destroy) {
            $this->destroy();
        }
    }

    /**
     * Delete image resources from memory
     */
    public function destroy(): void
    {
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
        if (is_resource($this->sourceImage)) {
            imagedestroy($this->sourceImage);
        }
    }

    /**
     * Initialize Image object
     */
    protected function initialize(): void
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD extension not loaded');
        }

        if (!FileSystem::isReadable($this->path)) {
            throw new RuntimeException('Image ' . $this->path . ' must be readable to be processed');
        }

        $this->JPEGQuality = Formwork::instance()->option('images.jpeg_quality');

        if ($this->JPEGQuality < 0 || $this->JPEGQuality > 100) {
            throw new UnexpectedValueException('JPEG quality must be in the range 0-100, ' . $this->JPEGQuality . ' given');
        }

        $this->JPEGSaveProgressive = Formwork::instance()->option('images.jpeg_progressive');

        $this->PNGCompression = Formwork::instance()->option('images.png_compression');

        if ($this->PNGCompression < 0 || $this->PNGCompression > 9) {
            throw new UnexpectedValueException('PNG compression level must be in range 0-9, ' . $this->PNGCompression . ' given');
        }

        $this->WEBPQuality = Formwork::instance()->option('images.webp_quality');

        if ($this->WEBPQuality < 0 || $this->WEBPQuality > 100) {
            throw new UnexpectedValueException('WebP quality must be in the range 0-100, ' . $this->WEBPQuality . ' given');
        }

        $this->info = getimagesize($this->path);

        if (!$this->info) {
            throw new RuntimeException('Cannot load image ' . $this->path);
        }

        switch ($this->info['mime']) {
            case 'image/jpeg':
                $this->image = imagecreatefromjpeg($this->path);
                break;
            case 'image/png':
                $this->image = imagecreatefrompng($this->path);
                $this->enableTransparency($this->image);
                break;
            case 'image/gif':
                $this->image = imagecreatefromgif($this->path);
                imagepalettetotruecolor($this->image);
                $this->enableTransparency($this->image);
                break;
            case 'image/webp':
                $this->image = imagecreatefromwebp($this->path);
                $this->enableTransparency($this->image);
                break;
            default:
                throw new RuntimeException('Unsupported image MIME type');
                break;
        }

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
        $this->sourceImage = $this->image;
    }

    /**
     * Enable transparency for PNG and GIF images
     *
     * @param resource $image
     */
    protected function enableTransparency($image): void
    {
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagecolortransparent($image, $transparent);
        imagefill($image, 0, 0, $transparent);
    }

    public function __destruct()
    {
        $this->destroy();
    }
}
