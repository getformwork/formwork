<?php

namespace Formwork\Panel\Events;

use Formwork\Events\Event;
use Formwork\Users\User;

/**
 * @since 2.3.0
 */
class PanelLoggedOutEvent extends Event
{
    public function __construct(User $user)
    {
        parent::__construct('panelLoggedOut', ['user' => $user]);
    }

    /**
     * Get the logged out user
     */
    public function user(): User
    {
        return $this->data['user'];
    }
}
