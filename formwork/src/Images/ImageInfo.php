<?php

namespace Formwork\Images;

use Formwork\Images\ColorProfile\ColorSpace;
use UnexpectedValueException;

class ImageInfo
{
    protected string $mimeType;

    protected int $width;

    protected int $height;

    protected ?ColorSpace $colorSpace;

    protected ?int $colorDepth;

    protected ?int $colorNumber;

    protected bool $hasAlphaChannel;

    protected bool $isAnimation;

    protected ?int $animationFrames;

    protected ?int $animationRepeatCount;

    public function __construct(array $info)
    {
        foreach ($info as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new UnexpectedValueException();
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
}
