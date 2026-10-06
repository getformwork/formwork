<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\ResponseStatus;
use Formwork\Http\Utils\Header;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Header::class)]
final class ResponseHeaderUtilsTest extends TestCase
{
    public function testHeaderUtilityBuildsAndSendsValues(): void
    {
        $this->assertSame('attachment; filename=sample.txt', Header::make(['attachment', 'filename' => 'sample.txt']));
        Header::send('X-Formwork-Test', ' value ');
        Header::contentType('text/plain');
        Header::sendStatus(ResponseStatus::OK);
        $this->addToAssertionCount(1);
    }

    public function testNotFoundSendsTheNotFoundStatus(): void
    {
        Header::notFound();
        $this->addToAssertionCount(1);
    }

    public function testRedirectRejectsNonRedirectionStatuses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Header::redirect('/next', ResponseStatus::OK);
    }
}
