<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\ResponseStatus;
use Formwork\Http\ResponseStatusType;
use Formwork\Tests\TestCase;
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
}
