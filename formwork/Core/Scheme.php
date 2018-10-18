<?php

namespace Formwork\Core;

use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class Scheme extends DataGetter
{
    /**
     * Create a new Scheme instance
     *
     * @param string $template
     */
    public function __construct($template)
    {
        $path = Formwork::instance()->site()->templatesPath() . 'schemes' . DS;
        $filename = $path . $template . '.yml';

        FileSystem::assert($filename);
        parent::__construct(YAML::parseFile($filename));

        if (!$this->has('title')) {
            $this->data['title'] = $template;
        }
    }

    /**
     * Get scheme title
     *
     * @return string
     */
    public function title()
    {
        return $this->get('title');
    }

    /**
     * Get whether scheme is default
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->get('default', false);
    }
}
