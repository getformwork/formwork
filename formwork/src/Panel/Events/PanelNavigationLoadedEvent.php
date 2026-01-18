<?php

namespace Formwork\Panel\Events;

use Formwork\Events\Event;
use Formwork\Panel\Navigation\NavigationItemCollection;
use Formwork\Translations\Translation;

/**
 * @since 2.3.0
 */
class PanelNavigationLoadedEvent extends Event
{
    public function __construct(NavigationItemCollection $navigationItemCollection, Translation $translation)
    {
        parent::__construct('panelNavigationLoaded', ['navigation' => $navigationItemCollection, 'translation' => $translation]);
    }

    /**
     * Get the panel navigation collection
     */
    public function navigation(): NavigationItemCollection
    {
        return $this->data['navigation'];
    }

    /**
     * Get the translations instance
     */
    public function translation(): Translation
    {
        return $this->data['translation'];
    }
}
