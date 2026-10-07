<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\AvifDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Utils\Avif\AvifFile;
use Formwork\Images\ImageInfo;
use GdImage;
use RuntimeException;
use UnexpectedValueException;

/**
 * @since 2.3.0
 */
final class AvifHandler extends AbstractHandler
{
    public function getInfo(): ImageInfo
    {
        $file = $this->getFile();

        return new ImageInfo([
            'mimeType'             => 'image/avif',
            'colorSpace'           => ColorSpace::RGB,
            'colorNumber'          => null,
            'isAnimation'          => $file->isAnimation(),
            'animationFrames'      => null,
            'animationRepeatCount' => null,
            ...$file->getInfo(),
        ]);
    }

    public function supportsTransforms(): bool
    {
        return !$this->getFile()->isAnimation();
    }

    public static function supportsColorProfile(): bool
    {
        return true;
    }

    public function hasColorProfile(): bool
    {
        return $this->getFile()->getColorProfile() !== null;
    }

    public function getColorProfile(): ?ColorProfile
    {
        $profile = $this->getFile()->getColorProfile();

        return $profile === null ? null : new ColorProfile($profile);
    }

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        $this->edit(static fn(AvifFile $file) => $file->setColorProfile($colorProfile->getData()));
    }

    public function removeColorProfile(): void
    {
        $this->edit(static fn(AvifFile $file) => $file->removeColorProfile());
    }

    public static function supportsExifData(): bool
    {
        return true;
    }

    public function hasExifData(): bool
    {
        return $this->getFile()->getExifData() !== null;
    }

    public function getExifData(): ?ExifData
    {
        $data = $this->getFile()->getExifData();

        return $data === null ? null : new ExifData($data);
    }

    public function setExifData(ExifData $exifData): void
    {
        $this->edit(static fn(AvifFile $file) => $file->setExifData($exifData->getData()));
    }

    public function removeExifData(): void
    {
        $this->edit(static fn(AvifFile $file) => $file->removeExifData());
    }

    protected function getDecoder(): AvifDecoder
    {
        return new AvifDecoder();
    }

    protected function setDataFromGdImage(GdImage $gdImage): void
    {
        imagesavealpha($gdImage, true);

        if (!imageistruecolor($gdImage)) {
            imagepalettetotruecolor($gdImage);
        }

        ob_start();

        if (imageavif($gdImage, null, $this->options['avifQuality']) === false) {
            throw new RuntimeException('Cannot set data from GdImage');
        }
        $this->data = ob_get_clean() ?: throw new UnexpectedValueException('Unexpected empty image data');
    }

    private function getFile(): AvifFile
    {
        return AvifFile::fromDecodedBoxes($this->decoder->decode($this->data));
    }

    /**
     * Apply a change to the image data
     *
     * @param callable(AvifFile): void $edit
     */
    private function edit(callable $edit): void
    {
        $file = $this->getFile();
        $edit($file);
        $this->data = $file->toString();
    }
}
