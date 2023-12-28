<?php

namespace Formwork\Panel\Users;

use Formwork\Data\AbstractCollection;

class UserCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = User::class;

    /**
     * @param array<string, User>  $data
     * @param array<string, mixed> $roles
     */
    public function __construct(array $data, protected array $roles)
    {
        parent::__construct($data);
    }

    /**
     * Get all available roles
     *
     * @return array<string, mixed>
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
