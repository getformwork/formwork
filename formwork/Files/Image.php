<?php

namespace Formwork\Files;

use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\MimeType;
use BadMethodCallException;
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
     * Array containing image information
     *
     * @var array
     */
    protected $info = array();

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
     * Return image width
     *
     * @return int
     */
    public function width()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        return $this->width;
    }

    /**
     * Return image height
     *
     * @return int
     */
    public function height()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        return $this->height;
    }

    /**
     * Return image orientation
     *
     * @return string
     */
    public function orientation()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        if ($this->width >= $this->height) {
            return self::ORIENTATION_LANDSCAPE;
        }
        return self::ORIENTATION_PORTRAIT;
    }

    /**
     * Rotate image
     *
     * @param int $angle
     *
     * @return $this
     */
    public function rotate($angle)
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        $backgroundColor = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        $this->image = imagerotate($this->image, $angle, $backgroundColor);
        return $this;
    }

    /**
     * Flip image horizontally
     *
     * @return $this
     */
    public function flipHorizontal()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imageflip($this->image, IMG_FLIP_HORIZONTAL);
        return $this;
    }

    /**
     * Flip image vertically
     *
     * @return $this
     */
    public function flipVertical()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imageflip($this->image, IMG_FLIP_VERTICAL);
        return $this;
    }

    /**
     * Flip image horizontally and vertically
     *
     * @return $this
     */
    public function flipBoth()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imageflip($this->image, IMG_FLIP_BOTH);
        return $this;
    }

    /**
     * Resize image without keeping its aspect ratio
     *
     * @param int $destinationWidth
     * @param int $destinationHeight
     *
     * @return $this
     */
    public function resize($destinationWidth, $destinationHeight)
    {
        if (is_null($this->image)) {
            $this->initialize();
        }

        $sourceWidth = $this->width;
        $sourceHeight = $this->height;

        $sourceRatio = $sourceWidth / $sourceHeight;

        if (!$destinationWidth && !$destinationHeight) {
            throw new BadMethodCallException(__METHOD__ . ' must be called with at least one of $destinationWidth or $destinationHeight arguments');
        }

        if (!$destinationWidth) {
            $destinationWidth = $destinationHeight * $sourceRatio;
        } elseif (!$destinationHeight) {
            $destinationHeight = $destinationWidth / $sourceRatio;
        }

        $destinationImage = imagecreatetruecolor($destinationWidth, $destinationHeight);

        if ($this->info['mime'] === 'image/png' || $this->info['mime'] === 'image/gif') {
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
     *
     * @param int $factor
     *
     * @return $this
     */
    public function scale($factor)
    {
        return $this->resize($factor * $this->width, $factor * $this->height);
    }

    /**
     * Resize image keeping its aspect ratio
     *
     * @param int    $destinationWidth
     * @param int    $destinationHeight
     * @param string $mode              self::RESIZE_FIT_COVER (default) or self::RESIZE_FIT_CONTAIN
     *
     * @return $this
     */
    public function resizeToFit($destinationWidth, $destinationHeight, $mode = self::RESIZE_FIT_COVER)
    {
        if (is_null($this->image)) {
            $this->initialize();
        }

        $sourceWidth = $this->width;
        $sourceHeight = $this->height;

        if (!$destinationWidth || !$destinationHeight) {
            throw new BadMethodCallException(__METHOD__ . ' must be called with both $destinationWidth and $destinationHeight arguments');
        }

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

        if ($this->info['mime'] === 'image/png' || $this->info['mime'] === 'image/gif') {
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
     * @param int    $size
     * @param string $mode self::RESIZE_FIT_COVER (default) or self::RESIZE_FIT_CONTAIN
     *
     * @return $this
     */
    public function square($size, $mode = self::RESIZE_FIT_COVER)
    {
        return $this->resizeToFit($size, $size, $mode);
    }

    /**
     * Crop image to given size from origin coordinates
     *
     * @param int $originX
     * @param int $originY
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function crop($originX, $originY, $width, $height)
    {
        if (!$width || !$height) {
            throw new BadMethodCallException(__METHOD__ . ' must be called with both $width and $height arguments');
        }

        if (is_null($this->image)) {
            $this->initialize();
        }

        $destinationImage = imagecreatetruecolor($width, $height);

        if ($this->info['mime'] === 'image/png' || $this->info['mime'] === 'image/gif') {
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
     *
     * @return $this
     */
    public function desaturate()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * Invert image colors
     *
     * @return $this
     */
    public function invert()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_NEGATE);
        return $this;
    }

    /**
     * Increase or decrease image brightness
     *
     * @param int $amount Amount of brightness from -255 to 255
     *
     * @return $this
     */
    public function brightness($amount)
    {
        if ($amount < -255 || $amount > 255) {
            throw new UnexpectedValueException('$amount value must be in range -255-+255, ' . $amount . ' given');
        }
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $amount);
        return $this;
    }

    /**
     * Increase or decrease image contrast
     *
     * @param int $amount Amount of contrast from -100 to 100
     *
     * @return $this
     */
    public function contrast($amount)
    {
        if ($amount < -100 || $amount > 100) {
            throw new UnexpectedValueException('$amount value must be in range -100-+100, ' . $amount . ' given');
        }
        if (is_null($this->image)) {
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
     *
     * @return $this
     */
    public function colorize($red, $green, $blue, $alpha = 0)
    {
        if ($red < 0 || $red > 255) {
            throw new UnexpectedValueException('$red value must be in range 0-255, ' . $red . ' given');
        }
        if ($green < 0 || $green > 255) {
            throw new UnexpectedValueException('$green value must be in range 0-255, ' . $green . ' given');
        }
        if ($blue < 0 || $blue > 255) {
            throw new UnexpectedValueException('$blue value must be in range 0-255, ' . $blue . ' given');
        }
        if ($alpha < 0 || $alpha > 127) {
            throw new UnexpectedValueException('$alpha value must be in range 0-127, ' . $alpha . ' given');
        }
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_COLORIZE, $red, $green, $blue, $alpha);
        return $this;
    }

    /**
     * Apply sepia effect to image
     *
     * @return $this
     */
    public function sepia()
    {
        return $this->desaturate()->colorize(76, 48, 0);
    }

    /**
     * Apply edge detect effect to image
     *
     * @return $this
     */
    public function edgedetect()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_EDGEDETECT);
        return $this;
    }

    /**
     * Apply emboss effect to image
     *
     * @return $this
     */
    public function emboss()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_EMBOSS);
        return $this;
    }

    /**
     * Blur image
     *
     * @param int $amount Amount of blur from 0 to 100
     *
     * @return $this
     */
    public function blur($amount)
    {
        if ($amount < 0 || $amount > 100) {
            throw new UnexpectedValueException('$amount value must be in range 0-100, ' . $amount . ' given');
        }
        if (is_null($this->image)) {
            $this->initialize();
        }
        for ($i = 0; $i < $amount; $i++) {
            imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
        }
        return $this;
    }

    /**
     * Sharpen image
     *
     * @return $this
     */
    public function sharpen()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
        return $this;
    }

    /**
     * Smoothen image
     *
     * @param int $amount
     *
     * @return $this
     */
    public function smoothen($amount)
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagefilter($this->image, IMG_FILTER_SMOOTH, $amount);
        return $this;
    }

    /**
     * Pixelate image
     *
     * @param int $amount
     *
     * @return $this
     */
    public function pixelate($amount)
    {
        if (is_null($this->image)) {
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
    public function save($filename = null, $destroy = true)
    {
        if (is_null($this->image)) {
            $this->initialize();
        }

        if (is_null($filename)) {
            $filename = $this->path;
        }

        $extension = strtolower(FileSystem::extension($filename));
        $mimeType = MimeType::fromExtension($extension);

        switch ($mimeType) {
            case 'image/jpeg':
                imageinterlace($this->image, $this->JPEGSaveProgressive);
                return imagejpeg($this->image, $filename, $this->JPEGQuality);
            case 'image/png':
                return imagepng($this->image, $filename, $this->PNGCompression);
            case 'image/gif':
                return imagegif($this->image, $filename);
            default:
                throw new RuntimeException('Unknown image MIME type for .' . $filename . ' extension');
                break;
        }

        if ($destroy) {
            $this->destroy();
        }
    }

    /**
     * Delete image resources from memory
     */
    public function destroy()
    {
        if (is_null($this->image)) {
            $this->initialize();
        }
        imagedestroy($this->image);
        if (is_resource($this->sourceImage)) {
            imagedestroy($this->sourceImage);
        }
    }

    /**
     * Initialize Image object
     */
    protected function initialize()
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
    protected function enableTransparency($image)
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
