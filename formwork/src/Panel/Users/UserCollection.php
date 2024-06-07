<?php

namespace Formwork\Panel\Users;

use Formwork\Data\AbstractCollection;

class UserCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = User::class;

    protected bool $mutable = true;

    /**
     * @param array<string, User> $data
     */
    public function __construct(array $data, protected RoleCollection $roleCollection)
    {
        parent::__construct($data);
    }

    /**
     * Get all available roles
     *
     * @return array<string, string>
     */
    public function availableRoles(): array
    {
        return $this->roleCollection->everyItem()->title()->toArray();
    }
}
