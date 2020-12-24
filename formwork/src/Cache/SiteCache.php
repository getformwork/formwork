<?php

namespace Formwork\Cache;

use Formwork\Formwork;
use Formwork\Utils\FileSystem;

class SiteCache extends FilesCache
{
    /**
     * @inheritdoc
     */
    protected function isValid(string $key): bool
    {
        $lastModified = FileSystem::lastModifiedTime($this->getFile($key));
        return parent::isValid($key) && !Formwork::instance()->site()->modifiedSince($lastModified);
    }
}
