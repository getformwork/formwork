<?php

namespace Formwork\Tests\Unit\Model\Fixtures;

use Formwork\Data\Attributes\Getter;
use Formwork\Data\Attributes\Setter;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Model\Model;
use Formwork\Schemes\Scheme;

/**
 * @extends Model<array<string, mixed>>
 */
class ModelFixture extends Model
{
    protected const string MODEL_IDENTIFIER = 'fixture';

    #[Getter]
    #[Setter]
    public string $slug = 'initial-slug';

    #[Getter(key: 'alias', export: false)]
    public string $internalAlias = 'secret-alias';

    /**
     * Property without accessor attributes (deprecated implicit access)
     */
    public string $legacy = 'legacy-value';

    #[ReadonlyModelProperty]
    public string $locked = 'locked-value';

    /**
     * Property with an implicit setter method
     */
    public string $shout = 'quiet';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data, Scheme $scheme)
    {
        $this->data = $data;
        $this->scheme = $scheme;
    }

    #[Getter]
    public function summary(): string
    {
        return 'Summary of ' . ($this->data['title'] ?? 'nothing');
    }

    #[Getter(export: false)]
    public function hidden(): string
    {
        return 'hidden-value';
    }

    #[Setter]
    public function setLabel(string $label): void
    {
        $this->data['label'] = strtoupper($label);
    }

    #[Getter]
    #[Setter]
    public function getMode(): string
    {
        return $this->data['mode'] ?? 'default-mode';
    }

    #[Setter(key: 'mode')]
    public function changeMode(string $mode): void
    {
        $this->data['mode'] = $mode;
    }

    public function legacy(): string
    {
        return 'legacy-from-method';
    }

    public function setShout(string $value): void
    {
        $this->shout = strtoupper($value);
    }
}
