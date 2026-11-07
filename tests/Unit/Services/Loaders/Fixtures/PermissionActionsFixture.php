<?php

namespace Formwork\Tests\Unit\Services\Loaders\Fixtures;

use Formwork\Users\Attributes\Permission;

/**
 * Actions with and without a required permission
 */
final class PermissionActionsFixture
{
    #[Permission('panel.test')]
    public function restricted(): string
    {
        return 'restricted';
    }

    public function open(): string
    {
        return 'open';
    }
}
