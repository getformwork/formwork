<?php

namespace Formwork\Images\Handler;

use Formwork\Images\ColorProfile\ColorProfile;
use Formwork\Images\ColorProfile\ColorSpace;
use Formwork\Images\Decoder\AvifDecoder;
use Formwork\Images\Exif\ExifData;
use Formwork\Images\Handler\Exceptions\UnsupportedFeatureException;
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
        $info = [
            'mimeType'             => 'image/avif',
            'width'                => 0,
            'height'               => 0,
            'colorSpace'           => ColorSpace::RGB,
            'colorDepth'           => 8,
            'colorNumber'          => null,
            'hasAlphaChannel'      => false,
            'isAnimation'          => false,
            'animationFrames'      => null,
            'animationRepeatCount' => null,
        ];

        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'ftyp' && str_starts_with($box['value'], 'avis')) {
                $info['isAnimation'] = true;
            }

            if ($box['type'] === 'meta') {
                $this->parseMetaBox($box['value'], $info);
            }
        }

        return new ImageInfo($info);
    }

    public function supportsTransforms(): bool
    {
        return !$this->getInfo()->isAnimation();
    }

    public static function supportsColorProfile(): bool
    {
        return true;
    }

    public function hasColorProfile(): bool
    {
        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'meta' && $this->findColorProfileInMetaBox($box['value']) !== null) {
                return true;
            }
        }

        return false;
    }

    public function getColorProfile(): ?ColorProfile
    {
        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'meta' && ($profileData = $this->findColorProfileInMetaBox($box['value'])) !== null) {
                return new ColorProfile($profileData);
            }
        }

        return null;
    }

    public function setColorProfile(ColorProfile $colorProfile): void
    {
        [$metaOffset, $metaSize] = $this->findBox($this->data, 0, strlen($this->data), 'meta');
        if ($metaOffset === 0) {
            return;
        }
        [$ilocOffset] = $this->findBox($this->data, $metaOffset + 12, $metaOffset + $metaSize, 'iloc');
        [$iprpOffset, $iprpSize] = $this->findBox($this->data, $metaOffset + 12, $metaOffset + $metaSize, 'iprp');
        if ($iprpOffset === 0) {
            return;
        }
        [$ipcoOffset, $ipcoSize] = $this->findBox($this->data, $iprpOffset + 8, $iprpOffset + $iprpSize, 'ipco');
        if ($ipcoOffset === 0) {
            return;
        }
        $offset = $ipcoOffset + 8;
        $colrFound = false;

        while ($offset < $ipcoOffset + $ipcoSize) {
            $boxSize = $this->unpack('N', $this->data, $offset)[1];
            if (substr($this->data, $offset + 4, 4) === 'colr' && (substr($this->data, $offset + 8, 4) === 'prof' || substr($this->data, $offset + 8, 4) === 'rICC')) {
                $newColrSize = 8 + 4 + strlen($colorProfile->getData());
                $sizeDiff = $newColrSize - $boxSize;
                $this->data = substr_replace($this->data, pack('N', $ipcoSize + $sizeDiff), $ipcoOffset, 4);
                $this->data = substr_replace($this->data, pack('N', $iprpSize + $sizeDiff), $iprpOffset, 4);
                $this->data = substr_replace($this->data, pack('N', $metaSize + $sizeDiff), $metaOffset, 4);
                if ($ilocOffset > 0 && $sizeDiff !== 0) {
                    $this->updateIlocBoxOffsets($ilocOffset, $sizeDiff);
                }
                $this->data = substr($this->data, 0, $offset) . pack('N', $newColrSize) . 'colr' . 'rICC' . $colorProfile->getData() . substr($this->data, $offset + $boxSize);
                $colrFound = true;
                break;
            }
            $offset += $boxSize;
        }

        if (!$colrFound) {
            $newColrSize = 8 + 4 + strlen($colorProfile->getData());
            $this->data = substr_replace($this->data, pack('N', $ipcoSize + $newColrSize), $ipcoOffset, 4);
            $this->data = substr_replace($this->data, pack('N', $iprpSize + $newColrSize), $iprpOffset, 4);
            $this->data = substr_replace($this->data, pack('N', $metaSize + $newColrSize), $metaOffset, 4);

            if ($ilocOffset > 0) {
                $this->updateIlocBoxOffsets($ilocOffset, $newColrSize);
            }
            $insertPosition = $ipcoOffset + $ipcoSize;
            $this->data = substr($this->data, 0, $insertPosition) . pack('N', $newColrSize) . 'colr' . 'rICC' . $colorProfile->getData() . substr($this->data, $insertPosition);
        }
    }

    public function removeColorProfile(): void
    {
        [$metaOffset, $metaSize] = $this->findBox($this->data, 0, strlen($this->data), 'meta');
        if ($metaOffset === 0) {
            return;
        }
        [$ilocOffset] = $this->findBox($this->data, $metaOffset + 12, $metaOffset + $metaSize, 'iloc');
        [$iprpOffset, $iprpSize] = $this->findBox($this->data, $metaOffset + 12, $metaOffset + $metaSize, 'iprp');
        if ($iprpOffset === 0) {
            return;
        }
        [$ipcoOffset, $ipcoSize] = $this->findBox($this->data, $iprpOffset + 8, $iprpOffset + $iprpSize, 'ipco');
        if ($ipcoOffset === 0) {
            return;
        }
        $offset = $ipcoOffset + 8;
        while ($offset < $ipcoOffset + $ipcoSize) {
            $boxSize = (int) $this->unpack('N', $this->data, $offset)[1];
            if (substr($this->data, $offset + 4, 4) === 'colr' && (substr($this->data, $offset + 8, 4) === 'prof' || substr($this->data, $offset + 8, 4) === 'rICC')) {
                $sizeDiff = -$boxSize;
                $this->data = substr_replace($this->data, pack('N', $ipcoSize + $sizeDiff), $ipcoOffset, 4);
                $this->data = substr_replace($this->data, pack('N', $iprpSize + $sizeDiff), $iprpOffset, 4);
                $this->data = substr_replace($this->data, pack('N', $metaSize + $sizeDiff), $metaOffset, 4);
                if ($ilocOffset > 0) {
                    $this->updateIlocBoxOffsets($ilocOffset, $sizeDiff);
                }
                $this->data = substr($this->data, 0, $offset) . substr($this->data, $offset + $boxSize);
                return;
            }
            $offset += $boxSize;
        }
    }

    public static function supportsExifData(): bool
    {
        return true;
    }

    public function hasExifData(): bool
    {
        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'meta') {
                return $this->findExifItemInMetaBox($box['value']) !== null;
            }
        }

        return false;
    }

    public function getExifData(): ?ExifData
    {
        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'meta' && ($exifItemId = $this->findExifItemInMetaBox($box['value'])) !== null && ($exifData = $this->extractExifDataFromItem($box['value'], $exifItemId)) !== null) {
                return new ExifData($exifData);
            }
        }

        return null;
    }

    public function setExifData(ExifData $exifData): void
    {
        [$metaOffset, $metaSize] = $this->findBox($this->data, 0, strlen($this->data), 'meta');
        if ($metaOffset === 0) {
            throw new UnsupportedFeatureException('AVIF file does not contain a meta box');
        }
        $exifPayload = pack('N', 0) . $exifData->getData();
        $fullMetaContent = substr($this->data, $metaOffset + 8, $metaSize - 8);
        $existingExifItemId = $this->findExifItemInMetaBox($fullMetaContent);

        if ($existingExifItemId !== null) {
            $this->updateExifDataInMdatBox($existingExifItemId, $fullMetaContent, $exifPayload);
        } else {
            $this->insertExifData($metaOffset, $metaSize, $exifPayload);
        }
    }

    public function removeExifData(): void
    {
        [$metaOffset, $metaSize] = $this->findBox($this->data, 0, strlen($this->data), 'meta');
        if ($metaOffset === 0) {
            return;
        }
        $metaContentStart = $metaOffset + 8;
        $metaContent = substr($this->data, $metaContentStart, $metaSize - 8);
        $exifItemId = $this->findExifItemInMetaBox($metaContent);

        if ($exifItemId === null) {
            return;
        }
        $offset = 4;
        while ($offset < strlen($metaContent)) {
            $size = $this->unpack('N', $metaContent, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaContent, $offset + 4, 4);

            if ($type === 'iinf') {
                $iinfOffset = $metaContentStart + $offset;
                $contentOffset = $iinfOffset + 8;
                $version = ord($this->data[$contentOffset]);
                $entryCountOffset = $contentOffset + 4;
                $entryCount = $version === 0
                    ? $this->unpack('n', $this->data, $entryCountOffset)[1]
                    : $this->unpack('N', $this->data, $entryCountOffset)[1];

                $infeOffset = $entryCountOffset + ($version === 0 ? 2 : 4);

                for ($i = 0; $i < $entryCount; $i++) {
                    $infeSize = $this->unpack('N', $this->data, $infeOffset)[1];

                    if (substr($this->data, $infeOffset + 4, 4) === 'infe') {
                        $infeContentOffset = $infeOffset + 8;
                        $infeVersion = ord($this->data[$infeContentOffset]);
                        $itemIdOffset = $infeContentOffset + 4;
                        $itemId = $infeVersion < 3
                            ? $this->unpack('n', $this->data, $itemIdOffset)[1]
                            : $this->unpack('N', $this->data, $itemIdOffset)[1];

                        if ($itemId === $exifItemId) {
                            $this->data = substr($this->data, 0, $infeOffset) . substr($this->data, $infeOffset + $infeSize);
                            $this->data = substr_replace($this->data, $version === 0 ? pack('n', $entryCount - 1) : pack('N', $entryCount - 1), $entryCountOffset, $version === 0 ? 2 : 4);
                            $this->data = substr_replace($this->data, pack('N', $size - $infeSize), $iinfOffset, 4);
                            foreach ($this->decoder->decode($this->data) as $box) {
                                if ($box['type'] === 'meta') {
                                    $this->data = substr_replace($this->data, pack('N', $box['size'] - $infeSize), $box['offset'], 4);
                                    break;
                                }
                            }
                            return;
                        }
                    }
                    $infeOffset += $infeSize;
                }
                return;
            }
            $offset += $size;
        }
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

    /**
     * @param array<string, mixed> $info
     */
    private function parseMetaBox(string $metaData, array &$info): void
    {
        $offset = 4;

        while ($offset < strlen($metaData)) {
            $size = $this->unpack('N', $metaData, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaData, $offset + 4, 4);
            $offset += 8;

            if ($type === 'iprp') {
                $this->parseIprpBox(substr($metaData, $offset, $size - 8), $info);
            }
            $offset += $size - 8;
        }
    }

    /**
     * @param array<string, mixed> $info
     */
    private function parseIprpBox(string $iprpData, array &$info): void
    {
        $offset = 0;

        while ($offset < strlen($iprpData)) {
            $size = $this->unpack('N', $iprpData, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($iprpData, $offset + 4, 4);
            $offset += 8;

            if ($type === 'ipco') {
                $this->parseIpcoBox(substr($iprpData, $offset, $size - 8), $info);
            }
            $offset += $size - 8;
        }
    }

    /**
     * @param array<string, mixed> $info
     */
    private function parseIpcoBox(string $ipcoData, array &$info): void
    {
        $offset = 0;

        while ($offset < strlen($ipcoData)) {
            $size = $this->unpack('N', $ipcoData, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($ipcoData, $offset + 4, 4);
            $contentOffset = $offset + 8;

            if ($type === 'ispe') {
                $info['width'] = $this->unpack('N', $ipcoData, $contentOffset + 4)[1];
                $info['height'] = $this->unpack('N', $ipcoData, $contentOffset + 8)[1];
            } elseif ($type === 'pixi') {
                if (ord($ipcoData[$contentOffset + 4]) > 0) {
                    $info['colorDepth'] = ord($ipcoData[$contentOffset + 5]);
                }
            } elseif ($type === 'auxC') {
                if (str_contains($auxType = $this->unpack('Z*', substr($ipcoData, $contentOffset + 4))[1], 'alpha') || str_contains($auxType, 'urn:mpeg:mpegB:cicp:systems:auxiliary:alpha')) {
                    $info['hasAlphaChannel'] = true;
                }
            }
            $offset += $size;
        }
    }

    /**
     * @return array<int, int>
     */
    private function findBox(string $data, int $offset, int $end, string $type): array
    {
        while ($offset < $end) {
            $size = $this->unpack('N', $data, $offset)[1];
            if ($size === 0) {
                break;
            }
            $boxType = substr($data, $offset + 4, 4);
            if ($boxType === $type) {
                return [$offset, $size];
            }
            $offset += $size;
        }

        return [0, 0];
    }

    /**
     * @return array<int, int>
     */
    private function findMetaBox(): array
    {
        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'meta') {
                return [$box['offset'], $box['size']];
            }
        }
        return [0, 0];
    }

    private function findColorProfileInMetaBox(string $metaData): ?string
    {
        $offset = 4;

        while ($offset < strlen($metaData)) {
            $size = $this->unpack('N', $metaData, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaData, $offset + 4, 4);
            $offset += 8;

            if ($type === 'iprp' && ($result = $this->findColorProfileInIprpBox(substr($metaData, $offset, $size - 8))) !== null) {
                return $result;
            }
            $offset += $size - 8;
        }

        return null;
    }

    private function findColorProfileInIprpBox(string $iprpData): ?string
    {
        $offset = 0;

        while ($offset < strlen($iprpData)) {
            $size = $this->unpack('N', $iprpData, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($iprpData, $offset + 4, 4);
            $offset += 8;

            if ($type === 'ipco' && ($result = $this->findColorProfileInIpcoBox(substr($iprpData, $offset, $size - 8))) !== null) {
                return $result;
            }
            $offset += $size - 8;
        }

        return null;
    }

    private function findColorProfileInIpcoBox(string $ipcoData): ?string
    {
        $offset = 0;

        while ($offset < strlen($ipcoData)) {
            $size = $this->unpack('N', $ipcoData, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($ipcoData, $offset + 4, 4);
            $contentOffset = $offset + 8;

            if ($type === 'colr') {
                $colorType = substr($ipcoData, $contentOffset, 4);
                if ($colorType === 'prof' || $colorType === 'rICC') {
                    return substr($ipcoData, $contentOffset + 4, $size - 12);
                }
            }
            $offset += $size;
        }

        return null;
    }

    private function findExifItemInMetaBox(string $metaData): ?int
    {
        $offset = 4;

        while ($offset < strlen($metaData)) {
            $size = $this->unpack('N', $metaData, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaData, $offset + 4, 4);
            $contentOffset = $offset + 8;

            if ($type === 'iinf') {
                $version = ord($metaData[$contentOffset]);
                $entryCountOffset = $contentOffset + 4;
                $entryCount = $version === 0
                    ? $this->unpack('n', $metaData, $entryCountOffset)[1]
                    : $this->unpack('N', $metaData, $entryCountOffset)[1];

                $infeOffset = $entryCountOffset + ($version === 0 ? 2 : 4);

                for ($i = 0; $i < $entryCount; $i++) {
                    $infeSize = $this->unpack('N', $metaData, $infeOffset)[1];
                    $infeType = substr($metaData, $infeOffset + 4, 4);

                    if ($infeType === 'infe') {
                        $infeContentOffset = $infeOffset + 8;
                        $infeVersion = ord($metaData[$infeContentOffset]);
                        $itemIdOffset = $infeContentOffset + 4;
                        $itemId = $infeVersion < 3
                            ? $this->unpack('n', $metaData, $itemIdOffset)[1]
                            : $this->unpack('N', $metaData, $itemIdOffset)[1];

                        $itemTypeOffset = $itemIdOffset + ($infeVersion < 3 ? 4 : 6);
                        $itemType = substr($metaData, $itemTypeOffset, 4);

                        if ($itemType === 'Exif') {
                            return $itemId;
                        }
                    }
                    $infeOffset += $infeSize;
                }
            }
            $offset += $size;
        }

        return null;
    }

    private function extractExifDataFromItem(string $metaData, int $itemId): ?string
    {
        $offset = 4;

        while ($offset < strlen($metaData)) {
            $size = $this->unpack('N', $metaData, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaData, $offset + 4, 4);
            $contentOffset = $offset + 8;

            if ($type === 'iloc') {
                $location = $this->findItemLocationInIlocBox(substr($metaData, $contentOffset, $size - 8), $itemId);
                if ($location !== null) {
                    $exifDataWithHeader = substr($this->data, $location['offset'], $location['length']);
                    $tiffHeaderOffset = $this->unpack('N', $exifDataWithHeader, 0)[1];
                    return substr($exifDataWithHeader, 4 + $tiffHeaderOffset);
                }
                return null;
            }
            $offset += $size;
        }

        return null;
    }

    /**
     * @return array{offset: int, length: int}|null
     */
    private function findItemLocationInIlocBox(string $ilocData, int $targetItemId): ?array
    {
        $offset = 0;
        $version = ord($ilocData[$offset]);
        $offset += 4;
        $offsetSize = (ord($ilocData[$offset]) >> 4) & 0x0F;
        $lengthSize = ord($ilocData[$offset]) & 0x0F;
        $baseOffsetSize = (ord($ilocData[$offset + 1]) >> 4) & 0x0F;
        $offset += 2;
        $itemCount = $version < 2
            ? $this->unpack('n', $ilocData, $offset)[1]
            : $this->unpack('N', $ilocData, $offset)[1];
        $offset += $version < 2 ? 2 : 4;

        for ($i = 0; $i < $itemCount; $i++) {
            $itemId = $version < 2
                ? $this->unpack('n', $ilocData, $offset)[1]
                : $this->unpack('N', $ilocData, $offset)[1];
            $offset += $version < 2 ? 2 : 4;

            if ($version === 1 || $version === 2) {
                $offset += 2;
            }
            $offset += 2;
            $baseOffset = 0;
            if ($baseOffsetSize > 0) {
                $baseOffset = $this->unpackInt($ilocData, $offset, $baseOffsetSize);
                $offset += $baseOffsetSize;
            }
            $extentCount = $this->unpack('n', $ilocData, $offset)[1];
            $offset += 2;

            if ($itemId === $targetItemId && $extentCount > 0) {
                if ($version === 1 || $version === 2) {
                    $offset += $offsetSize;
                }

                return [
                    'offset' => $baseOffset + $this->unpackInt($ilocData, $offset, $offsetSize),
                    'length' => $this->unpackInt($ilocData, $offset + $offsetSize, $lengthSize),
                ];
            }

            for ($j = 0; $j < $extentCount; $j++) {
                if ($version === 1 || $version === 2) {
                    $offset += $offsetSize;
                }
                $offset += $offsetSize + $lengthSize;
            }
        }

        return null;
    }

    private function updateIlocBoxOffsets(int $ilocOffset, int $sizeDiff): void
    {
        [$metaOffset, $metaSize] = $this->findMetaBox();

        if ($metaOffset === 0) {
            return;
        }
        $metaEndBeforeExpansion = $metaOffset + $metaSize - $sizeDiff;
        $offset = $ilocOffset + 8;
        $version = ord($this->data[$offset]);
        $offset += 4;
        $offsetSize = (ord($this->data[$offset]) >> 4) & 0x0F;
        $lengthSize = ord($this->data[$offset]) & 0x0F;
        $baseOffsetSize = (ord($this->data[$offset + 1]) >> 4) & 0x0F;
        $offset += 2;
        $itemCount = $version < 2
            ? $this->unpack('n', $this->data, $offset)[1]
            : $this->unpack('N', $this->data, $offset)[1];
        $offset += $version < 2 ? 2 : 4;

        for ($i = 0; $i < $itemCount; $i++) {
            $offset += $version < 2 ? 2 : 4;
            if ($version === 1 || $version === 2) {
                $offset += 2;
            }
            $offset += 2;

            if ($baseOffsetSize > 0) {
                if ($baseOffsetSize === 4) {
                    $old = $this->unpack('N', $this->data, $offset)[1];
                    if ($old >= $metaEndBeforeExpansion) {
                        $this->data = substr_replace($this->data, pack('N', $old + $sizeDiff), $offset, 4);
                    }
                } elseif ($baseOffsetSize === 8) {
                    $old = $this->unpack('J', $this->data, $offset)[1];
                    if ($old >= $metaEndBeforeExpansion) {
                        $this->data = substr_replace($this->data, pack('J', $old + $sizeDiff), $offset, 8);
                    }
                }
                $offset += $baseOffsetSize;
            }
            $extentCount = $this->unpack('n', $this->data, $offset)[1];
            $offset += 2;

            for ($j = 0; $j < $extentCount; $j++) {
                if ($version === 1 || $version === 2) {
                    $offset += $offsetSize;
                }

                if ($offsetSize === 4) {
                    $old = $this->unpack('N', $this->data, $offset)[1];
                    if ($old >= $metaEndBeforeExpansion) {
                        $this->data = substr_replace($this->data, pack('N', $old + $sizeDiff), $offset, 4);
                    }
                } elseif ($offsetSize === 8) {
                    $old = $this->unpack('J', $this->data, $offset)[1];
                    if ($old >= $metaEndBeforeExpansion) {
                        $this->data = substr_replace($this->data, pack('J', $old + $sizeDiff), $offset, 8);
                    }
                }
                $offset += $offsetSize + $lengthSize;
            }
        }
    }

    private function updateExifDataInMdatBox(int $itemId, string $metaContent, string $exifPayload): void
    {
        $offset = 4;
        while ($offset < strlen($metaContent)) {
            $size = $this->unpack('N', $metaContent, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaContent, $offset + 4, 4);
            $contentOffset = $offset + 8;

            if ($type === 'iloc') {
                $location = $this->findItemLocationInIlocBox(substr($metaContent, $contentOffset, $size - 8), $itemId);
                if ($location !== null) {
                    foreach ($this->decoder->decode($this->data) as $box) {
                        if ($box['type'] === 'mdat') {
                            $mdatContentStart = $box['offset'] + 8;
                            $relativeOffset = $location['offset'] - $mdatContentStart;
                            $oldLength = $location['length'];
                            $newLength = strlen($exifPayload);
                            $sizeDiff = $newLength - $oldLength;
                            $mdatContentOffset = $box['offset'] + 8 + $relativeOffset;
                            $this->data = substr($this->data, 0, $mdatContentOffset)
                                . $exifPayload
                                . substr($this->data, $mdatContentOffset + $oldLength);

                            $newMdatSize = $box['size'] + $sizeDiff;
                            $this->data = substr_replace($this->data, pack('N', $newMdatSize), $box['offset'], 4);

                            if ($sizeDiff !== 0) {
                                [$metaOffset, $metaSize] = $this->findMetaBox();
                                if ($metaOffset > 0) {
                                    $metaContentStart = $metaOffset + 12;
                                    $metaContent = substr($this->data, $metaContentStart, $metaSize - 12);
                                    $offset = 4;
                                    while ($offset < strlen($metaContent)) {
                                        $size = $this->unpack('N', $metaContent, $offset)[1];
                                        if ($size === 0) {
                                            break;
                                        }
                                        $type = substr($metaContent, $offset + 4, 4);
                                        $contentOffset = $offset + 8;
                                        if ($type === 'iloc') {
                                            $ilocOffset = $metaContentStart + $offset;
                                            $ilocContentOffset = $ilocOffset + 8;
                                            $version = ord($this->data[$ilocContentOffset]);
                                            $ilocContentOffset += 4;
                                            $offsetSize = (ord($this->data[$ilocContentOffset]) >> 4) & 0x0F;
                                            $lengthSize = ord($this->data[$ilocContentOffset]) & 0x0F;
                                            $baseOffsetSize = (ord($this->data[$ilocContentOffset + 1]) >> 4) & 0x0F;
                                            $ilocContentOffset += 2;
                                            $itemCount = $version < 2
                                                ? $this->unpack('n', $this->data, $ilocContentOffset)[1]
                                                : $this->unpack('N', $this->data, $ilocContentOffset)[1];
                                            $ilocContentOffset += $version < 2 ? 2 : 4;
                                            for ($i = 0; $i < $itemCount; $i++) {
                                                $currentItemId = $version < 2
                                                    ? $this->unpack('n', $this->data, $ilocContentOffset)[1]
                                                    : $this->unpack('N', $this->data, $ilocContentOffset)[1];
                                                $ilocContentOffset += $version < 2 ? 2 : 4;
                                                if ($version === 1 || $version === 2) {
                                                    $ilocContentOffset += 2;
                                                }
                                                $ilocContentOffset += 2;
                                                if ($baseOffsetSize > 0) {
                                                    $ilocContentOffset += $baseOffsetSize;
                                                }
                                                $extentCount = $this->unpack('n', $this->data, $ilocContentOffset)[1];
                                                $ilocContentOffset += 2;
                                                if ($currentItemId === $itemId && $extentCount > 0) {
                                                    if ($version === 1 || $version === 2) {
                                                        $ilocContentOffset += $offsetSize;
                                                    }
                                                    $ilocContentOffset += $offsetSize;
                                                    if ($lengthSize === 4) {
                                                        $this->data = substr_replace($this->data, pack('N', $newLength), $ilocContentOffset, 4);
                                                    } elseif ($lengthSize === 8) {
                                                        $this->data = substr_replace($this->data, pack('J', $newLength), $ilocContentOffset, 8);
                                                    }
                                                    break 2;
                                                }
                                                for ($j = 0; $j < $extentCount; $j++) {
                                                    if ($version === 1 || $version === 2) {
                                                        $ilocContentOffset += $offsetSize;
                                                    }
                                                    $ilocContentOffset += $offsetSize + $lengthSize;
                                                }
                                            }
                                            break;
                                        }
                                        $offset += $size;
                                    }
                                }
                            }

                            return;
                        }
                    }
                }
                break;
            }
            $offset += $size;
        }
    }

    private function insertExifData(int $metaOffset, int $metaSize, string $exifPayload): void
    {
        $originalMetaSize = $metaSize;
        $metaContentStart = $metaOffset + 12;
        $metaContent = substr($this->data, $metaContentStart, $metaSize - 12);
        $iinfOffset = 0;
        $iinfSize = 0;
        $offset = 0;

        while ($offset < strlen($metaContent)) {
            $size = $this->unpack('N', $metaContent, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaContent, $offset + 4, 4);
            if ($type === 'iinf') {
                $iinfOffset = $metaContentStart + $offset;
                $iinfSize = $size;
                break;
            }
            $offset += $size;
        }

        if ($iinfOffset === 0) {
            [$iinfOffset, $iinfSize] = $this->createIinfBox($metaOffset, $metaSize);
        }
        $newItemId = $this->getNextItemId(substr($this->data, $metaOffset + 12, $this->findBox($this->data, 0, strlen($this->data), 'meta')[1] - 12));
        $this->addInfeToIinfBox((int) $iinfOffset, (int) $iinfSize, $this->createInfeBox($newItemId, 'Exif'));
        [$mdatOffset, $mdatSize] = $this->findBox($this->data, 0, strlen($this->data), 'mdat');

        if ($mdatOffset === 0) {
            [$mdatOffset, $mdatSize] = $this->createMdatBox();
        }
        $this->finalizeIlocBoxOffsets($metaOffset, $originalMetaSize);
        [$mdatOffset, $mdatSize] = $this->findBox($this->data, 0, strlen($this->data), 'mdat');
        $mdatContentEnd = $mdatOffset + $mdatSize;

        $absoluteExifOffset = $mdatContentEnd;

        $this->data = substr($this->data, 0, $mdatContentEnd) . $exifPayload . substr($this->data, $mdatContentEnd);

        $newMdatSize = $mdatSize + strlen($exifPayload);
        $this->data = substr_replace($this->data, pack('N', $newMdatSize), $mdatOffset, 4);

        $this->addIlocEntry($newItemId, $absoluteExifOffset, strlen($exifPayload));
    }

    private function finalizeIlocBoxOffsets(int $originalMetaOffset, int $originalMetaSize): void
    {
        [$metaOffset, $metaSize] = $this->findMetaBox();

        if ($metaOffset === 0) {
            return;
        }
        $totalExpansion = $metaSize - $originalMetaSize;
        if ($totalExpansion === 0) {
            return;
        }
        $threshold = $originalMetaOffset + $originalMetaSize;
        $metaContent = substr($this->data, $metaOffset + 12, $metaSize - 12);
        $offset = 0;

        while ($offset < strlen($metaContent)) {
            $size = $this->unpack('N', $metaContent, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaContent, $offset + 4, 4);

            if ($type === 'iloc') {
                $ilocOffset = $metaOffset + 12 + $offset;
                $ilocContentOffset = $ilocOffset + 8;
                $version = ord($this->data[$ilocContentOffset]);
                $ilocContentOffset += 4;
                $offsetSize = (ord($this->data[$ilocContentOffset]) >> 4) & 0x0F;
                $lengthSize = ord($this->data[$ilocContentOffset]) & 0x0F;
                $baseOffsetSize = (ord($this->data[$ilocContentOffset + 1]) >> 4) & 0x0F;
                $ilocContentOffset += 2;
                $itemCount = $version < 2
                    ? $this->unpack('n', $this->data, $ilocContentOffset)[1]
                    : $this->unpack('N', $this->data, $ilocContentOffset)[1];
                $ilocContentOffset += $version < 2 ? 2 : 4;

                for ($i = 0; $i < $itemCount; $i++) {
                    $ilocContentOffset += $version < 2 ? 2 : 4;
                    if ($version === 1 || $version === 2) {
                        $ilocContentOffset += 2;
                    }
                    $ilocContentOffset += 2;

                    if ($baseOffsetSize > 0) {
                        $ilocContentOffset += $baseOffsetSize;
                    }
                    $extentCount = $this->unpack('n', $this->data, $ilocContentOffset)[1];
                    $ilocContentOffset += 2;

                    for ($j = 0; $j < $extentCount; $j++) {
                        if ($version === 1 || $version === 2) {
                            $ilocContentOffset += $offsetSize;
                        }

                        if ($offsetSize === 4) {
                            $old = $this->unpack('N', $this->data, $ilocContentOffset)[1];
                            if ($old > 0 && $old >= $threshold) {
                                $new = $old + $totalExpansion;
                                $this->data = substr_replace($this->data, pack('N', $new), $ilocContentOffset, 4);
                            }
                        } elseif ($offsetSize === 8) {
                            $old = $this->unpack('J', $this->data, $ilocContentOffset)[1];
                            if ($old > 0 && $old >= $threshold) {
                                $new = $old + $totalExpansion;
                                $this->data = substr_replace($this->data, pack('J', $new), $ilocContentOffset, 8);
                            }
                        }
                        $ilocContentOffset += $offsetSize + $lengthSize;
                    }
                }

                return;
            }
            $offset += $size;
        }
    }

    private function getNextItemId(string $metaContent): int
    {
        $maxId = 0;
        $offset = 0;

        while ($offset < strlen($metaContent)) {
            $size = $this->unpack('N', $metaContent, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaContent, $offset + 4, 4);
            $contentOffset = $offset + 8;

            if ($type === 'iinf') {
                $version = ord($metaContent[$contentOffset]);
                $entryCountOffset = $contentOffset + 4;
                $entryCount = $version === 0
                    ? $this->unpack('n', $metaContent, $entryCountOffset)[1]
                    : $this->unpack('N', $metaContent, $entryCountOffset)[1];

                $infeOffset = $entryCountOffset + ($version === 0 ? 2 : 4);

                for ($i = 0; $i < $entryCount; $i++) {
                    $infeSize = $this->unpack('N', $metaContent, $infeOffset)[1];
                    $infeContentOffset = $infeOffset + 8;
                    $infeVersion = ord($metaContent[$infeContentOffset]);
                    $itemIdOffset = $infeContentOffset + 4;
                    $itemId = $infeVersion < 3
                        ? $this->unpack('n', $metaContent, $itemIdOffset)[1]
                        : $this->unpack('N', $metaContent, $itemIdOffset)[1];

                    $maxId = max($maxId, $itemId);
                    $infeOffset += $infeSize;
                }
                break;
            }
            $offset += $size;
        }

        return $maxId + 1;
    }

    private function createInfeBox(int $itemId, string $itemType): string
    {
        // version (1 byte) + flags (3 bytes) + item_ID (2 bytes) + item_protection_index (2 bytes) + item_type (4 bytes) + null terminator
        $content = pack('C', 2) . substr(pack('N', 0), 1, 3) . pack('nn', $itemId, 0) . $itemType . "\x00";

        return pack('N', 8 + strlen($content)) . 'infe' . $content;
    }

    private function addInfeToIinfBox(int $iinfOffset, int $iinfSize, string $infeBox): void
    {
        $contentOffset = $iinfOffset + 8;
        $version = ord($this->data[$contentOffset]);
        $entryCountOffset = $contentOffset + 4;
        $entryCount = $version === 0
            ? $this->unpack('n', $this->data, $entryCountOffset)[1]
            : $this->unpack('N', $this->data, $entryCountOffset)[1];

        $this->data = substr_replace($this->data, $version === 0 ? pack('n', $entryCount + 1) : pack('N', $entryCount + 1), $entryCountOffset, $version === 0 ? 2 : 4);
        $insertPosition = $iinfOffset + $iinfSize;
        $this->data = substr($this->data, 0, $insertPosition) . $infeBox . substr($this->data, $insertPosition);
        $this->data = substr_replace($this->data, pack('N', $iinfSize + strlen($infeBox)), $iinfOffset, 4);

        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'meta') {
                $newMetaSize = $box['size'] + strlen($infeBox);
                $this->data = substr_replace($this->data, pack('N', $newMetaSize), $box['offset'], 4);
                $ilocOffset = 0;
                $metaContentStart = $box['offset'] + 12;
                $metaContent = substr($this->data, $metaContentStart, $newMetaSize - 12);
                $offset = 4;
                while ($offset < strlen($metaContent)) {
                    $size = $this->unpack('N', $metaContent, $offset)[1];
                    if ($size === 0) {
                        break;
                    }
                    $type = substr($metaContent, $offset + 4, 4);
                    if ($type === 'iloc') {
                        $ilocOffset = $metaContentStart + $offset;
                        break;
                    }
                    $offset += $size;
                }

                if ($ilocOffset > 0) {
                    $this->updateIlocBoxOffsets($ilocOffset, strlen($infeBox));
                }

                break;
            }
        }
    }

    private function addIlocEntry(int $itemId, int $dataOffset, int $dataLength): void
    {
        [$metaOffset, $metaSize] = $this->findMetaBox();

        if ($metaOffset === 0) {
            return;
        }
        $metaContentStart = $metaOffset + 12;
        $metaContent = substr($this->data, $metaContentStart, $metaSize - 12);
        $offset = 0;
        while ($offset < strlen($metaContent)) {
            $size = $this->unpack('N', $metaContent, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaContent, $offset + 4, 4);

            if ($type === 'iloc') {
                $ilocOffset = $metaContentStart + $offset;
                $ilocContentOffset = $ilocOffset + 8;
                $version = ord($this->data[$ilocContentOffset]);
                $ilocContentOffset += 4;
                $offsetSize = (ord($this->data[$ilocContentOffset]) >> 4) & 0x0F;
                $lengthSize = ord($this->data[$ilocContentOffset]) & 0x0F;
                $baseOffsetSize = (ord($this->data[$ilocContentOffset + 1]) >> 4) & 0x0F;
                $ilocContentOffset += 2;
                $itemCountOffset = $ilocContentOffset;
                $itemCount = $version < 2
                    ? $this->unpack('n', $this->data, $itemCountOffset)[1]
                    : $this->unpack('N', $this->data, $itemCountOffset)[1];

                $newEntry = '';
                if ($version === 0) {
                    $newEntry .= pack('nnn', $itemId, 0, 1);
                } elseif ($version === 1 || $version === 2) {
                    $newEntry .= pack('Nnnn', $itemId, 0, 0, 1);
                } else {
                    $newEntry .= pack('Nnn', $itemId, 0, 1);
                }
                if ($offsetSize === 4) {
                    $newEntry .= pack('N', $dataOffset);
                } elseif ($offsetSize === 8) {
                    $newEntry .= pack('J', $dataOffset);
                }
                if ($lengthSize === 4) {
                    $newEntry .= pack('N', $dataLength);
                } elseif ($lengthSize === 8) {
                    $newEntry .= pack('J', $dataLength);
                }
                $this->data = substr_replace($this->data, $version < 2 ? pack('n', $itemCount + 1) : pack('N', $itemCount + 1), $itemCountOffset, $version < 2 ? 2 : 4);
                $insertPosition = $ilocOffset + $size;
                $this->data = substr($this->data, 0, $insertPosition) . $newEntry . substr($this->data, $insertPosition);
                $this->data = substr_replace($this->data, pack('N', $size + strlen($newEntry)), $ilocOffset, 4);
                $this->data = substr_replace($this->data, pack('N', $metaSize + strlen($newEntry)), $metaOffset, 4);
                $this->updateIlocBoxOffsets($ilocOffset, strlen($newEntry));

                return;
            }
            $offset += $size;
        }
        $this->createIlocBox($metaOffset);
        $metaSize = 0;
        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'meta') {
                $metaOffset = $box['offset'];
                $metaSize = $box['size'];
                break;
            }
        }

        if ($metaSize === 0) {
            return;
        }
        $metaContentStart = $metaOffset + 12;
        $metaContent = substr($this->data, $metaContentStart, $metaSize - 12);
        $offset = 0;
        while ($offset < strlen($metaContent)) {
            $size = $this->unpack('N', $metaContent, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaContent, $offset + 4, 4);

            if ($type === 'iloc') {
                $ilocOffset = $metaContentStart + $offset;
                $ilocContentOffset = $ilocOffset + 8;
                $version = ord($this->data[$ilocContentOffset]);
                $ilocContentOffset += 4;
                $offsetSize = (ord($this->data[$ilocContentOffset]) >> 4) & 0x0F;
                $lengthSize = ord($this->data[$ilocContentOffset]) & 0x0F;
                $ilocContentOffset += 2;
                $itemCountOffset = $ilocContentOffset;
                $itemCount = $version < 2
                    ? $this->unpack('n', $this->data, $itemCountOffset)[1]
                    : $this->unpack('N', $this->data, $itemCountOffset)[1];

                $newEntry = '';
                if ($version === 0) {
                    $newEntry .= pack('nnn', $itemId, 0, 1);
                } elseif ($version === 1 || $version === 2) {
                    $newEntry .= pack('Nnnn', $itemId, 0, 0, 1);
                } else {
                    $newEntry .= pack('Nnn', $itemId, 0, 1);
                }

                if ($offsetSize === 4) {
                    $newEntry .= pack('N', $dataOffset);
                } elseif ($offsetSize === 8) {
                    $newEntry .= pack('J', $dataOffset);
                }

                if ($lengthSize === 4) {
                    $newEntry .= pack('N', $dataLength);
                } elseif ($lengthSize === 8) {
                    $newEntry .= pack('J', $dataLength);
                }
                $this->data = substr_replace($this->data, $version < 2 ? pack('n', $itemCount + 1) : pack('N', $itemCount + 1), $itemCountOffset, $version < 2 ? 2 : 4);
                $insertPosition = $ilocOffset + $size;
                $this->data = substr($this->data, 0, $insertPosition) . $newEntry . substr($this->data, $insertPosition);
                $this->data = substr_replace($this->data, pack('N', $size + strlen($newEntry)), $ilocOffset, 4);
                $this->data = substr_replace($this->data, pack('N', $metaSize + strlen($newEntry)), $metaOffset, 4);
                $this->updateIlocBoxOffsets($ilocOffset, strlen($newEntry));

                return;
            }
            $offset += $size;
        }
    }

    /**
     * @return array<int, int>
     */
    private function createIinfBox(int $metaOffset, int $metaSize): array
    {
        $metaContentStart = $metaOffset + 12;
        $metaContent = substr($this->data, $metaContentStart, $metaSize - 12);
        $ilocOffset = 0;
        $offset = 4;
        while ($offset < strlen($metaContent)) {
            $size = $this->unpack('N', $metaContent, $offset)[1];
            if ($size === 0) {
                break;
            }
            $type = substr($metaContent, $offset + 4, 4);
            if ($type === 'iloc') {
                $ilocOffset = $metaContentStart + $offset;
                break;
            }
            $offset += $size;
        }
        $iinfBox = pack('N', 14) . 'iinf' . pack('C', 0) . substr(pack('N', 0), 1, 3) . pack('n', 0);
        $insertPosition = $ilocOffset > 0 ? (int) $ilocOffset : $metaOffset + $metaSize;
        $this->data = substr($this->data, 0, $insertPosition) . $iinfBox . substr($this->data, $insertPosition);
        $this->data = substr_replace($this->data, pack('N', $metaSize + strlen($iinfBox)), $metaOffset, 4);

        return [$insertPosition, 14];
    }

    /**
     * @return array<int, int>
     */
    private function createMdatBox(): array
    {
        $insertPosition = strlen($this->data);

        foreach ($this->decoder->decode($this->data) as $box) {
            if ($box['type'] === 'meta') {
                $insertPosition = $box['offset'] + $box['size'];
                break;
            }
        }
        $this->data = substr($this->data, 0, $insertPosition) . pack('N', 8) . 'mdat' . substr($this->data, $insertPosition);

        return [$insertPosition, 8];
    }

    private function createIlocBox(int $metaOffset): void
    {
        $metaSize = $this->findBox($this->data, 0, strlen($this->data), 'meta')[1];
        $insertPosition = $metaOffset + $metaSize;
        $this->data = substr($this->data, 0, $insertPosition) . pack('N', 16) . 'iloc' . pack('C', 0) . substr(pack('N', 0), 1, 3) . pack('C', 0x44) . pack('C', 0) . pack('n', 0) . substr($this->data, $insertPosition);
        $this->data = substr_replace($this->data, pack('N', $metaSize + 16), $metaOffset, 4);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function unpack(string $format, string $string, int $offset = 0): array
    {
        return unpack($format, $string, $offset) ?: throw new UnexpectedValueException('Cannot unpack string');
    }

    private function unpackInt(string $data, int $offset, int $size): int
    {
        if ($size === 0) {
            return 0;
        }
        if ($size === 4) {
            return $this->unpack('N', $data, $offset)[1];
        }
        if ($size === 8) {
            return $this->unpack('J', $data, $offset)[1];
        }
        $value = 0;
        for ($i = 0; $i < $size; $i++) {
            $value = ($value << 8) | ord($data[$offset + $i]);
        }
        return $value;
    }
}
