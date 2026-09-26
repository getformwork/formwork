<?php

namespace Formwork\Router\Events;

use Closure;
use Formwork\Data\Attributes\Setter;
use Formwork\Events\Event;
use Formwork\Router\Route;

class RouteActionResolvedEvent extends Event
{
    public function __construct(Route $route, Closure $action)
    {
        parent::__construct('routeActionResolved', ['route' => $route, 'action' => $action]);
    }

    /**
     * Get the resolved route
     */
    public function route(): Route
    {
        return $this->data['route'];
    }

    /**
     * Get the resolved route action
     */
    public function action(): Closure
    {
        return $this->data['action'];
    }

    /**
     * Set the resolved route action
     */
    #[Setter]
    public function setAction(Closure $action): void
    {
        $this->data['action'] = $action;
    }
}
