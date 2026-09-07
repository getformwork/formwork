<?php

namespace Formwork\Panel\Modals;

class Modals extends ModalCollection
{
    public function __construct(
        private ModalFactory $modalFactory
    ) {}

    /**
     * Add a modal by name. If the modal is already present, it won't be added again
     *
     * @param string $name
     */
    // @phpstan-ignore method.childParameterType
    public function add(mixed $name): void
    {
        if (!$this->has($name)) {
            $this->set($name, $this->modalFactory->make($name));
        }
    }
}
