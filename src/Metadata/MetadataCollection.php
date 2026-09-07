<?php

namespace Formwork\Metadata;

use Formwork\Data\AbstractCollection;

/**
 * @extends AbstractCollection<Metadata>
 */
class MetadataCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Metadata::class;

    protected bool $mutable = true;

    /**
     * @param array<Metadata> $data
     */
    public function __construct(array $data)
    {
        parent::__construct();
        $this->setMultiple($data);
    }

    /**
     * Set multiple metadata
     *
     * @param array<string, string> $keysAndValues
     */
    // @phpstan-ignore method.childParameterType
    public function setMultiple(array $keysAndValues): void
    {
        // @phpstan-ignore argument.type
        parent::setMultiple($keysAndValues);
    }

    /**
     * Set a metadata
     *
     * @param string $value
     */
    // @phpstan-ignore method.childParameterType
    public function set(string $key, $value): void
    {
        $this->data[$key] = new Metadata($key, $value);
    }
}
