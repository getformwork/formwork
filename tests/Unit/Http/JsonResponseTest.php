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
        $response = JsonResponse::success('Done', ResponseStatus::Created, ['id' => 7]);
        $payload = json_decode($response->content(), true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame('Done', $payload['message']);
        $this->assertSame(201, (int) $payload['code']);
        $this->assertSame(['id' => 7], $payload['data']);
        $this->assertSame(ResponseStatus::Created, $response->status());
        $this->assertSame('application/json; charset=utf-8', $response->headers()->get('Content-Type'));
    }

    public function testErrorFactoryBuildsAnErrorPayload(): void
    {
        $response = JsonResponse::error('Invalid', ResponseStatus::UnprocessableEntity, ['field' => 'name']);
        $payload = json_decode($response->content(), true);

        $this->assertSame('error', $payload['status']);
        $this->assertSame('Invalid', $payload['message']);
        $this->assertSame(422, (int) $payload['code']);
        $this->assertSame(['field' => 'name'], $payload['data']);
        $this->assertSame(ResponseStatus::UnprocessableEntity, $response->status());
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
        $response = new JsonResponse('{}', headers: ['Content-Type' => 'application/problem+json']);

        $this->assertSame('application/problem+json', $response->headers()->get('Content-Type'));
        $this->assertSame('{}', $response->content());
    }
}
