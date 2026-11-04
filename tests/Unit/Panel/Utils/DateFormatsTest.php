<?php

namespace Formwork\Tests\Unit\Panel\Utils;

use Formwork\Panel\Utils\DateFormats;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DateFormats::class)]
final class DateFormatsTest extends TestCase
{
    public function testDateFormatsAreKeyedByFormat(): void
    {
        $this->assertSame(['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'], array_keys(DateFormats::date()));
    }

    public function testDateLabelsStartWithTheFormattedCurrentDate(): void
    {
        foreach (DateFormats::date() as $format => $label) {
            $this->assertStringStartsWith(date($format) . ' (', $label);
            $this->assertStringEndsWith(')', $label);
        }
    }

    public function testDateLabelsDescribeThePattern(): void
    {
        $this->assertStringEndsWith('(DD/MM/YYYY)', DateFormats::date()['d/m/Y']);
        $this->assertStringEndsWith('(YYYY-MM-DD)', DateFormats::date()['Y-m-d']);
    }

    public function testHourFormatsAreKeyedByFormat(): void
    {
        $this->assertSame(['H:i', 'h:i A'], array_keys(DateFormats::hour()));
    }

    public function testHourLabelsStartWithTheFormattedCurrentTime(): void
    {
        foreach (DateFormats::hour() as $label) {
            $this->assertMatchesRegularExpression('/^\d{2}:\d{2}( [AP]M)? \(.+\)$/', $label);
        }
    }

    public function testTimezonesAreKeyedByIdentifier(): void
    {
        $timezones = DateFormats::timezones();

        $this->assertSame(timezone_identifiers_list(), array_keys($timezones));
    }

    public function testTimezoneLabelsReplaceUnderscoresWithSpaces(): void
    {
        $timezones = DateFormats::timezones();

        $this->assertSame('America/New York', $timezones['America/New_York']);
        $this->assertSame('America/Argentina/Buenos Aires', $timezones['America/Argentina/Buenos_Aires']);
        $this->assertSame('UTC', $timezones['UTC']);
    }
}
