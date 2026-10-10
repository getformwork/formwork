<?php

namespace Formwork\Tests\Unit\Http;

use Formwork\Http\ServerData;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ServerData::class)]
final class ServerDataTest extends TestCase
{
    public function testServerDataExtractsHttpHeaders(): void
    {
        $server = new ServerData([
            'HTTP_HOST'      => 'example.test',
            'CONTENT_TYPE'   => 'application/json',
            'CONTENT_LENGTH' => '12',
            'REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame(['HOST' => 'example.test', 'CONTENT_TYPE' => 'application/json', 'CONTENT_LENGTH' => '12'], $server->getHeaders());
    }
}
