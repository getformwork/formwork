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
        $redirectResponse = new RedirectResponse('/next', ResponseStatus::SeeOther, ['X-Redirect' => 'yes']);

        $this->assertSame('/next', $redirectResponse->headers()->get('Location'));
        $this->assertSame('yes', $redirectResponse->headers()->get('X-Redirect'));
        $this->assertSame(ResponseStatus::SeeOther, $redirectResponse->status());
    }

    public function testRedirectUsesFoundStatusAndAnEmptyBodyByDefault(): void
    {
        $redirectResponse = new RedirectResponse('/next');

        $this->assertSame(ResponseStatus::Found, $redirectResponse->status());
        $this->assertSame('', $redirectResponse->content());
    }

    public function testTheGivenUriIsNotOverriddenByAnExtraLocationHeader(): void
    {
        $redirectResponse = new RedirectResponse('/next', headers: ['Location' => '/elsewhere']);

        $this->assertSame('/next', $redirectResponse->headers()->get('Location'));
    }
}
