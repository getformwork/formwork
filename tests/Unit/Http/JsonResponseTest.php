<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\JsonResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(JsonResponse::class)]
final class JsonResponseTest extends TestCase
{
    public function testSuccessFactoryBuildsASuccessPayload(): void
    {
        $jsonResponse = JsonResponse::success('Done', ResponseStatus::Created, ['id' => 7]);
        $payload = json_decode($jsonResponse->content(), true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame('Done', $payload['message']);
        $this->assertSame(201, (int) $payload['code']);
        $this->assertSame(['id' => 7], $payload['data']);
        $this->assertSame(ResponseStatus::Created, $jsonResponse->status());
        $this->assertSame('application/json; charset=utf-8', $jsonResponse->headers()->get('Content-Type'));
    }

    public function testErrorFactoryBuildsAnErrorPayload(): void
    {
        $jsonResponse = JsonResponse::error('Invalid', ResponseStatus::UnprocessableEntity, ['field' => 'name']);
        $payload = json_decode($jsonResponse->content(), true);

        $this->assertSame('error', $payload['status']);
        $this->assertSame('Invalid', $payload['message']);
        $this->assertSame(422, (int) $payload['code']);
        $this->assertSame(['field' => 'name'], $payload['data']);
        $this->assertSame(ResponseStatus::UnprocessableEntity, $jsonResponse->status());
    }

    public function testFactoriesUseDefaultStatusesAndEmptyData(): void
    {
        $success = json_decode(JsonResponse::success('Done')->content(), true);
        $error = JsonResponse::error('Failed');

        $this->assertSame(200, (int) $success['code']);
        $this->assertSame([], $success['data']);
        $this->assertSame(ResponseStatus::BadRequest, $error->status());
    }

    public function testCustomHeadersCanOverrideTheContentType(): void
    {
        $jsonResponse = new JsonResponse('{}', headers: ['Content-Type' => 'application/problem+json']);

        $this->assertSame('application/problem+json', $jsonResponse->headers()->get('Content-Type'));
        $this->assertSame('{}', $jsonResponse->content());
    }
}
