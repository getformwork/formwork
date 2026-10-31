<?php

namespace Formwork\Images\ColorProfile;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;
use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use UnexpectedValueException;

class ColorProfile
{
    /**
     * File signature for ICC profiles
     */
    protected const string ICC_PROFILE_SIGNATURE = 'acsp';

    /**
     * Offset of the color profile signature
     */
    protected const int ICC_PROFILE_SIGNATURE_OFFSET = 36;

    /**
     * Color profile tags
     *
     * @var array<string, mixed>
     */
    protected array $tags;

    /**
     * @throws InvalidArgumentException If the ICC profile data is invalid
     */
    public function __construct(
        protected string $data,
    ) {
        if (substr($this->data, self::ICC_PROFILE_SIGNATURE_OFFSET, strlen(self::ICC_PROFILE_SIGNATURE)) !== self::ICC_PROFILE_SIGNATURE) {
            throw new InvalidArgumentException('Invalid ICC profile data');
        }

        try {
            $this->tags = $this->getTags();
        } catch (UnexpectedValueException $unexpectedValueException) {
            throw new InvalidArgumentException('Invalid ICC profile data', previous: $unexpectedValueException);
        }
    }

    /**
     * Get the name of the color profile
     */
    public function name(): string
    {
        return $this->getTagValue('desc', '');
    }

    /**
     * Get the copyright string of the color profile
     */
    public function copyright(): string
    {
        return $this->getTagValue('cprt', '');
    }

    /**
     * Get the color profile version
     */
    public function profileVersion(): string
    {
        return sprintf('%u.%u.%u', ord($this->data[8]), (ord($this->data[9]) & 0xf0) >> 4, ord($this->data[9]) & 0x0f);
    }

    /**
     * Get the color profile device class
     *
     * @throws UnexpectedValueException If the device class is unexpected
     */
    public function deviceClass(): DeviceClass
    {
        $deviceClass = substr($this->data, 12, 4);

        return match ($deviceClass) {
            'scnr'  => DeviceClass::Input,
            'mntr'  => DeviceClass::Display,
            'prtr'  => DeviceClass::Output,
            'link'  => DeviceClass::Link,
            'spac'  => DeviceClass::ColorSpace,
            'abst'  => DeviceClass::AbstractProfile,
            'nmcl'  => DeviceClass::NamedColor,
            default => throw new UnexpectedValueException('Unexpected device class'),
        };
    }

    /**
     * Get the color profile color space
     *
     * @throws UnexpectedValueException If the color space is unexpected
     */
    public function colorSpace(): ColorSpace
    {
        $colorSpace = trim(substr($this->data, 16, 4));
        return match ($colorSpace) {
            'XYZ'   => ColorSpace::XYZ,
            'Lab'   => ColorSpace::LAB,
            'Luv'   => ColorSpace::LUV,
            'YCbCr' => ColorSpace::YCbCr,
            'Yxy'   => ColorSpace::XYY,
            'RGB'   => ColorSpace::RGB,
            'GRAY'  => ColorSpace::Grayscale,
            'HSV'   => ColorSpace::HSV,
            'HLS'   => ColorSpace::HLS,
            'CMYK'  => ColorSpace::CMYK,
            'CMY'   => ColorSpace::CMY,
            '2CLR'  => ColorSpace::Palette,
            '3CLR'  => ColorSpace::Palette,
            '4CLR'  => ColorSpace::Palette,
            '5CLR'  => ColorSpace::Palette,
            '6CLR'  => ColorSpace::Palette,
            '7CLR'  => ColorSpace::Palette,
            '8CLR'  => ColorSpace::Palette,
            '9CLR'  => ColorSpace::Palette,
            'ACLR'  => ColorSpace::Palette,
            'BCLR'  => ColorSpace::Palette,
            'CCLR'  => ColorSpace::Palette,
            'DCLR'  => ColorSpace::Palette,
            'ECLR'  => ColorSpace::Palette,
            'FCLR'  => ColorSpace::Palette,
            default => throw new UnexpectedValueException('Unexpected color space'),
        };
    }

    /**
     * Get the color profile connection space
     */
    public function connectionSpace(): string
    {
        return trim(substr($this->data, 20, 4));
    }

    /**
     * Get the color profile primary platform
     */
    public function primaryPlatform(): string
    {
        return substr($this->data, 40, 4);
    }

    /**
     * Get the color profile rendering intent
     *
     * @throws UnexpectedValueException If the rendering intent is unexpected
     */
    public function renderingIntent(): RenderingIntent
    {
        $renderingIntent = Bytes::unpack($this->data, 64, 4);
        return match ($renderingIntent) {
            0       => RenderingIntent::Perceptual,
            1       => RenderingIntent::MediaRelative,
            3       => RenderingIntent::Saturation,
            4       => RenderingIntent::IccAbsolute,
            default => throw new UnexpectedValueException('Unexpected rendering intent'),
        };
    }

    /**
     * Get the color profile data
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Export the color profile to a file
     */
    public function export(string $path): void
    {
        FileSystem::write($path, $this->data);
    }

    /**
     * Create a ColorProfile instance from a file
     */
    public static function fromFile(string $path): ColorProfile
    {
        return new self(FileSystem::read($path));
    }

    /**
     * Get the color profile tags
     *
     * @throws UnexpectedValueException If the tag table is truncated or a tag is outside the profile
     *
     * @return array<string, array{offset: int, length: int}>
     */
    protected function getTags(): array
    {
        $tags = [];
        $reader = new ByteReader($this->data, 128);
        $count = $reader->uint(4);

        if ($count * 12 > $reader->remaining()) {
            throw new UnexpectedValueException('Unexpected end of data');
        }

        for ($i = 0; $i < $count; $i++) {
            $tag = $reader->bytes(4);
            $offset = $reader->uint(4);
            $length = $reader->uint(4);

            if ($offset + $length > strlen($this->data)) {
                throw new UnexpectedValueException(sprintf('Tag "%s" is outside the profile', $tag));
            }

            $tags[$tag] = ['offset' => $offset, 'length' => $length];
        }

        return $tags;
    }

    /**
     * Get the value of a tag
     */
    protected function getTagValue(string $name, ?string $default = null): mixed
    {
        if (!isset($this->tags[$name])) {
            return $default;
        }
        ['offset' => $offset, 'length' => $length] = $this->tags[$name];
        $value = substr($this->data, $offset, $length);
        try {
            return match (substr($value, 0, 4)) {
                'text'  => rtrim(substr($value, 8), "\x00"),
                'desc'  => $this->parseDescString($value),
                'mluc'  => array_values($this->parseMlucString($value))[0] ?? $default,
                default => $default,
            };
        } catch (UnexpectedValueException) {
            return $default;
        }
    }

    /**
     * Parse a textDescriptionType string
     *
     * @throws UnexpectedValueException If the string is truncated
     */
    protected function parseDescString(string $data): string
    {
        $reader = new ByteReader($data, 8);
        $length = $reader->uint(4);

        return rtrim($reader->bytes(min($length, $reader->remaining())), "\x00");
    }

    /**
     * Parse a multi-lingual Unicode string
     *
     * @throws InvalidArgumentException If the mluc tag is invalid
     * @throws UnexpectedValueException If the mluc string is truncated or cannot be converted to UTF-8
     *
     * @return array<string, string>
     */
    protected function parseMlucString(string $data): array
    {
        $result = [];

        if (substr($data, 0, 4) !== 'mluc') {
            throw new InvalidArgumentException('Invalid mluc tag');
        }

        $reader = new ByteReader($data, 8);
        $records = $reader->uint(4);
        $reader->skip(4);

        for ($i = 0; $i < $records; $i++) {
            $langCode = $reader->bytes(4);
            $stringLength = $reader->uint(4);
            $stringOffset = $reader->uint(4);

            if ($stringOffset + $stringLength > strlen($data)) {
                throw new UnexpectedValueException('Unexpected end of data');
            }

            $value = mb_convert_encoding(substr($data, $stringOffset, $stringLength), 'UTF-8', 'UTF-16BE');
            // @phpstan-ignore identical.alwaysFalse
            if ($value === false) {
                throw new UnexpectedValueException('Cannot convert mluc string to UTF-8');
            }
            $result[$langCode] = $value;
        }

        return $result;
    }
}
