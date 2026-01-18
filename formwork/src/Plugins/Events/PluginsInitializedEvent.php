<?php

namespace Formwork\Plugins\Events;

use Formwork\Events\Event;
use Formwork\Plugins\Plugins;

/**
 * @since 2.3.0
 */
class PluginsInitializedEvent extends Event
{
    public function __construct(Plugins $plugins)
    {
        parent::__construct('pluginsInitialized', ['plugins' => $plugins]);
    }

    /**
     * Get the plugins instance
     */
    public function plugins(): Plugins
    {
        return $this->data['plugins'];
    }
}
