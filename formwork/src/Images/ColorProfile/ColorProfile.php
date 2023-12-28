<?php

namespace Formwork\Images\ColorProfile;

use Formwork\Utils\FileSystem;
use InvalidArgumentException;
use UnexpectedValueException;

class ColorProfile
{
    protected const ICC_PROFILE_SIGNATURE = 'acsp';

    protected const ICC_PROFILE_SIGNATURE_OFFSET = 36;

    /**
     * @var array<string, mixed>
     */
    protected array $tags;

    public function __construct(protected string $data)
    {
        if (strpos($this->data, self::ICC_PROFILE_SIGNATURE) !== self::ICC_PROFILE_SIGNATURE_OFFSET) {
            throw new InvalidArgumentException('Invalid ICC profile data');
        }

        $this->tags = $this->getTags();
    }

    public function name(): string
    {
        return $this->getTagValue('desc', '');
    }

    public function copyright(): string
    {
        return $this->getTagValue('cprt', '');
    }

    public function profileVersion(): string
    {
        return sprintf('%u.%u.%u', ord($this->data[8]), (ord($this->data[9]) & 0xf0) >> 4, ord($this->data[9]) & 0x0f);
    }

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

    public function connectionSpace(): string
    {
        return trim(substr($this->data, 20, 4));
    }

    public function primaryPlatform(): string
    {
        return substr($this->data, 40, 4);
    }

    public function renderingIntent(): RenderingIntent
    {
        $renderingIntent = unpack('N', $this->data, 64)[1];
        return match ($renderingIntent) {
            0       => RenderingIntent::Perceptual,
            1       => RenderingIntent::MediaRelative,
            3       => RenderingIntent::Saturation,
            4       => RenderingIntent::IccAbsolute,
            default => throw new UnexpectedValueException('Unexpected rendering intent'),
        };
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function export(string $path): void
    {
        FileSystem::write($path, $this->data);
    }

    public static function fromFile(string $path): ColorProfile
    {
        return new self(FileSystem::read($path));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTags(): array
    {
        $tags = [];
        $position = 128;
        $count = unpack('N', $this->data, $position)[1];
        $position += 4;
        for ($i = 0; $i < $count; $i++) {
            $info = unpack('Z4tag/Noffset/Nlength', $this->data, $position);
            $tag = array_shift($info);
            $tags[$tag] = $info;
            $position += 12;
        }
        return $tags;
    }

    protected function getTagValue(string $name, ?string $default = null): mixed
    {
        if (!isset($this->tags[$name])) {
            return $default;
        }
        ['offset' => $offset, 'length' => $length] = $this->tags[$name];
        $value = substr($this->data, $offset, $length);
        $type = substr($value, 0, 4);
        return match ($type) {
            'text' => substr($value, 8),
            'desc' => unpack('Z*', $value, 12)[1],
            // @phpstan-ignore-next-line
            'mluc'  => $this->parseMlucString($value)[0] ?? $default,
            default => $default,
        };
    }

    /**
     * @return array<string, string>
     */
    protected function parseMlucString(string $data): array
    {
        $result = [];
        $position = 0;
        $type = substr($data, 0, 4);
        if ($type !== 'mluc') {
            throw new InvalidArgumentException('Invalid mluc tag');
        }
        $position += 8;
        $records = unpack('N', $data, $position)[1];
        $position += 8;
        for ($i = 0; $i < $records; $i++) {
            $langCode = substr($data, $position, 4);
            $position += 4;
            $stringLength = unpack('N', $data, $position)[1];
            $position += 4;
            $stringOffset = unpack('N', $data, $position)[1];
            $result[$langCode] = mb_convert_encoding(substr($data, $stringOffset, $stringLength), 'UTF-8', 'UTF-16BE');
        }
        return $result;
    }
}
