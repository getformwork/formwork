<?php

namespace Formwork\Cache;

use Formwork\Formwork;
use Formwork\Utils\FileSystem;

class SiteCache extends FilesCache
{
    /**
     * @inheritdoc
     */
    protected function hasExpired(string $key): bool
    {
        if (parent::hasExpired($key)) {
            return true;
        }
        $lastModified = FileSystem::lastModifiedTime($this->getFile($key));
        return Formwork::instance()->site()->modifiedSince($lastModified);
    }
}
