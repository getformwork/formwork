<?php

namespace Formwork\Tests\Unit\Images\Isobmff;

use Formwork\Images\Isobmff\Box;
use Formwork\Images\Isobmff\ImageSequence;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Images\Fixtures\AvifFileFixture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ImageSequence::class)]
final class ImageSequenceTest extends TestCase
{
    #[DataProvider('timelineProvider')]
    public function testTimelineIsReadFromTheMovieBox(int $frames, int $plays): void
    {
        $sequence = ImageSequence::fromBox($this->movie(AvifFileFixture::create()->withFrames($frames, $plays)->build()));

        $this->assertSame($frames, $sequence->frameCount);
        $this->assertSame($plays, $sequence->playCount);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function timelineProvider(): iterable
    {
        yield 'played once' => [3, 1];
        yield 'played three times' => [3, 3];
        yield 'looping forever' => [7, 0];
        yield 'single frame' => [1, 2];
    }

    public function testMoviesWithoutTracksHaveAnUnknownTimeline(): void
    {
        $sequence = ImageSequence::fromBox(new Box('moov'));

        $this->assertNull($sequence->frameCount);
        $this->assertNull($sequence->playCount);
    }

    public function testTracksReferencingOthersAreIgnored(): void
    {
        $auxiliary = AvifFileFixture::box('trak', AvifFileFixture::box('tref', AvifFileFixture::box('auxl', "\x00\x00\x00\x01")) . AvifFileFixture::box('mdia', AvifFileFixture::box('minf', AvifFileFixture::box('stbl', AvifFileFixture::fullBox('stsz', 0, pack('NN', 10, 99))))));
        $movie = $this->movie(AvifFileFixture::create()->withFrames(4)->build());
        $sequence = ImageSequence::fromBox(new Box('moov', $auxiliary . $movie->payload));

        $this->assertSame(4, $sequence->frameCount);
    }

    public function testTracksWithoutEditListArePlayedOnce(): void
    {
        $stsz = AvifFileFixture::fullBox('stsz', 0, pack('NN', 10, 6));
        $track = AvifFileFixture::box('trak', AvifFileFixture::fullBox('tkhd', 0, str_repeat("\x00", 80)) . AvifFileFixture::box('mdia', AvifFileFixture::box('minf', AvifFileFixture::box('stbl', $stsz))));

        $sequence = ImageSequence::fromBox(new Box('moov', $track));

        $this->assertSame(6, $sequence->frameCount);
        $this->assertSame(1, $sequence->playCount);
    }

    public function testShortDurationFieldsAreRead(): void
    {
        $header = AvifFileFixture::fullBox('tkhd', 0, pack('NNNNN', 0, 0, 1, 0, 12) . str_repeat("\x00", 60));
        $edits = AvifFileFixture::box('edts', AvifFileFixture::fullBox('elst', 0, pack('N', 1) . pack('NN', 4, 0) . pack('N', 0x10000), 1));
        $track = AvifFileFixture::box('trak', $header . $edits);

        $sequence = ImageSequence::fromBox(new Box('moov', $track));

        $this->assertNull($sequence->frameCount);
        $this->assertSame(3, $sequence->playCount);
    }

    public function testTruncatedBoxesLeaveTheTimelineUnknown(): void
    {
        $track = AvifFileFixture::box('trak', AvifFileFixture::fullBox('tkhd', 1, "\x00\x00") . AvifFileFixture::box('edts', AvifFileFixture::fullBox('elst', 1, "\x00", 1)) . AvifFileFixture::box('mdia', AvifFileFixture::box('minf', AvifFileFixture::box('stbl', AvifFileFixture::fullBox('stsz', 0, "\x00")))));

        $sequence = ImageSequence::fromBox(new Box('moov', $track));

        $this->assertNull($sequence->frameCount);
        $this->assertNull($sequence->playCount);
    }

    public function testMalformedMoviesHaveAnUnknownTimeline(): void
    {
        $sequence = ImageSequence::fromBox(new Box('moov', "\x00\x00\x00\xff" . 'trak'));

        $this->assertNull($sequence->frameCount);
        $this->assertNull($sequence->playCount);
    }

    private function movie(string $file): Box
    {
        foreach (Box::parseAll($file) as $box) {
            if ($box->type === 'moov') {
                return $box;
            }
        }

        $this->fail('Missing movie box');
    }
}
