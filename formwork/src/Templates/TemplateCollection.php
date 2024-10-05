<?php

namespace Formwork\Templates;

use Formwork\Data\AbstractCollection;

class TemplateCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Template::class;
}
