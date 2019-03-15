<?php

namespace Formwork\Utils;

use RuntimeException;

class FileSystem
{
    /**
     * Array containing files to ignore
     *
     * @var array
     */
    protected static $ignore = array('.', '..');

    /**
     * Array containing units of measurement for human-readable file sizes
     *
     * @var array
     */
    protected static $units = array('B', 'KB', 'MB', 'GB', 'TB');

    /**
     * Get file name without extension given a file
     *
     * @param string $file
     *
     * @return string
     */
    public static function name($file)
    {
        $basename = basename($file);
        $pos = strrpos($basename, '.');
        return $pos !== false ? substr($basename, 0, $pos) : $basename;
    }

    /**
     * Get extension of a file
     *
     * @param string $file
     *
     * @return string
     */
    public static function extension($file)
    {
        $extension = substr(basename($file), strlen(static::name($file)) + 1);
        return $extension !== false ? $extension : '';
    }

    /**
     * Get MIME type of a file
     *
     * @param string $file
     *
     * @return string|null
     */
    public static function mimeType($file)
    {
        $mimeType = @mime_content_type($file);

        // Fix wrong type for image/svg+xml
        if ($mimeType === 'text/html') {
            $node = @simplexml_load_file($file);
            if ($node && $node->getName() === 'svg') {
                $mimeType = MimeType::fromExtension('svg');
            }
        }

        return $mimeType ?: MimeType::fromExtension(static::extension($file));
    }

    /**
     * Return whether a file exists
     *
     * @param string $path
     *
     * @return bool
     */
    public static function exists($path)
    {
        return @file_exists($path);
    }

    /**
     * Assert a file exists or not
     *
     * @param bool $value Whether to assert if file exists or not
     *
     * @return bool
     */
    public static function assert($path, $value = true)
    {
        if ($value === true && !static::exists($path)) {
            throw new RuntimeException('File not found: ' . $path);
        }
        if ($value === false && static::exists($path)) {
            throw new RuntimeException('File ' . $path . ' already exists');
        }
        return true;
    }

    /**
     * Get access time of a file
     *
     * @param string $file
     *
     * @return int|null
     */
    public static function accessTime($file)
    {
        static::assert($file);
        return @fileatime($file) ?: null;
    }

    /**
     * Get creation time of a file
     *
     * @param string $file
     *
     * @return int|null
     */
    public static function creationTime($file)
    {
        static::assert($file);
        return @filectime($file) ?: null;
    }

    /**
     * Get last modified time of a file
     *
     * @param string $file
     *
     * @return int|null
     */
    public static function lastModifiedTime($file)
    {
        static::assert($file);
        return @filemtime($file) ?: null;
    }

    /**
     * Return whether a directory has been modified since a given time
     *
     * @param int $time
     *
     * @return bool
     */
    public static function directoryModifiedSince($directory, $time)
    {
        if (static::lastModifiedTime($directory) > $time) {
            return true;
        }
        foreach (static::scan($directory) as $item) {
            $path = static::normalize($directory) . $item;
            if (static::lastModifiedTime($path) > $time) {
                return true;
            }
            if (static::isDirectory($path) && static::directoryModifiedSince($path, $time)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get file size
     *
     * @param string $file
     * @param bool   $unit Whether to return size with unit of measurement or not
     *
     * @return int|string|null
     */
    public static function size($file, $unit = true)
    {
        static::assert($file);
        if (($bytes = @filesize($file)) !== false) {
            return $unit ? static::bytesToSize($bytes) : $bytes;
        }
        return null;
    }

    /**
     * Get directory size recursively
     *
     * @param string $path
     * @param bool   $unit Whether to return size with unit of measurement or not
     *
     * @return int|string|null
     */
    public static function directorySize($path, $unit = true)
    {
        $path = static::normalize($path);
        static::assert($path);
        $bytes = 0;
        foreach (static::scan($path, true) as $item) {
            if (static::isFile($path . $item)) {
                $bytes += (int) static::size($path . $item, false);
            } else {
                $bytes += static::directorySize($path . $item, false);
            }
        }
        return $unit ? static::bytesToSize($bytes) : $bytes;
    }

    /**
     * Get an integer representing permissions of a file
     *
     * @param string $file
     *
     * @return int
     */
    public static function mode($file)
    {
        static::assert($file);
        return @fileperms($file);
    }

    /**
     * Return whether a file is visible (starts with a dot) or not
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isVisible($path)
    {
        return basename($path)[0] !== '.';
    }

    /**
     * Return whether a file is readable
     *
     * @param string $file
     *
     * @return bool
     */
    public static function isReadable($file)
    {
        static::assert($file);
        return @is_readable($file);
    }

    /**
     * Return whether a file is writable
     *
     * @param string $file
     *
     * @return bool
     */
    public static function isWritable($file)
    {
        static::assert($file);
        return @is_writable($file);
    }

    /**
     * Return whether a path corresponds to a file
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isFile($path)
    {
        static::assert($path);
        return @is_file($path);
    }

    /**
     * Return whether a path corresponds to a directory
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isDirectory($path)
    {
        static::assert($path);
        return @is_dir($path);
    }

    /**
     * Delete a file or a directory
     *
     * @param string $path
     * @param bool   $recursive Whether to delete files recursively or not
     *
     * @return bool
     */
    public static function delete($path, $recursive = false)
    {
        static::assert($path);
        if (static::isFile($path)) {
            return @unlink($path);
        }
        if ($recursive) {
            foreach (static::scan($path, true) as $file) {
                static::delete($path . DS . $file, true);
            }
        }
        return @rmdir($path);
    }

    /**
     * Copy a file to another path
     *
     * @param string $source
     * @param string $destination
     * @param bool   $overwrite   Whether to overwrite destination file or not
     *
     * @return bool
     */
    public static function copy($source, $destination, $overwrite = false)
    {
        static::assert($source);
        if (!$overwrite) {
            static::assert($destination, false);
        }
        return @copy($source, $destination);
    }

    /**
     * Download a file to a destination
     *
     * @param string   $source
     * @param string   $destination
     * @param bool     $overwrite   Whether to overwrite destination if already exists
     * @param resource $context     A stream context resource
     *
     * @return bool
     */
    public static function download($source, $destination, $overwrite = false, $context = null)
    {
        if (!$overwrite) {
            static::assert($destination, false);
        }
        if (!is_null($context)) {
            $valid = is_resource($context) && get_resource_type($context) === 'stream-context';
            if (!$valid) {
                throw new RuntimeException('Invalid stream context resource');
            }
        }
        if (!@copy($source, $destination, $context)) {
            throw new RuntimeException('Cannot download ' . $source);
        }
        return true;
    }

    /**
     * Move a file to another path
     *
     * @param string $source
     * @param string $destination
     * @param bool   $overwrite   Whether to overwrite destination file or not
     *
     * @return bool
     */
    public static function move($source, $destination, $overwrite = false)
    {
        static::assert($source);
        if (!$overwrite) {
            static::assert($destination, false);
        }
        return @rename($source, $destination);
    }

    /**
     * Move a directory to another path
     *
     * @param string $source
     * @param string $destination
     * @param bool   $overwrite   Whether to overwrite destination directory or not
     *
     * @return bool
     */
    public static function moveDirectory($source, $destination, $overwrite = false)
    {
        $source = static::normalize($source);
        $destination = static::normalize($destination);
        if (!$overwrite) {
            static::assert($destination, false);
        }
        if (!static::exists($destination)) {
            static::createDirectory($destination);
        }
        foreach (static::scan($source, true) as $item) {
            if (static::isFile($source . $item)) {
                static::move($source . $item, $destination . $item);
            } else {
                static::moveDirectory($source . $item, $destination . $item);
            }
        }
        static::delete($source, true);
    }

    /**
     * Swap two files
     *
     * @param string $path1
     * @param string $path2
     *
     * @return bool
     */
    public static function swap($path1, $path2)
    {
        $temp = dirname($path1) . DS . static::temporaryName('temp.');
        static::move($path1, $temp);
        static::move($path2, $path1);
        static::move($temp, $path2);
        return true;
    }

    /**
     * Read the content of a file
     *
     * @param string $file
     *
     * @return string
     */
    public static function read($file)
    {
        static::assert($file);
        return file_get_contents($file);
    }

    /**
     * Fetch a remote file
     *
     * @param string   $source
     * @param resource $context A stream context resource
     *
     * @return string
     */
    public static function fetch($source, $context = null)
    {
        if (!is_null($context)) {
            $valid = is_resource($context) && get_resource_type($context) === 'stream-context';
            if (!$valid) {
                throw new RuntimeException('Invalid stream context resource');
            }
        }
        $data = @file_get_contents($source, false, $context);
        if ($data === false) {
            throw new RuntimeException('Cannot fetch ' . $source);
        }
        return $data;
    }

    /**
     * Write content to file
     *
     * @param string $file
     * @param string $content
     * @param bool   $append  Whether to append or replace content
     *
     * @return bool
     */
    public static function write($file, $content, $append = false)
    {
        $flag = $append ? FILE_APPEND : null;
        return file_put_contents($file, $content, $flag) !== false;
    }

    /**
     * Create a new file with empty content
     *
     * @param string $file
     *
     * @return bool
     */
    public static function createFile($file)
    {
        static::assert($file, false);
        return static::write($file, '');
    }

    /**
     * Create a empty directory
     *
     * @param string $directory
     * @param bool   $recursive Whether to create directory recursively
     *
     * @return bool
     */
    public static function createDirectory($directory, $recursive = false)
    {
        static::assert($directory, false);
        return @mkdir($directory, 0777, $recursive);
    }

    /**
     * Alias of createFile method
     *
     * @see FileSystem::createFile()
     *
     * @param string $file
     *
     * @return bool
     */
    public static function create($file)
    {
        return static::createFile($file);
    }

    /**
     * Return a path with a single trailing slash
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalize($path)
    {
        return rtrim($path, DS) . DS;
    }

    /**
     * Scan a path for files and directories
     *
     * @param string $path
     * @param bool   $all  Whether to return only visible or all files
     *
     * @return array
     */
    public static function scan($path, $all = false)
    {
        static::assert($path);
        if (!static::isDirectory($path)) {
            throw new RuntimeException('Unable to list: ' . $path . ', specified path is not a directory');
        }
        $items = @scandir($path);
        if (!is_array($items)) {
            return array();
        }
        $items = array_diff($items, static::$ignore);
        if (!$all) {
            $items = array_filter($items, array(static::class, 'isVisible'));
        }
        return $items;
    }

    /**
     * Recursively scan a path for files and directories
     *
     * @param string $path
     * @param bool   $all  Whether to return only visible or all files
     *
     * @return array
     */
    public static function scanRecursive($path, $all = false)
    {
        $list = array();
        $path = static::normalize($path);
        foreach (FileSystem::scan($path, $all) as $item) {
            if (FileSystem::isDirectory($path . $item)) {
                $list = array_merge($list, static::scanRecursive($path . $item, $all));
            } else {
                $list[] = $path . $item;
            }
        }
        return $list;
    }

    /**
     * Scan a path only for files
     *
     * @param string $path
     * @param bool   $all  Whether to return only visible or all files
     *
     * @return array
     */
    public static function listFiles($path, $all = false)
    {
        $path = static::normalize($path);
        return array_filter(static::scan($path, $all), static function ($item) use ($path) {
            return static::isFile($path . $item);
        });
    }

    /**
     * Scan a path only for directories
     *
     * @param string $path
     * @param bool   $all  Whether to return only visible or all directories
     *
     * @return array
     */
    public static function listDirectories($path, $all = false)
    {
        $path = static::normalize($path);
        return array_filter(static::scan($path, $all), static function ($item) use ($path) {
            return static::isDirectory($path . $item);
        });
    }

    /**
     * Find files or directories matching a glob pattern
     *
     * @param string $pattern
     * @param mixed  ...$arguments
     *
     * @return array|null
     */
    public static function glob($pattern, ...$arguments)
    {
        if (empty($arguments)) {
            return @glob($pattern) ?: null;
        }

        if (is_int($flags = $arguments[0])) {
            return @glob($pattern, $flags) ?: null;
        }

        if (static::isDirectory($base = static::normalize($arguments[0]))) {
            if (!isset($arguments[1])) {
                $glob = @glob($base . $pattern);
            } else {
                $flags = $arguments[1];
                $glob = @glob($base . $pattern, $flags);
            }
            return is_array($glob) ? array_map(array(static::class, 'basename'), $glob) : null;
        }

        throw new RuntimeException(__METHOD__ . ' base path not found: ' . $base);
    }

    /**
     * Touch a file or directory
     *
     * @param string $path
     *
     * @return bool
     */
    public static function touch($path)
    {
        static::assert($path, true);
        return @touch($path);
    }

    /**
     * Convert bytes to a human-readable size
     *
     * @param int $bytes
     *
     * @return string
     */
    public static function bytesToSize($bytes)
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $exp = min(floor(log($bytes, 1024)), count(static::$units) - 1);
        return round($bytes / pow(1024, $exp), 2) . ' ' . static::$units[$exp];
    }

    /**
     * Convert shorthand bytes notation to an integer
     *
     * @see http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
     *
     * @param string $shorthand
     *
     * @return int
     */
    public static function shorthandToBytes($shorthand)
    {
        $shorthand = trim($shorthand);
        preg_match('/^(\d+)([K|M|G]?)$/i', $shorthand, $matches);
        $value = (int) $matches[1];
        $unit = strtoupper($matches[2]);
        if ($unit === 'K') {
            $value *= 1024;
        } elseif ($unit === 'M') {
            $value *= 1024 * 1024;
        } elseif ($unit === 'G') {
            $value *= 1024 * 1024;
        }
        return $value;
    }

    /**
     * Generate a random file name
     *
     * @return string
     */
    public static function randomName()
    {
        return str_shuffle(dechex(mt_rand(0x100, 0xfff)) . uniqid());
    }

    /**
     * Generate a temporary file name
     *
     * @param string $prefix Optional file name prefix
     *
     * @return string
     */
    public static function temporaryName($prefix = '')
    {
        return $prefix . static::randomName();
    }
}
