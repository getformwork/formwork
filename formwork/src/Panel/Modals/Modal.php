<?php

namespace Formwork\Panel\Modals;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Translations\Translation;
use Formwork\Utils\Arr;
use Formwork\Utils\Str;
use UnexpectedValueException;

class Modal implements Arrayable
{
    use DataArrayable;

    protected ModalButtonCollection $buttons;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected string $id, array $data, protected Translation $translation, protected FieldFactory $fieldFactory)
    {
        $this->data = [...$this->defaults(), ...$data];

        if ($this->data['title'] === null) {
            throw new UnexpectedValueException('Unexpected missing title');
        }

        $this->translate();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {

        return $this->data['title'];
    }

    public function message(): ?string
    {
        return $this->data['message'] ?? null;
    }

    public function action(): ?string
    {
        return $this->data['action'] ?? null;
    }

    public function size(): string
    {
        return $this->data['size'];
    }

    public function fields(): FieldCollection
    {
        $fieldCollection = new FieldCollection();

        $fieldCollection->setMultiple(Arr::map($this->data['fields'] ?? [], fn ($data, $name) => $this->fieldFactory->make($name, $data, $fieldCollection)));

        return $fieldCollection;
    }

    public function buttons(): ModalButtonCollection
    {
        if (!isset($this->buttons)) {
            $this->buttons = new ModalButtonCollection(Arr::map($this->data['buttons'] ?? [], fn (array $data) => new ModalButton($data, $this->translation)));
        }
        return $this->buttons;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'title'   => null,
            'message' => null,
            'action'  => null,
            'size'    => 'small',
        ];
    }

    protected function translate(): void
    {
        $this->data['title'] = Str::interpolate($this->data['title'], fn ($key) => $this->translation->translate($key));

        if (isset($this->data['message'])) {
            $this->data['message'] = Str::interpolate($this->data['message'], fn ($key) => $this->translation->translate($key));
        }
    }
}
