<?php

namespace Formwork\Panel\Events;

use Formwork\Events\Event;
use Formwork\Http\Request;
use Formwork\Users\User;

/**
 * @since 2.3.0
 */
class PanelLoggedInEvent extends Event
{
    public function __construct(User $user, Request $request)
    {
        parent::__construct('panelLoggedIn', ['user' => $user, 'request' => $request]);
    }

    /**
     * Get the logged in user
     */
    public function user(): User
    {
        return $this->data['user'];
    }

    /**
     * Get the request instance
     */
    public function request(): Request
    {
        return $this->data['request'];
    }
}
