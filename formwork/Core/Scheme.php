<?php

namespace Formwork\Core;

use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class Scheme extends DataGetter
{
    public function __construct($template)
    {
        $path = Formwork::instance()->site()->templatesPath() . 'schemes' . DS;
        $filename = $path . $template . '.yml';

        FileSystem::assert($filename);
        $this->data = YAML::parseFile($filename);

        if (!$this->has('title')) {
            $this->data['title'] = $template;
        }
    }

    public function title()
    {
        return $this->get('title');
    }

    public function isDefault()
    {
        return $this->get('default', false);
    }
}
