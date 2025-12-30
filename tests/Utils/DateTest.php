<?php

namespace Formwork\Tests\Utils;

use DateTime;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use Formwork\Utils\Date;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Date::class)]
final class DateTest extends TestCase
{
    private array $translation = [
        'date.distance.ago'     => '%s fa',
        'date.distance.in'      => 'tra %s',
        'date.duration.days'    => ['giorno', 'giorni'],
        'date.duration.hours'   => ['ora', 'ore'],
        'date.duration.minutes' => ['minuto', 'minuti'],
        'date.duration.months'  => ['mese', 'mesi'],
        'date.duration.seconds' => ['secondo', 'secondi'],
        'date.duration.weeks'   => ['settimana', 'settimane'],
        'date.duration.years'   => ['anno', 'anni'],
        'date.months.long'      => ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
        'date.months.short'     => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
        'date.now'              => 'adesso',
        'date.weekdays.long'    => ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
        'date.weekdays.short'   => ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
    ];

    public function testToTimestamp(): void
    {
        $this->assertSame(1869436800, Date::toTimestamp('2029-03-29', 'Y-m-d'));
        $this->assertSame(1869436800, Date::toTimestamp('29/03/2029', ['Y-m-d', 'd/m/Y']));
        $this->assertSame(1869436800, Date::toTimestamp('2029-03-29', ''));
    }

    public function testToTimestampThrowsOnInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Date::toTimestamp('invalid-date', 'Y-m-d');
    }

    public function testFormatToPattern(): void
    {
        $this->assertSame('YYYY-MM-DD', Date::formatToPattern('Y-m-d'));
        $this->assertSame('DD/MM/YYYY hh:mm:ss', Date::formatToPattern('d/m/Y H:i:s'));
        $this->assertSame('DD/MM/YYYY [at] hh:mm:ss', Date::formatToPattern('d/m/Y \a\t H:i:s'));
    }

    public function testPatternToFormat(): void
    {
        $this->assertSame('Y-m-d', Date::patternToFormat('YYYY-MM-DD'));
        $this->assertSame('d/m/Y H:i:s', Date::patternToFormat('DD/MM/YYYY hh:mm:ss'));
        $this->assertSame('l d F Y \a\t h:i:s A \o\' \c\l\o\c\k', Date::patternToFormat('DDDD DD MMMM YYYY [at] HH:mm:ss A [o\' clock]'));
    }

    public function testFormat(): void
    {
        $dateTime = new DateTime('2029-03-29 15:30:00');

        $translation = new Translation('it', $this->translation);

        $this->assertSame('Gio, 29 Mar 2029 15:30:00 +0000', Date::formatDateTime(new DateTime('2029-03-29 15:30:00'), 'r', $translation));
        $this->assertSame('Giovedì 29 Marzo 2029', Date::formatDateTime($dateTime, 'l d F Y', $translation));
    }

    public function testFormatWithTimestamp(): void
    {
        $translation = new Translation('it', $this->translation);

        $this->assertSame('Gio, 29 Mar 2029 10:10:00 +0000', Date::formatTimestamp(1869473400, 'r', $translation));
        $this->assertSame('Giovedì 29 Marzo 2029', Date::formatTimestamp(1869473400, 'l d F Y', $translation));
    }

    public function testFormatDistance(): void
    {
        $translation = new Translation('it', $this->translation);

        $now = time();

        $this->assertSame('adesso', Date::formatDateTimeAsDistance(new DateTime('@' . $now), $translation, $now));
        $this->assertSame('5 giorni fa', Date::formatDateTimeAsDistance(new DateTime('@' . ($now - 5 * 86400)), $translation, $now));
        $this->assertSame('tra 3 ore', Date::formatDateTimeAsDistance(new DateTime('@' . ($now + 3 * 3600)), $translation, $now));
    }

    public function testFormatDistanceWithTimestamp(): void
    {
        $translation = new Translation('it', $this->translation);

        $now = time();

        $this->assertSame('adesso', Date::formatTimestampAsDistance($now, $translation, $now));
        $this->assertSame('2 mesi fa', Date::formatTimestampAsDistance($now - 60 * 86400, $translation, $now));
        $this->assertSame('tra 10 minuti', Date::formatTimestampAsDistance($now + 10 * 60, $translation, $now));
    }
}
