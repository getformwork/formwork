<?php

namespace Formwork\Core;

use Formwork\Data\DataGetter;
use Formwork\Utils\FileSystem;
use Spyc;

class Scheme extends DataGetter
{
    public function __construct($template)
    {
        $path = Formwork::instance()->site()->templatesPath() . 'schemes' . DS;
        $filename = $path . $template . '.yml';

        FileSystem::assert($filename);
        $this->data = Spyc::YAMLLoad($filename);

        if (!$this->has('title')) {
            $this->data['title'] = $template;
        }
    }

    public function title()
    {
        return $this->get('title');
    }

    public function default()
    {
        return $this->get('default', false);
    }
}
