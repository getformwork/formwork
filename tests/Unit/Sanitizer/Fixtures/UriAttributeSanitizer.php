<?php

namespace Formwork\Tests\Unit\Sanitizer\Fixtures;

use Formwork\Sanitizer\DomSanitizer;

final class UriAttributeSanitizer extends DomSanitizer
{
    protected array $uriAttributes = ['href', 'src'];
}
