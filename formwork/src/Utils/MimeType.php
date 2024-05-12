<?php

namespace Formwork\Utils;

use DOMDocument;
use DOMElement;
use Formwork\Traits\StaticClass;
use RuntimeException;

class MimeType
{
    use StaticClass;

    /**
     * Default MIME type for unknown files
     */
    protected const DEFAULT_MIME_TYPE = 'application/octet-stream';

    /**
     * Associative array containing common MIME types
     *
     * @see https://www.iana.org/assignments/media-types/media-types.xhtml
     */
    protected const MIME_TYPES = [
        'atom'     => 'application/atom+xml',
        'epub'     => 'application/epub+zip',
        'gz'       => 'application/gzip',
        'js'       => 'application/javascript',
        'json'     => 'application/json',
        'doc'      => 'application/msword',
        'pdf'      => 'application/pdf',
        'ai'       => 'application/postscript',
        'eps'      => 'application/postscript',
        'ps'       => 'application/postscript',
        'rss'      => 'application/rss+xml',
        'rtf'      => 'application/rtf',
        'xls'      => 'application/vnd.ms-excel',
        'otf'      => 'application/vnd.ms-opentype',
        'ppt'      => 'application/vnd.ms-powerpoint',
        'pps'      => 'application/vnd.ms-powerpoint',
        'odp'      => 'application/vnd.oasis.opendocument.presentation',
        'otp'      => 'application/vnd.oasis.opendocument.presentation-template',
        'ods'      => 'application/vnd.oasis.opendocument.spreadsheet',
        'ots'      => 'application/vnd.oasis.opendocument.spreadsheet-template',
        'odt'      => 'application/vnd.oasis.opendocument.text',
        'odm'      => 'application/vnd.oasis.opendocument.text-master',
        'ott'      => 'application/vnd.oasis.opendocument.text-template',
        'pptx'     => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        '7z'       => 'application/x-7z-compressed',
        'torrent'  => 'application/x-bittorrent',
        'bz2'      => 'application/x-bzip2',
        'dvi'      => 'application/x-dvi',
        'ttf'      => 'application/x-font-ttf',
        'woff'     => 'application/x-font-woff',
        'latex'    => 'application/x-latex',
        'rar'      => 'application/x-rar-compressed',
        'tar'      => 'application/x-tar',
        'tex'      => 'application/x-tex',
        'xhtml'    => 'application/xhtml+xml',
        'xml'      => 'application/xml',
        'zip'      => 'application/zip',
        'flac'     => 'audio/flac',
        'mid'      => 'audio/midi',
        'midi'     => 'audio/midi',
        'm4a'      => 'audio/mp4',
        'mp4a'     => 'audio/mp4',
        'mp3'      => 'audio/mpeg',
        'ogg'      => 'audio/ogg',
        'oga'      => 'audio/ogg',
        'aac'      => 'audio/x-aac',
        'aif'      => 'audio/x-aiff',
        'm3u'      => 'audio/x-mpegurl',
        'wma'      => 'audio/x-ms-wma',
        'wav'      => 'audio/x-wav',
        'bmp'      => 'image/bmp',
        'gif'      => 'image/gif',
        'jpg'      => 'image/jpeg',
        'jpeg'     => 'image/jpeg',
        'jpe'      => 'image/jpeg',
        'png'      => 'image/png',
        'svg'      => 'image/svg+xml',
        'tiff'     => 'image/tiff',
        'tif'      => 'image/tiff',
        'psd'      => 'image/vnd.adobe.photoshop',
        'webp'     => 'image/webp',
        'ico'      => 'image/x-icon',
        'tga'      => 'image/x-tga',
        'ics'      => 'text/calendar',
        'css'      => 'text/css',
        'csv'      => 'text/csv',
        'html'     => 'text/html',
        'htm'      => 'text/html',
        'md'       => 'text/markdown',
        'markdown' => 'text/markdown',
        'ini'      => 'text/plain',
        'txt'      => 'text/plain',
        'log'      => 'text/plain',
        'vcf'      => 'text/x-vcard',
        'yml'      => 'text/yaml',
        'yaml'     => 'text/yaml',
        '3gp'      => 'video/3gpp',
        '3g2'      => 'video/3gpp2',
        'mp4'      => 'video/mp4',
        'm4v'      => 'video/mp4',
        'mp4v'     => 'video/mp4',
        'mpg4'     => 'video/mp4',
        'mpg'      => 'video/mpeg',
        'mpeg'     => 'video/mpeg',
        'mpe'      => 'video/mpeg',
        'ogv'      => 'video/ogg',
        'mov'      => 'video/quicktime',
        'webm'     => 'video/webm',
        'flv'      => 'video/x-flv',
        'mkv'      => 'video/x-matroska',
        'asf'      => 'video/x-ms-asf',
        'wmv'      => 'video/x-ms-wmv',
        'avi'      => 'video/x-msvideo',
    ];

    /**
     * Get MIME types from file extension
     */
    public static function fromExtension(string $extension): string
    {
        $extension = ltrim($extension, '.');
        return self::MIME_TYPES[$extension] ?? self::DEFAULT_MIME_TYPE;
    }

    /**
     * Get MIME type from a file
     */
    public static function fromFile(string $file): string
    {
        $mimeType = null;

        if (!extension_loaded('fileinfo')) {
            throw new RuntimeException(sprintf('%s() requires the extension "fileinfo" to be enabled', __METHOD__));
        }

        if ($finfo = finfo_open(FILEINFO_MIME_TYPE)) {
            $mimeType = finfo_file($finfo, $file);
            finfo_close($finfo);

            // Fix type for SVG images without XML declaration
            if ($mimeType === 'image/svg') {
                $mimeType = static::fromExtension('svg');
            }

            // Fix wrong type for image/svg+xml
            if ($mimeType === 'text/html') {
                $domDocument = new DOMDocument();
                $domDocument->load($file);
                $node = $domDocument->documentElement;
                if ($node instanceof DOMElement && $node->nodeName === 'svg') {
                    $mimeType = static::fromExtension('svg');
                }
            }
        }

        return $mimeType ?: self::DEFAULT_MIME_TYPE;
    }

    /**
     * Get an array of extensions associated to the given MIME type
     *
     * @return array<string>
     */
    public static function getAssociatedExtensions(string $mimeType): array
    {
        return array_keys(self::MIME_TYPES, $mimeType, true);
    }

    /**
     * Get the extension associated to a MIME type
     */
    public static function toExtension(string $mimeType): ?string
    {
        return static::getAssociatedExtensions($mimeType)[0] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function extensionTypes(): array
    {
        return Arr::mapKeys(Arr::map(self::MIME_TYPES, fn ($value, $key) => sprintf('.%s (%s)', $key, $value)), fn ($key) => '.' . $key);
    }
}
