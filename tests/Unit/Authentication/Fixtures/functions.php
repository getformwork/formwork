<?php

namespace Formwork\Authentication;

use Formwork\Tests\Unit\Authentication\Fixtures\NativeFunctionsFixture;

function usleep(int $microseconds): void
{
    NativeFunctionsFixture::usleep($microseconds);
}
