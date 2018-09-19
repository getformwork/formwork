<?php

namespace Formwork\Admin\Utils;

use ZipArchive;

class ZipErrors
{
    public static function getMessage($code)
    {
        switch ($code) {
            case ZipArchive::ER_EXISTS:
                return Language::get('zip.error.already-exists');
            case ZipArchive::ER_INCONS:
                return Language::get('zip.error.inconsistent-archive');
            case ZipArchive::ER_INVAL:
                return Language::get('zip.error.invalid-argument');
            case ZipArchive::ER_MEMORY:
                return Language::get('zip.error.memory-failure');
            case ZipArchive::ER_NOENT:
                return Language::get('zip.error.not-found');
            case ZipArchive::ER_NOZIP:
                return Language::get('zip.error.not-zip-archive');
            case ZipArchive::ER_OPEN:
                return Language::get('zip.error.cannot-open');
            case ZipArchive::ER_READ:
            case ZipArchive::ER_SEEK:
                return Language::get('zip.error.cannot-read');
            default:
                return Language::get('zip.error.unspecified');
        }
    }
}
