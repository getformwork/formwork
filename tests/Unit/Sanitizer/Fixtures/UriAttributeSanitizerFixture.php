<?php

namespace Formwork\Tests\Unit\Sanitizer\Fixtures;

use Formwork\Sanitizer\DomSanitizer;

final class UriAttributeSanitizerFixture extends DomSanitizer
{
    protected array $uriAttributes = ['href', 'src'];
}
