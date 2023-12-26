<?php

namespace Formwork\Panel\Users;

use Formwork\Data\AbstractCollection;

class UserCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = User::class;

    /**
     * @var array<string, mixed>
     */
    protected array $roles;

    /**
     * @param array<string, User>  $data
     * @param array<string, mixed> $roles
     */
    public function __construct(array $data, array $roles)
    {
        parent::__construct($data);
        $this->roles = $roles;
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
