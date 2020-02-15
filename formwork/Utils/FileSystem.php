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
    protected const IGNORED_FILES = ['.', '..'];

    /**
     * Array containing units of measurement for human-readable file sizes
     *
     * @var array
     */
    protected const FILE_SIZE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    /**
     * Get file name without extension given a file
     *
     * @return string
     */
    public static function name(string $file)
    {
        $basename = basename($file);
        $pos = strrpos($basename, '.');
        return $pos !== false ? substr($basename, 0, $pos) : $basename;
    }

    /**
     * Get extension of a file
     *
     * @return string
     */
    public static function extension(string $file)
    {
        return substr(basename($file), strlen(static::name($file)) + 1);
    }

    /**
     * Get MIME type of a file
     *
     * @return string|null
     */
    public static function mimeType(string $file)
    {
        return MimeType::fromFile($file);
    }

    /**
     * Return whether a file exists
     *
     * @return bool
     */
    public static function exists(string $path)
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
    public static function assert(string $path, bool $value = true)
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
     * @return int|null
     */
    public static function accessTime(string $file)
    {
        static::assert($file);
        return @fileatime($file) ?: null;
    }

    /**
     * Get creation time of a file
     *
     * @return int|null
     */
    public static function creationTime(string $file)
    {
        static::assert($file);
        return @filectime($file) ?: null;
    }

    /**
     * Get last modified time of a file
     *
     * @return int|null
     */
    public static function lastModifiedTime(string $file)
    {
        static::assert($file);
        return @filemtime($file) ?: null;
    }

    /**
     * Return whether a directory has been modified since a given time
     *
     * @return bool
     */
    public static function directoryModifiedSince(string $directory, int $time)
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
     * @param bool $unit Whether to return size with unit of measurement or not
     *
     * @return int|string|null
     */
    public static function size(string $file, bool $unit = true)
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
     * @param bool $unit Whether to return size with unit of measurement or not
     *
     * @return int|string|null
     */
    public static function directorySize(string $path, bool $unit = true)
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
     * @return int
     */
    public static function mode(string $file)
    {
        static::assert($file);
        return @fileperms($file);
    }

    /**
     * Return whether a file is visible (starts with a dot) or not
     *
     * @return bool
     */
    public static function isVisible(string $path)
    {
        return basename($path)[0] !== '.';
    }

    /**
     * Return whether a file is readable
     *
     * @return bool
     */
    public static function isReadable(string $file)
    {
        static::assert($file);
        return @is_readable($file);
    }

    /**
     * Return whether a file is writable
     *
     * @return bool
     */
    public static function isWritable(string $file)
    {
        static::assert($file);
        return @is_writable($file);
    }

    /**
     * Return whether a path corresponds to a file
     *
     * @return bool
     */
    public static function isFile(string $path)
    {
        static::assert($path);
        return @is_file($path);
    }

    /**
     * Return whether a path corresponds to a directory
     *
     * @return bool
     */
    public static function isDirectory(string $path)
    {
        static::assert($path);
        return @is_dir($path);
    }

    /**
     * Delete a file or a directory
     *
     * @param bool $recursive Whether to delete files recursively or not
     *
     * @return bool
     */
    public static function delete(string $path, bool $recursive = false)
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
     * @param bool $overwrite Whether to overwrite destination file or not
     *
     * @return bool
     */
    public static function copy(string $source, string $destination, bool $overwrite = false)
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
     * @param bool     $overwrite Whether to overwrite destination if already exists
     * @param resource $context   A stream context resource
     *
     * @return bool
     */
    public static function download(string $source, string $destination, bool $overwrite = false, $context = null)
    {
        if (!$overwrite) {
            static::assert($destination, false);
        }
        if ($context !== null) {
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
     * @param bool $overwrite Whether to overwrite destination file or not
     *
     * @return bool
     */
    public static function move(string $source, string $destination, bool $overwrite = false)
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
     * @param bool $overwrite Whether to overwrite destination directory or not
     *
     * @return bool
     */
    public static function moveDirectory(string $source, string $destination, bool $overwrite = false)
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
     * Read the content of a file
     *
     * @return string
     */
    public static function read(string $file)
    {
        static::assert($file);
        return file_get_contents($file);
    }

    /**
     * Fetch a remote file
     *
     * @param resource $context A stream context resource
     *
     * @return string
     */
    public static function fetch(string $source, $context = null)
    {
        if (filter_var($source, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Cannot fetch ' . $source . ': invalid URI');
        }
        if ($context !== null) {
            $valid = is_resource($context) && get_resource_type($context) === 'stream-context';
            if (!$valid) {
                throw new RuntimeException('Invalid stream context resource');
            }
        }
        $data = @file_get_contents($source, false, $context);
        if ($data === false) {
            $message = error_get_last()['message'] ?? '';
            // Stream errors are in the form %s(%s):%s, we only need the trailing part
            if (preg_match('/^.*\(.*\)(:.*)/', $message, $matches)) {
                $message = $matches[1];
            }
            throw new RuntimeException('Cannot fetch ' . $source . $message);
        }
        return $data;
    }

    /**
     * Write content to file atomically
     *
     * @return bool
     */
    public static function write(string $file, string $content)
    {
        $temp = static::temporaryName($file . '.');
        if (file_put_contents($temp, $content, LOCK_EX) === false) {
            throw new RuntimeException('Cannot write ' . $file);
        }
        if (static::exists($file)) {
            @chmod($temp, @fileperms($file));
        }
        return static::move($temp, $file, true);
    }

    /**
     * Create a new file with empty content
     *
     * @return bool
     */
    public static function createFile(string $file)
    {
        static::assert($file, false);
        return static::write($file, '');
    }

    /**
     * Create a empty directory
     *
     * @param bool $recursive Whether to create directory recursively
     *
     * @return bool
     */
    public static function createDirectory(string $directory, bool $recursive = false)
    {
        static::assert($directory, false);
        return @mkdir($directory, 0777, $recursive);
    }

    /**
     * Alias of createFile method
     *
     * @see FileSystem::createFile()
     *
     * @return bool
     */
    public static function create(string $file)
    {
        return static::createFile($file);
    }

    /**
     * Return a path with a single trailing slash
     *
     * @return string
     */
    public static function normalize(string $path)
    {
        return rtrim($path, DS) . DS;
    }

    /**
     * Scan a path for files and directories
     *
     * @param bool $all Whether to return only visible or all files
     *
     * @return array
     */
    public static function scan(string $path, bool $all = false)
    {
        static::assert($path);
        if (!static::isDirectory($path)) {
            throw new RuntimeException('Unable to list: ' . $path . ', specified path is not a directory');
        }
        $items = @scandir($path);
        if (!is_array($items)) {
            return [];
        }
        $items = array_diff($items, self::IGNORED_FILES);
        if (!$all) {
            $items = array_filter($items, [static::class, 'isVisible']);
        }
        return $items;
    }

    /**
     * Recursively scan a path for files and directories
     *
     * @param bool $all Whether to return only visible or all files
     *
     * @return array
     */
    public static function scanRecursive(string $path, bool $all = false)
    {
        $list = [];
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
     * @param bool $all Whether to return only visible or all files
     *
     * @return array
     */
    public static function listFiles(string $path, bool $all = false)
    {
        $path = static::normalize($path);
        return array_filter(static::scan($path, $all), static function ($item) use ($path) {
            return static::isFile($path . $item);
        });
    }

    /**
     * Scan a path only for directories
     *
     * @param bool $all Whether to return only visible or all directories
     *
     * @return array
     */
    public static function listDirectories(string $path, bool $all = false)
    {
        $path = static::normalize($path);
        return array_filter(static::scan($path, $all), static function ($item) use ($path) {
            return static::isDirectory($path . $item);
        });
    }

    /**
     * Touch a file or directory
     *
     * @return bool
     */
    public static function touch(string $path)
    {
        static::assert($path, true);
        return @touch($path);
    }

    /**
     * Convert bytes to a human-readable size
     *
     * @return string
     */
    public static function bytesToSize(int $bytes)
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $exp = min(floor(log($bytes, 1024)), count(self::FILE_SIZE_UNITS) - 1);
        return round($bytes / 1024 ** $exp, 2) . ' ' . self::FILE_SIZE_UNITS[$exp];
    }

    /**
     * Convert shorthand bytes notation to an integer
     *
     * @see https://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
     *
     * @return int
     */
    public static function shorthandToBytes(string $shorthand)
    {
        $shorthand = trim($shorthand);
        preg_match('/^(\d+)([K|M|G]?)$/i', $shorthand, $matches);
        $value = (int) $matches[1];
        $unit = strtoupper($matches[2]);
        if ($unit === 'K') {
            $value *= 1024;
        } elseif ($unit === 'M') {
            $value *= 1024 ** 2;
        } elseif ($unit === 'G') {
            $value *= 1024 ** 3;
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
    public static function temporaryName(string $prefix = '')
    {
        return $prefix . static::randomName();
    }
}
