<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\JsonResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(JsonResponse::class)]
final class JsonResponseTest extends TestCase
{
    public function testSuccessAndErrorFactoriesBuildJsonResponses(): void
    {
        $success = JsonResponse::success('Done', ResponseStatus::Created, ['id' => 7]);
        $error = JsonResponse::error('Invalid', ResponseStatus::UnprocessableEntity, ['field' => 'name']);

        $this->assertStringContainsString('"status":"success"', $success->content());
        $this->assertStringContainsString('"status":"error"', $error->content());
        $this->assertSame(ResponseStatus::Created, $success->status());
        $this->assertSame(ResponseStatus::UnprocessableEntity, $error->status());
        $this->assertSame('application/json; charset=utf-8', $success->headers()->get('Content-Type'));
    }
}
