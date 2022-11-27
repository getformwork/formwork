<?php

namespace Formwork\Fields\Layout;

class Layout
{
    protected string $type;

    protected SectionCollection $sections;

    public function __construct(array $data)
    {
        $this->type = $data['type'];
        $this->sections = new SectionCollection($data['sections'] ?? []);
    }

    /** Get layout type
     *
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get layout sections
     */
    public function sections()
    {
        return $this->sections;
    }
}
