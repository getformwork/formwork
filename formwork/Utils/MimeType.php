<?php

namespace Formwork\Utils;

class MimeType
{
    /**
     * Default MIME type for unknown files
     *
     * @var string
     */
    protected const DEFAULT_MIME_TYPE = 'application/octet-stream';

    /**
     * Associative array containing common MIME types
     *
     * @see https://www.iana.org/assignments/media-types/media-types.xhtml
     *
     * @var array
     */
    protected const MIME_TYPES = [
        'js'       => 'application/javascript',
        'doc'      => 'application/msword',
        'pdf'      => 'application/pdf',
        'ai'       => 'application/postscript',
        'eps'      => 'application/postscript',
        'ps'       => 'application/postscript',
        'rss'      => 'application/rss+xml',
        'rtf'      => 'application/rtf',
        'xls'      => 'application/vnd.ms-excel',
        'ppt'      => 'application/vnd.ms-powerpoint',
        'pps'      => 'application/vnd.ms-powerpoint',
        'odt'      => 'application/vnd.oasis.opendocument.text',
        'pptx'     => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        '7z'       => 'application/x-7z-compressed',
        'torrent'  => 'application/x-bittorrent',
        'rar'      => 'application/x-rar-compressed',
        'tar'      => 'application/x-tar',
        'tex'      => 'application/x-tex',
        'xhtml'    => 'application/xhtml+xml',
        'xml'      => 'application/xml',
        'zip'      => 'application/zip',
        'mid'      => 'audio/midi',
        'm4a'      => 'audio/mp4',
        'mp3'      => 'audio/mpeg',
        'aif'      => 'audio/x-aiff',
        'm3u'      => 'audio/x-mpegurl',
        'wma'      => 'audio/x-ms-wma',
        'wav'      => 'audio/x-wav',
        'bmp'      => 'image/bmp',
        'gif'      => 'image/gif',
        'jpg'      => 'image/jpeg',
        'jpeg'     => 'image/jpeg',
        'png'      => 'image/png',
        'svg'      => 'image/svg+xml',
        'tiff'     => 'image/tiff',
        'tif'      => 'image/tiff',
        'psd'      => 'image/vnd.adobe.photoshop',
        'ico'      => 'image/x-icon',
        'tga'      => 'image/x-tga',
        'ics'      => 'text/calendar',
        'css'      => 'text/css',
        'csv'      => 'text/csv',
        'html'     => 'text/html',
        'htm'      => 'text/html',
        'markdown' => 'text/markdown',
        'md'       => 'text/markdown',
        'txt'      => 'text/plain',
        'log'      => 'text/plain',
        'vcf'      => 'text/x-vcard',
        '3gp'      => 'video/3gpp',
        '3g2'      => 'video/3gpp2',
        'mp4'      => 'video/mp4',
        'm4v'      => 'video/mp4',
        'mpg'      => 'video/mpeg',
        'mov'      => 'video/quicktime',
        'flv'      => 'video/x-flv',
        'asf'      => 'video/x-ms-asf',
        'wmv'      => 'video/x-ms-wmv',
        'avi'      => 'video/x-msvideo'
    ];

    /**
     * Get MIME types from file extension
     *
     * @param string $extension
     *
     * @return string
     */
    public static function fromExtension($extension)
    {
        $extension = ltrim($extension, '.');
        return self::MIME_TYPES[$extension] ?? self::DEFAULT_MIME_TYPE;
    }

    /**
     * Get MIME type from a file
     *
     * @param string $file
     *
     * @return string|null
     */
    public static function fromFile($file)
    {
        $mimeType = null;

        if (extension_loaded('fileinfo')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = @mime_content_type($file);
        }

        // Fix type for SVG images without XML declaration
        if ($mimeType === 'image/svg') {
            $mimeType = static::fromExtension('svg');
        }

        // Fix wrong type for image/svg+xml
        if ($mimeType === 'text/html') {
            $node = @simplexml_load_file($file);
            if ($node && $node->getName() === 'svg') {
                $mimeType = static::fromExtension('svg');
            }
        }

        return $mimeType ?: self::DEFAULT_MIME_TYPE;
    }

    /**
     * Get extension or array of extensions (if multiple) from a MIME type
     *
     * @param string $mimeType
     *
     * @return array|string
     */
    public static function toExtension($mimeType)
    {
        $results = array_keys(self::MIME_TYPES, $mimeType, true);
        return count($results) > 1 ? $results : array_shift($results);
    }
}
