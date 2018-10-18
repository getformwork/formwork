<?php

namespace Formwork\Cache;

use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;

class SiteCache extends FilesCache
{
    /**
     * @inheritdoc
     */
    public function isValid($key)
    {
        $lastModified = FileSystem::lastModifiedTime($this->getFile($key));
        $expires = $lastModified + $this->time;
        return !Formwork::instance()->site()->modifiedSince($lastModified) && time() < $expires;
    }
}
