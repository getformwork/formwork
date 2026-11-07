<?php

namespace Formwork\Users\Attributes;

use Attribute;

/**
 * Permission the logged-in user must have to run a controller action or any other route action
 *
 * @since 2.4.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
final readonly class Permission
{
    public function __construct(
        public string $name,
    ) {}
}
