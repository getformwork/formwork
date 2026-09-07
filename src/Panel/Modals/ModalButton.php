<?php

namespace Formwork\Panel\Modals;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Translations\Translation;
use Formwork\Utils\Str;
use UnexpectedValueException;

class ModalButton implements Arrayable
{
    use DataArrayable;

    protected string $action;

    protected ?string $icon = null;

    protected string $label;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data,
        protected Translation $translation,
    ) {
        $this->data = [...$this->defaults(), ...$data];

        if ($this->data['label'] === null) {
            throw new UnexpectedValueException('Unexpected empty button label');
        }

        $this->translate();
    }

    public function action(): string
    {
        return $this->data['action'];
    }

    public function formType(): string
    {
        return $this->data['action'] === 'submit' ? 'submit' : 'button';
    }

    public function icon(): ?string
    {
        return $this->data['icon'];
    }

    public function label(): string
    {
        return $this->data['label'];
    }

    public function variant(): string
    {
        return $this->data['variant'];
    }

    public function align(): string
    {
        return $this->data['align'];
    }

    public function command(): ?string
    {
        return $this->data['command'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'action'  => 'submit',
            'label'   => null,
            'icon'    => null,
            'variant' => 'accent',
            'align'   => 'left',
            'command' => null,
        ];
    }

    protected function translate(): void
    {
        $this->data['label'] = Str::interpolate($this->data['label'], fn($key) => $this->translation->translate($key));
    }
}
