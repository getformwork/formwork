<?php

namespace Formwork\Utils;

class MimeType
{
    protected static $data = array(
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
        'm4a'      => 'audio/mp4a-latm',
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
        'm4v'      => 'video/x-m4v',
        'asf'      => 'video/x-ms-asf',
        'wmv'      => 'video/x-ms-wmv',
        'avi'      => 'video/x-msvideo'
    );

    public static function fromExtension($extension)
    {
        $extension = ltrim($extension, '.');
        return isset(static::$data[$extension]) ? static::$data[$extension] : null;
    }

    public static function toExtension($mimeType)
    {
        $results = array();
        foreach (static::$data as $ext => $mime) {
            if ($mime === $mimeType) {
                $results[] = $ext;
            }
        }
        return count($results) < 2 ? array_shift($results) : $results;
    }
}
