<?php

namespace Formwork\Languages;

use Formwork\Data\AbstractCollection;
use Formwork\Utils\Arr;

/**
 * @extends AbstractCollection<Language>
 */
class LanguageCollection extends AbstractCollection
{
    protected ?string $dataType = Language::class;

    protected bool $associative = true;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        parent::__construct(Arr::fromEntries(Arr::map($data, fn(string $code) => [$code, new Language($code)])));
    }
}
