<?php

namespace Formwork\Images;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Images\ColorProfile\ColorSpace;
use UnexpectedValueException;

class ImageInfo implements Arrayable
{
    protected string $mimeType;

    protected int $width;

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

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function colorSpace(): ?ColorSpace
    {
        return $this->colorSpace;
    }

    public function colorDepth(): ?int
    {
        return $this->colorDepth;
    }

    public function colorNumber(): ?int
    {
        return $this->colorNumber;
    }

    public function hasAlphaChannel(): bool
    {
        return $this->hasAlphaChannel;
    }

    public function isAnimation(): bool
    {
        return $this->isAnimation;
    }

    public function animationFrames(): ?int
    {
        return $this->animationFrames;
    }

    public function animationRepeatCount(): ?int
    {
        return $this->animationRepeatCount;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
