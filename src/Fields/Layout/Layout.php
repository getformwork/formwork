<?php

namespace Formwork\Fields\Layout;

use Formwork\Translations\Translation;

class Layout
{
    /**
     * Layout type
     */
    protected string $type = 'sections';

    /**
     * Layout sections collection
     */
    protected SectionCollection $sections;

    /**
     * Layout tabs collection
     */
    protected TabCollection $tabs;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data, protected Translation $translation) {}

    /**
     * Get layout type
     *
     * @deprecated Type property is no longer used and will be removed in Formwork 3.0
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get layout sections
     */
    public function sections(): SectionCollection
    {
        return $this->sections ??= (new SectionCollection($this->data['sections'] ?? []))
            ->each(fn(Section $section) => $section->setTranslation($this->translation))
            ->sortBy('order');
    }

    /**
     * Get layout tabs
     *
     * @since 2.2.0
     */
    public function tabs(): TabCollection
    {
        return $this->tabs ??= (new TabCollection($this->data['tabs'] ?? []))
            ->each(fn(Tab $tab) => $tab->setTranslation($this->translation))
            ->sortBy('order');
    }
}
