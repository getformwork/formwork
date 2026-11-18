<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\RequestType;
use Formwork\Http\ResponseStatus;
use Formwork\Http\ResponseStatusType;
use Formwork\Http\Session\MessageType;
use Formwork\Http\Utils\DeviceType;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RequestType::class)]
#[CoversClass(ResponseStatusType::class)]
#[CoversClass(DeviceType::class)]
#[CoversClass(MessageType::class)]
final class EnumsTest extends TestCase
{
    public function testRequestTypesHaveTheirValues(): void
    {
        $this->assertSame(['HTTP', 'XHR'], array_map(static fn(RequestType $requestType): string => $requestType->value, RequestType::cases()));
    }

    public function testDeviceTypesHaveTheirValues(): void
    {
        $this->assertSame(['mobile', 'tablet', 'desktop'], array_map(static fn(DeviceType $deviceType): string => $deviceType->value, DeviceType::cases()));
    }

    public function testMessageTypesHaveTheirValues(): void
    {
        $this->assertSame(['info', 'success', 'warning', 'error'], array_map(static fn(MessageType $messageType): string => $messageType->value, MessageType::cases()));
    }

    public function testResponseStatusTypesCoverEveryHttpClass(): void
    {
        $this->assertSame(
            ['Informational', 'Successful', 'Redirection', 'ClientError', 'ServerError'],
            array_map(static fn(ResponseStatusType $responseStatusType): string => $responseStatusType->name, ResponseStatusType::cases())
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
