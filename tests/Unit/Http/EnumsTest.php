<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\RequestType;
use Formwork\Http\ResponseStatus;
use Formwork\Http\ResponseStatusType;
use Formwork\Http\Session\MessageType;
use Formwork\Http\Utils\DeviceType;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ValueError;

#[CoversClass(RequestType::class)]
#[CoversClass(ResponseStatusType::class)]
#[CoversClass(DeviceType::class)]
#[CoversClass(MessageType::class)]
final class EnumsTest extends TestCase
{
    public function testRequestTypes(): void
    {
        $this->assertSame('HTTP', RequestType::Http->value);
        $this->assertSame('XHR', RequestType::XmlHttpRequest->value);
        $this->assertSame(RequestType::XmlHttpRequest, RequestType::from('XHR'));
        $this->assertNull(RequestType::tryFrom('xhr'), 'Values are case sensitive');
        $this->assertCount(2, RequestType::cases());
    }

    public function testDeviceTypes(): void
    {
        $this->assertSame(['mobile', 'tablet', 'desktop'], array_map(static fn(DeviceType $type): string => $type->value, DeviceType::cases()));
        $this->assertSame(DeviceType::Tablet, DeviceType::from('tablet'));
        $this->assertNull(DeviceType::tryFrom('watch'));
    }

    public function testMessageTypes(): void
    {
        $this->assertSame(['info', 'success', 'warning', 'error'], array_map(static fn(MessageType $type): string => $type->value, MessageType::cases()));
        $this->assertSame(MessageType::Error, MessageType::from('error'));
    }

    public function testInvalidValuesAreRejected(): void
    {
        $this->expectException(ValueError::class);

        MessageType::from('fatal');
    }

    public function testResponseStatusTypesCoverEveryHttpClass(): void
    {
        $this->assertSame(
            ['Informational', 'Successful', 'Redirection', 'ClientError', 'ServerError'],
            array_map(static fn(ResponseStatusType $type): string => $type->name, ResponseStatusType::cases())
        );
    }

    public function testEveryResponseStatusHasTheTypeOfItsCodeClass(): void
    {
        $expected = [
            1 => ResponseStatusType::Informational,
            2 => ResponseStatusType::Successful,
            3 => ResponseStatusType::Redirection,
            4 => ResponseStatusType::ClientError,
            5 => ResponseStatusType::ServerError,
        ];

        foreach (ResponseStatus::cases() as $status) {
            $this->assertSame($expected[intdiv($status->code(), 100)], $status->type(), sprintf('Status %d', $status->code()));
        }
    }
}
