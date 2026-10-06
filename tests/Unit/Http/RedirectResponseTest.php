<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\RedirectResponse;
use Formwork\Http\ResponseStatus;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RedirectResponse::class)]
final class RedirectResponseTest extends TestCase
{
    public function testRedirectResponseSetsLocationAndCustomHeaders(): void
    {
        $response = new RedirectResponse('/next', ResponseStatus::SeeOther, ['X-Redirect' => 'yes']);

        $this->assertSame('/next', $response->headers()->get('Location'));
        $this->assertSame('yes', $response->headers()->get('X-Redirect'));
        $this->assertSame(ResponseStatus::SeeOther, $response->status());
    }
}
