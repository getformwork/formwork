<?php

namespace Formwork\Templates;

use Formwork\Data\AbstractCollection;

/**
 * @extends AbstractCollection<Template>
 */
class TemplateCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Template::class;
}
