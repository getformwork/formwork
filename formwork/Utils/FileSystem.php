<?php

namespace Formwork\Utils;
use Formwork\Utils\MimeType;

class FileSystem {

    protected static $ignore = array('.', '..');

    protected static $units = array('B', 'KB', 'MB', 'GB', 'TB');

    public static function root() {
        return defined(ROOT_PATH) ? ROOT_PATH : $_SERVER['DOCUMENT_ROOT'];
    }

    public static function dirname($path) {
        return dirname($path);
    }

    public static function basename($path, $extension = null) {
        return basename($path, $extension);
    }

    public static function name($file) {
        $basename = static::basename($file);
        $pos = strrpos($basename, '.');
        return $pos !== false ? substr($basename, 0, $pos) : $basename;
    }

    public static function extension($file) {
        $extension = substr(static::basename($file), strlen(static::name($file)) + 1);
        return $extension !== false ? $extension : '';
    }

    public static function mimeType($file) {
        if (function_exists('mime_content_type')) {
            return mime_content_type($file);
        }
        return MimeType::fromExtension(static::extension($file));
    }

    public static function exists($path) {
        return @file_exists($path);
    }

    public static function assert($path, $value = true) {
        if ($value === true && !static::exists($path)) {
            throw new \RuntimeException('File not found: ' . $path);
        }
        if ($value === false && static::exists($path)) {
            throw new \RuntimeException('File ' . $path . ' already exists!');
        }
        return true;
    }

    public static function accessTime($file) {
        static::assert($file);
        return @fileatime($file);
    }

    public static function creationTime($file) {
        static::assert($file);
        return @filectime($file);
    }

    public static function lastModifiedTime($file) {
        static::assert($file);
        return @filemtime($file);
    }

    public static function directoryModifiedSince($directory, $time) {
        if (static::lastModifiedTime($directory) > $time) return true;
        foreach (static::list($directory) as $item) {
            $path = static::normalize($directory) . $item;
            if (static::lastModifiedTime($path) > $time) return true;
            if (static::isDirectory($path) && static::directoryModifiedSince($path, $time)) {
                return true;
            }
        }
        return false;
    }

    public static function size($file, $unit = true) {
        static::assert($file);
        $bytes = @filesize($file);
        return $unit ? static::bytesToSize($bytes) : $bytes;
    }

    public function directorySize($path, $unit = true) {
        $path = static::normalize($path);
        static::assert($path);
        $bytes = 0;
        foreach (static::list($path, true) as $item) {
            if (static::isFile($path . $item)) {
                $bytes += static::size($path . $item, false);
            } else {
                $bytes += static::directorySize($path . $item, false);
            }
        }
        return $unit ? static::bytesToSize($bytes) : $bytes;
    }

    public static function mode($file) {
        static::assert($file);
        return @fileperms($file);
    }

    public static function isVisible($path) {
        return static::basename($path)[0] !== '.';
    }

    public static function isReadable($file) {
        static::assert($file);
        return @is_readable($file);
    }

    public static function isWritable($file) {
        static::assert($file);
        return @is_writable($file);
    }

    public static function isFile($file) {
        static::assert($file);
        return @is_file($file);
    }

    public static function isDirectory($directory) {
        static::assert($directory);
        return @is_dir($directory);
    }

    public static function delete($path, $recursive = false) {
        static::assert($path);
        if (static::isFile($path)) {
            return @unlink($path);
        } else {
            if ($recursive) {
                foreach(static::list($path, true) as $file) {
                    static::delete($path . DS . $file, true);
                }
            }
            return @rmdir($path);
        }
    }

    public static function copy($source, $destination, $overwrite = false) {
        static::assert($source);
        static::assert($destination, $overwrite);
        return @copy($source, $destination);
    }

    public static function move($source, $destination, $overwrite = false) {
        static::assert($source);
        if (!$overwrite) static::assert($destination, false);
        return @rename($source, $destination);
    }

    public static function moveDirectory($source, $destination, $overwrite = false) {
        $source = static::normalize($source);
        $destination = static::normalize($destination);
        if (!$overwrite) static::assert($destination, false);
        if (!static::exists($destination)) static::createDirectory($destination);
        foreach (static::list($source, true) as $item) {
            if (static::isFile($source . $item)) {
                static::move($source . $item, $destination . $item);
            } else {
                static::moveDirectory($source . $item, $destination . $item);
            }
        }
        static::delete($source, true);
    }

    public static function swap($path1, $path2) {
        $temp = static::dirname($path1) . DS . static::temporaryName('temp.');
        static::move($path1, $temp);
        static::move($path2, $path1);
        static::move($temp, $path2);
        return true;
    }

    public static function read($file) {
        static::assert($file);
        return file_get_contents($file);
    }

    public static function write($file, $content, $append = false) {
        $flag = $append ? FILE_APPEND : null;
        return (file_put_contents($file, $content, $flag) !== false) ? true : false;
    }

    public static function createFile($file) {
        static::assert($file, false);
        return static::write($file, '');
    }

    public static function createDirectory($directory) {
        static::assert($directory, false);
        return @mkdir($directory);
    }

    public static function create($file) {
        return static::createFile($file);
    }

    public static function normalize($path) {
        return rtrim($path, DS) . DS;
    }

    public static function list($path = null, $all = false) {
        if (empty($path)) $path = static::root();
        static::assert($path);
        if (!static::isDirectory($path)) {
            throw new \RuntimeException('Unable to list: ' . $path . '. Specified path is not a directory.');
        }
        $items = @scandir($path);
        if (!is_array($items)) return array();
        $items = array_diff($items, static::$ignore);
        if (!$all) $items = array_filter($items, array(static::class, 'isVisible'));
        return $items;
    }

    public static function listFiles($path = null, $all = false) {
        $path = static::normalize($path);
        return array_filter(static::list($path, $all), function ($item) use ($path) {
            return static::isFile($path . $item);
        });
    }

    public static function listDirectories($path = null, $all = false) {
        $path = static::normalize($path);
        return array_filter(static::list($path, $all), function ($item) use ($path) {
            return static::isDirectory($path . $item);
        });
    }

    public static function glob($pattern) {
        if (func_num_args() == 1) {
            return glob($pattern);
        } elseif (func_num_args() >= 2) {
            if (is_int(func_get_args()[1])) {
                $flags = func_get_args()[1];
                return @glob($pattern, $flags);
            } else {
                $context = static::normalize(func_get_args()[1]);
            }
            if (static::isDirectory($context)) {
                if (func_num_args() == 2) {
                    return @glob($context . $pattern);
                } else {
                    $flags = func_get_args()[2];
                    return @glob($context . $pattern, $flags);
                }
            }
            throw new \RuntimeException('Invalid glob context');
        }
    }

    public static function touch($path) {
        static::assert($path, true);
        return @touch($path);
    }

    public static function bytesToSize($bytes) {
        if ($bytes <= 0) return '0 B';
        $exp = min(floor(log($bytes, 1024)), count(static::$units) - 1);
        return round($bytes / pow(1024, $exp), 2) . ' ' . static::$units[$exp];
    }

    public static function temporaryName($prefix = '') {
        return $prefix . uniqid();
    }

}
