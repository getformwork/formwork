<?php

namespace Formwork\Panel\Users;

use Formwork\Data\AbstractCollection;

class UserCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = User::class;

    protected array $roles;

    public function __construct(array $data, array $roles)
    {
        parent::__construct($data);
        $this->roles = $roles;
    }

    /**
     * Get all available roles
     */
    public function availableRoles(): array
    {
        $roles = [];
        foreach ($this->roles as $role => $data) {
            $roles[$role] = $data['title'];
        }
        return $roles;
    }
}
