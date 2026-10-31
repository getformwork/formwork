<?php

namespace Formwork\Images\Isobmff;

use Formwork\Images\Binary\ByteReader;
use Formwork\Images\Binary\Bytes;
use UnexpectedValueException;

/**
 * Timeline of an image sequence, read from the movie box (moov)
 *
 * Only the first track without references to other tracks is considered, since the
 * others hold auxiliary data such as the alpha channel.
 *
 * @since 2.4.0
 */
final readonly class ImageSequence
{
    public const string TYPE = 'moov';

    /**
     * Flag of the edit list stating that its edits are repeated
     */
    private const int REPEAT_FLAG = 0x01;

    /**
     * @param ?int $frameCount Number of frames, or null if unknown
     * @param ?int $playCount  Number of times the sequence is played, 0 if it loops forever, or null if unknown
     */
    public function __construct(
        public ?int $frameCount,
        public ?int $playCount,
    ) {}

    /**
     * Read the timeline of the sequence, leaving unknown the values that cannot be read
     */
    public static function fromBox(Box $box): self
    {
        $track = self::findTrack($box);

        if ($track === null) {
            return new self(null, null);
        }

        try {
            $frameCount = self::readFrameCount($track);
        } catch (UnexpectedValueException) {
            $frameCount = null;
        }

        try {
            $playCount = self::readPlayCount($track);
        } catch (UnexpectedValueException) {
            $playCount = null;
        }

        return new self($frameCount, $playCount);
    }

    private static function findTrack(Box $movie): ?Box
    {
        try {
            foreach (Box::parseAll($movie->payload) as $box) {
                if ($box->type === 'trak' && self::find($box, ['tref']) === null) {
                    return $box;
                }
            }
        } catch (UnexpectedValueException) {
        }

        return null;
    }

    /**
     * Find a descendant box following a path of box types
     *
     * @param list<string> $path
     */
    private static function find(Box $box, array $path): ?Box
    {
        foreach ($path as $type) {
            $found = null;

            foreach (Box::parseAll($box->payload) as $child) {
                if ($child->type === $type) {
                    $found = $child;
                    break;
                }
            }

            if ($found === null) {
                return null;
            }

            $box = $found;
        }

        return $box;
    }

    private static function readFrameCount(Box $track): ?int
    {
        $sizes = self::find($track, ['mdia', 'minf', 'stbl', 'stsz']);

        if ($sizes === null) {
            return null;
        }

        $reader = new ByteReader($sizes->payload);
        $reader->skip(8);

        return $reader->uint(4);
    }

    private static function readPlayCount(Box $track): ?int
    {
        $header = self::find($track, ['tkhd']);
        $edits = self::find($track, ['edts', 'elst']);

        if ($header === null) {
            return null;
        }

        if ($edits === null) {
            return 1;
        }

        $reader = new ByteReader($edits->payload);
        $version = $reader->uint(1);
        $repeats = ($reader->uint(3) & self::REPEAT_FLAG) !== 0;

        if (!$repeats) {
            return 1;
        }

        if ($reader->uint(4) < 1) {
            return null;
        }

        $segmentDuration = $reader->uint($version === 1 ? 8 : 4);

        $reader = new ByteReader($header->payload);
        $version = $reader->uint(1);
        $durationSize = $version === 1 ? 8 : 4;

        // Skip flags, creation and modification times, track identifier and reserved field
        $reader->skip(3 + 2 * $durationSize + 8);
        $duration = $reader->bytes($durationSize);

        if ($duration === str_repeat("\xff", $durationSize)) {
            return 0;
        }

        return $segmentDuration > 0 ? max(1, intdiv(Bytes::unpack($duration, 0, $durationSize), $segmentDuration)) : null;
    }
}
