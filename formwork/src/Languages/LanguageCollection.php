<?php

namespace Formwork\Languages;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\Arr;

class LanguageCollection extends AbstractCollection
{
    protected ?string $dataType = Language::class;

    protected bool $associative = true;

    public function __construct(array $data)
    {
        parent::__construct(Arr::fromEntries(Arr::map($data, fn ($code) => [$code, new Language($code)])));
    }
}
