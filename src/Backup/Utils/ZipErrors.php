<?php

namespace Formwork\Backup\Utils;

use Formwork\Traits\StaticClass;
use ZipArchive;

final class ZipErrors
{
    use StaticClass;

    /**
     * Human-readable ZipArchive error messages
     *
     * @var array<int, string>
     */
    public const array ERROR_MESSAGES = [
        ZipArchive::ER_MULTIDISK   => 'Multi-disk zip archives not supported',
        ZipArchive::ER_RENAME      => 'Renaming temporary file failed',
        ZipArchive::ER_CLOSE       => 'Closing zip archive failed',
        ZipArchive::ER_SEEK        => 'Seek error',
        ZipArchive::ER_READ        => 'Read error',
        ZipArchive::ER_WRITE       => 'Write error',
        ZipArchive::ER_CRC         => 'CRC error',
        ZipArchive::ER_ZIPCLOSED   => 'Containing zip archive was closed',
        ZipArchive::ER_NOENT       => 'No such file',
        ZipArchive::ER_EXISTS      => 'File already exists',
        ZipArchive::ER_OPEN        => 'Cannot open file',
        ZipArchive::ER_TMPOPEN     => 'Failure to create temporary file',
        ZipArchive::ER_ZLIB        => 'Zlib error',
        ZipArchive::ER_MEMORY      => 'Memory allocation failure',
        ZipArchive::ER_CHANGED     => 'Entry has been changed',
        ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
        ZipArchive::ER_EOF         => 'Premature EOF',
        ZipArchive::ER_INVAL       => 'Invalid argument',
        ZipArchive::ER_NOZIP       => 'Not a zip archive',
        ZipArchive::ER_INTERNAL    => 'Internal error',
        ZipArchive::ER_INCONS      => 'Zip archive inconsistent',
        ZipArchive::ER_REMOVE      => 'Cannot remove file',
        ZipArchive::ER_DELETED     => 'Entry has been deleted',
    ];

    /**
     * ZipArchive errors language strings
     *
     * @var array<int, string>
     */
    public const array ERROR_LANGUAGE_STRINGS = [
        ZipArchive::ER_MULTIDISK   => 'zip.error.unspecified',
        ZipArchive::ER_RENAME      => 'zip.error.unspecified',
        ZipArchive::ER_CLOSE       => 'zip.error.unspecified',
        ZipArchive::ER_SEEK        => 'zip.error.cannotRead',
        ZipArchive::ER_READ        => 'zip.error.cannotRead',
        ZipArchive::ER_WRITE       => 'zip.error.unspecified',
        ZipArchive::ER_CRC         => 'zip.error.unspecified',
        ZipArchive::ER_ZIPCLOSED   => 'zip.error.unspecified',
        ZipArchive::ER_NOENT       => 'zip.error.notFound',
        ZipArchive::ER_EXISTS      => 'zip.error.alreadyExists',
        ZipArchive::ER_OPEN        => 'zip.error.cannotOpen',
        ZipArchive::ER_TMPOPEN     => 'zip.error.unspecified',
        ZipArchive::ER_ZLIB        => 'zip.error.unspecified',
        ZipArchive::ER_MEMORY      => 'zip.error.memoryFailure',
        ZipArchive::ER_CHANGED     => 'zip.error.unspecified',
        ZipArchive::ER_COMPNOTSUPP => 'zip.error.unspecified',
        ZipArchive::ER_EOF         => 'zip.error.unspecified',
        ZipArchive::ER_INVAL       => 'zip.error.invalidArgument',
        ZipArchive::ER_NOZIP       => 'zip.error.notZipArchive',
        ZipArchive::ER_INTERNAL    => 'zip.error.unspecified',
        ZipArchive::ER_INCONS      => 'zip.error.inconsistentArchive',
        ZipArchive::ER_REMOVE      => 'zip.error.unspecified',
        ZipArchive::ER_DELETED     => 'zip.error.unspecified',
    ];
}
