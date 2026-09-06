<?php

namespace Formwork\Model\Attributes;

use Attribute;
use Formwork\Data\Attributes\Getter;

trigger_error(sprintf('%s is deprecated since Formwork 2.4.0. Use the new %s instead', ReadonlyModelProperty::class, Getter::class), E_USER_DEPRECATED);

/**
 * @deprecated since 2.4.0. Use `Formwork\Data\Attributes\Getter` instead
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ReadonlyModelProperty {}
