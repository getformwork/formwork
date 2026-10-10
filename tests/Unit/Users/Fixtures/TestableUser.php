<?php

namespace Formwork\Tests\Unit\Users\Fixtures;

use Formwork\Authentication\Authenticator;
use Formwork\Users\User;

/**
 * User whose authenticator can be replaced, since the real one is resolved from the application
 */
final class TestableUser extends User
{
    public ?Authenticator $authenticator = null;

    protected function getAuthenticator(): Authenticator
    {
        return $this->authenticator ?? parent::getAuthenticator();
    }
}
