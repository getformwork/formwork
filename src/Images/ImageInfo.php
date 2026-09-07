<?php

namespace Formwork\Images;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Images\ColorProfile\ColorSpace;
use UnexpectedValueException;

class ImageInfo implements Arrayable
{
    protected string $mimeType;

    /**
     * @var int<1, max>
     */
    protected int $width;

    /**
     * @var int<1, max>
     */
    protected int $height;

    protected ?ColorSpace $colorSpace = null;

    protected ?int $colorDepth = null;

    protected ?int $colorNumber = null;

    protected bool $hasAlphaChannel;

    protected bool $isAnimation;

    protected ?int $animationFrames = null;

    protected ?int $animationRepeatCount = null;

    /**
     * @param array<string, mixed> $info
     */
    public function __construct(array $info)
    {
        foreach ($info as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new UnexpectedValueException(sprintf('Invalid property "%s"', $key));
            }

            $this->{$key} = $value;
        }
    }

    /**
     * Get image MIME type
     */
    public function mimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get image width
     *
     * @return int<1, max>
     */
    public function width(): int
    {
        return $this->width;
    }

    /**
     * Get image height
     *
     * @return int<1, max>
     */
    public function height(): int
    {
        return $this->height;
    }

    /**
     * Get image color space
     */
    public function colorSpace(): ?ColorSpace
    {
        return $this->colorSpace;
    }

    /**
     * Get image color depth
     */
    public function colorDepth(): ?int
    {
        return $this->colorDepth;
    }

    /**
     * Get image number of colors
     */
    public function colorNumber(): ?int
    {
        return $this->colorNumber;
    }

    /**
     * Return whether image has an alpha channel
     */
    public function hasAlphaChannel(): bool
    {
        return $this->hasAlphaChannel;
    }

    /**
     * Return whether image is animated
     */
    public function isAnimation(): bool
    {
        return $this->isAnimation;
    }

    /**
     * Get number of animation frames
     */
    public function animationFrames(): ?int
    {
        return $this->animationFrames;
    }

    /**
     * Get animation repeat count
     */
    public function animationRepeatCount(): ?int
    {
        return $this->animationRepeatCount;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
