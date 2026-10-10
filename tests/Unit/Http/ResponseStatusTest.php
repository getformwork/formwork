<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\ResponseStatus;
use Formwork\Http\ResponseStatusType;
use Formwork\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ResponseStatus::class)]
final class ResponseStatusTest extends TestCase
{
    #[DataProvider('statusProvider')]
    public function testStatusExposesCodeMessageAndType(ResponseStatus $status, int $code, ResponseStatusType $type): void
    {
        $this->assertSame($code, $status->code());
        $this->assertSame(explode(' ', $status->value, 2)[1], $status->message());
        $this->assertSame($type, $status->type());
        $this->assertSame($status, ResponseStatus::fromCode($code));
    }

    public static function statusProvider(): array
    {
        return [
            'informational' => [ResponseStatus::Continue, 100, ResponseStatusType::Informational],
            'successful'    => [ResponseStatus::OK, 200, ResponseStatusType::Successful],
            'redirect'      => [ResponseStatus::Found, 302, ResponseStatusType::Redirection],
            'client error'  => [ResponseStatus::NotFound, 404, ResponseStatusType::ClientError],
            'server error'  => [ResponseStatus::InternalServerError, 500, ResponseStatusType::ServerError],
        ];
    }

    public function testStatusCodesAreUnique(): void
    {
        $codes = array_map(static fn(ResponseStatus $status): int => $status->code(), ResponseStatus::cases());

        $this->assertSame($codes, array_values(array_unique($codes)));
    }

    public function testEveryStatusIsConsistentWithItsValueAndType(): void
    {
        foreach (ResponseStatus::cases() as $status) {
            $this->assertMatchesRegularExpression('/^[1-5][0-9]{2} [A-Za-z\' -]+$/', $status->value, $status->name);
            $this->assertSame($status, ResponseStatus::fromCode($status->code()), $status->name);
            $this->assertSame(
                match ((int) ($status->code() / 100)) {
                    1 => ResponseStatusType::Informational,
                    2 => ResponseStatusType::Successful,
                    3 => ResponseStatusType::Redirection,
                    4 => ResponseStatusType::ClientError,
                    5 => ResponseStatusType::ServerError,
                },
                $status->type(),
                $status->name,
            );
        }
    }

    public function testUnknownStatusCodesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ResponseStatus::fromCode(299);
    }
}
