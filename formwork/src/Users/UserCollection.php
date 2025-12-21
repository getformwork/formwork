<?php

namespace Formwork\Users;

use Formwork\Data\AbstractCollection;

class UserCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = User::class;

    protected bool $mutable = true;

    /**
     * @param array<string, User> $data
     */
    public function __construct(
        array $data,
        protected RoleCollection $roleCollection,
    ) {
        parent::__construct($data);
    }

    /**
     * Get roles collection
     */
    public function roles(): RoleCollection
    {
        return $this->roleCollection;
    }

    /**
     * Get all available roles as an array of role titles
     *
     * @return array<string, string>
     */
    public function availableRoles(): array
    {
        return $this->roleCollection->everyItem()->title()->toArray();
    }

    /**
     * Get logged in user or null if no user is authenticated
     */
    public function loggedIn(): ?User
    {
        return $this->find(fn(User $user): bool => $user->isLoggedIn());
    }
}
