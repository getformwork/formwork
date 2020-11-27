<?php

namespace Formwork\Utils;

use Generator;
use RuntimeException;

class FileSystem
{
    /**
     * List only files flag
     *
     * @var int
     */
    public const LIST_FILES = 1;

    /**
     * List only directories flag
     *
     * @var int
     */
    public const LIST_DIRECTORIES = 2;

    /**
     * List hidden files flag
     *
     * @var int
     */
    public const LIST_HIDDEN = 4;

    /**
     * List visible files and directories flag
     *
     * @var int
     */
    public const LIST_VISIBLE = self::LIST_FILES | self::LIST_DIRECTORIES;

    /**
     * List visible and hidden files and directories flag
     *
     * @var int
     */
    public const LIST_ALL = self::LIST_FILES | self::LIST_DIRECTORIES | self::LIST_HIDDEN;

    /**
     * Maximum path length provided by the system
     *
     * @var int
     */
    public const MAX_PATH_LENGTH = PHP_MAXPATHLEN - 2;

    /**
     * Maximum directory or filename length
     *
     * @var int
     */
    public const MAX_NAME_LENGTH = 255;

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
     */
    public static function name(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * Get extension of a file
     */
    public static function extension(string $file): string
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * Get MIME type of a file
     */
    public static function mimeType(string $file): ?string
    {
        return MimeType::fromFile($file);
    }

    /**
     * Return whether a file exists
     */
    public static function exists(string $path): bool
    {
        return @file_exists($path);
    }

    /**
     * Assert a file exists or not
     *
     * @deprecated Use FileSystem::assertExists() instead
     */
    public static function assert(string $path): bool
    {
        trigger_error(static::class . '::assert() is deprecated since Formwork 1.11.0, use ' . static::class . '::assertExists() instead', E_USER_DEPRECATED);
        static::assertExists($path);
        return true;
    }

    /**
     * Assert a file exists or not
     *
     * @param bool $value Whether to assert if file exists or not
     */
    public static function assertExists(string $path, bool $value = true): void
    {
        if ($value === true && !static::exists($path)) {
            throw new RuntimeException('File not found: ' . $path);
        }
        if ($value === false && static::exists($path)) {
            throw new RuntimeException('File ' . $path . ' already exists');
        }
    }

    /**
     * Get access time of a file
     */
    public static function accessTime(string $file): ?int
    {
        static::assertExists($file);
        return @fileatime($file) ?: null;
    }

    /**
     * Get creation time of a file
     */
    public static function creationTime(string $file): ?int
    {
        static::assertExists($file);
        return @filectime($file) ?: null;
    }

    /**
     * Get last modified time of a file
     */
    public static function lastModifiedTime(string $file): ?int
    {
        static::assertExists($file);
        return @filemtime($file) ?: null;
    }

    /**
     * Return whether a directory has been modified since a given time
     */
    public static function directoryModifiedSince(string $directory, int $time): bool
    {
        if (static::lastModifiedTime($directory) > $time) {
            return true;
        }
        foreach (static::listContents($directory) as $item) {
            $path = static::joinPaths($directory, $item);
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
        static::assertExists($file);
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
        static::assertExists($path);
        $bytes = 0;
        foreach (static::listContents($path, self::LIST_ALL) as $item) {
            $itemPath = static::joinPaths($path, $item);
            if (static::isFile($itemPath)) {
                $bytes += (int) static::size($itemPath, false);
            } else {
                $bytes += static::directorySize($itemPath, false);
            }
        }
        return $unit ? static::bytesToSize($bytes) : $bytes;
    }

    /**
     * Get an integer representing permissions of a file
     */
    public static function mode(string $file): int
    {
        static::assertExists($file);
        return @fileperms($file);
    }

    /**
     * Return whether a file is visible (starts with a dot) or not
     */
    public static function isVisible(string $path): bool
    {
        return basename($path)[0] !== '.';
    }

    /**
     * Return whether a file is readable
     */
    public static function isReadable(string $file, bool $assertExists = true): bool
    {
        if ($assertExists) {
            static::assertExists($file);
        }
        return @is_readable($file);
    }

    /**
     * Return whether a file is writable
     */
    public static function isWritable(string $file, bool $assertExists = true): bool
    {
        if ($assertExists) {
            static::assertExists($file);
        }
        return @is_writable($file);
    }

    /**
     * Return whether a path corresponds to a file
     */
    public static function isFile(string $path, bool $assertExists = true): bool
    {
        if ($assertExists) {
            static::assertExists($path);
        }
        return @is_file($path);
    }

    /**
     * Return whether a path corresponds to a directory
     */
    public static function isDirectory(string $path, bool $assertExists = true): bool
    {
        if ($assertExists) {
            static::assertExists($path);
        }
        return @is_dir($path);
    }

    /**
     * Return whether a directory is empty
     */
    public static function isEmptyDirectory(string $path, bool $assertExists = true): bool
    {
        if (!static::isDirectory($path, $assertExists)) {
            return false;
        }
        foreach (static::listContents($path, self::LIST_ALL) as $item) {
            return false;
        }
        return true;
    }

    /**
     * Delete a file or a directory
     *
     * @param bool $recursive Whether to delete files recursively or not
     */
    public static function delete(string $path, bool $recursive = false): bool
    {
        static::assertExists($path);
        if (static::isFile($path)) {
            return @unlink($path);
        }
        if ($recursive) {
            foreach (static::listContents($path, self::LIST_ALL) as $item) {
                static::delete(static::joinPaths($path, $item), true);
            }
        }
        return @rmdir($path);
    }

    /**
     * Copy a file to another path
     *
     * @param bool $overwrite Whether to overwrite destination file or not
     */
    public static function copy(string $source, string $destination, bool $overwrite = false): bool
    {
        static::assertExists($source);
        if (!$overwrite) {
            static::assertExists($destination, false);
        }
        return @copy($source, $destination);
    }

    /**
     * Download a file to a destination
     *
     * @param bool     $overwrite Whether to overwrite destination if already exists
     * @param resource $context   A stream context resource
     */
    public static function download(string $source, string $destination, bool $overwrite = false, $context = null): bool
    {
        if (!$overwrite) {
            static::assertExists($destination, false);
        }
        $data = static::fetch($source, $context);
        static::write($destination, $data);
        return true;
    }

    /**
     * Move a file to another path
     *
     * @param bool $overwrite Whether to overwrite destination file or not
     */
    public static function move(string $source, string $destination, bool $overwrite = false): bool
    {
        static::assertExists($source);
        if (!$overwrite) {
            static::assertExists($destination, false);
        }
        return @rename($source, $destination);
    }

    /**
     * Move a directory to another path
     *
     * @param bool $overwrite Whether to overwrite destination directory or not
     */
    public static function moveDirectory(string $source, string $destination, bool $overwrite = false): void
    {
        if (!$overwrite) {
            static::assertExists($destination, false);
        }
        if (!static::exists($destination)) {
            static::createDirectory($destination);
        }
        foreach (static::listContents($source, self::LIST_ALL) as $item) {
            $sourceItemPath = static::joinPaths($source, $item);
            $destinationItemPath = static::joinPaths($destination, $item);
            if (static::isFile($sourceItemPath)) {
                static::move($sourceItemPath, $destinationItemPath);
            } else {
                static::moveDirectory($sourceItemPath, $destinationItemPath);
            }
        }
        static::delete($source, true);
    }

    /**
     * Read the content of a file
     */
    public static function read(string $file): string
    {
        static::assertExists($file);
        return @file_get_contents($file);
    }

    /**
     * Fetch a remote file
     *
     * @param resource $context A stream context resource
     */
    public static function fetch(string $source, $context = null): string
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
            throw new RuntimeException('Cannot fetch ' . $source . ': ' . static::getLastStreamErrorMessage());
        }
        return $data;
    }

    /**
     * Write content to file atomically
     */
    public static function write(string $file, string $content): bool
    {
        $temp = static::temporaryName($file . '.');
        if (static::exists($file) && !static::isWritable($file)) {
            throw new RuntimeException('Cannot write ' . $file . ': file exists but is not writable');
        }
        if (@file_put_contents($temp, $content, LOCK_EX) === false) {
            throw new RuntimeException('Cannot write ' . $file . ': ' . static::getLastStreamErrorMessage());
        }
        if (static::exists($file)) {
            @chmod($temp, @fileperms($file));
        }
        return static::move($temp, $file, true);
    }

    /**
     * Create a new file with empty content
     */
    public static function createFile(string $file): bool
    {
        static::assertExists($file, false);
        return static::write($file, '');
    }

    /**
     * Create a empty directory
     *
     * @param bool $recursive Whether to create directory recursively
     */
    public static function createDirectory(string $directory, bool $recursive = false): bool
    {
        static::assertExists($directory, false);
        return @mkdir($directory, 0777, $recursive);
    }

    /**
     * Alias of createFile method
     *
     * @deprecated
     * @see FileSystem::createFile()
     */
    public static function create(string $file): bool
    {
        trigger_error(static::class . '::create() is deprecated since Formwork 1.10.0, use ' . static::class . '::createFile() instead', E_USER_DEPRECATED);
        return static::createFile($file);
    }

    /**
     * Return a path with a single trailing slash
     *
     * @deprecated
     */
    public static function normalize(string $path): string
    {
        trigger_error(static::class . '::normalize($path) is deprecated since Formwork 1.10.0, use ' . static::class . '::normalizePath($path . DS) instead', E_USER_DEPRECATED);
        return static::normalizePath($path . DS);
    }

    /**
     * Normalize path slashes
     */
    public static function normalizePath(string $path): string
    {
        return preg_replace('~[/\\\\]+~', DS, $path);
    }

    /**
     * Join paths and normalize the result
     */
    public static function joinPaths(string ...$paths): string
    {
        return static::normalizePath(implode(DS, $paths));
    }

    /**
     * Scan a path for files and directories
     *
     * @deprecated
     *
     * @param bool $all Whether to return only visible or all files
     */
    public static function scan(string $path, bool $all = false): array
    {
        trigger_error(static::class . '::scan() is deprecated since Formwork 1.8.0, use ' . static::class . '::listContents() instead', E_USER_DEPRECATED);
        static::assertExists($path);
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
     * @deprecated
     *
     * @param bool $all Whether to return only visible or all files
     */
    public static function scanRecursive(string $path, bool $all = false): array
    {
        trigger_error(static::class . '::scanRecursive() is deprecated since Formwork 1.8.0, use ' . static::class . '::listRecursive() instead', E_USER_DEPRECATED);
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
     * List files and directories contained in a path
     *
     * @param int $flags Any of FileSystem::LIST_FILES, FileSystem::LIST_DIRECTORIES, FileSystem::LIST_HIDDEN, FileSystem::LIST_VISIBLE, FileSystem::LIST_ALL flags
     */
    public static function listContents(string $path, int $flags = self::LIST_VISIBLE): Generator
    {
        static::assertExists($path);
        $handle = @opendir($path);
        if ($handle === false) {
            throw new RuntimeException('Cannot open the directory ' . $path);
        }
        while (($item = @readdir($handle)) !== false) {
            if (in_array($item, self::IGNORED_FILES, true)) {
                continue;
            }
            if (!($flags & self::LIST_HIDDEN) && !static::isVisible($item)) {
                continue;
            }
            $itemPath = static::joinPaths($path, $item);
            if (!($flags & self::LIST_FILES) && static::isFile($itemPath)) {
                continue;
            }
            if (!($flags & self::LIST_DIRECTORIES) && static::isDirectory($itemPath)) {
                continue;
            }
            yield $item;
        }
        @closedir($handle);
    }

    /**
     * Recursively list files and directories contained in a path
     *
     * @param int $flags Any of FileSystem::LIST_FILES, FileSystem::LIST_DIRECTORIES, FileSystem::LIST_HIDDEN, FileSystem::LIST_VISIBLE, FileSystem::LIST_ALL flags
     */
    public static function listRecursive(string $path, int $flags = self::LIST_VISIBLE): Generator
    {
        foreach (static::listContents($path, $flags) as $item) {
            $itemPath = static::joinPaths($path, $item);
            if (static::isDirectory($itemPath)) {
                foreach (static::listRecursive($itemPath, $flags) as $item) {
                    yield $item;
                }
            } else {
                yield $itemPath;
            }
        }
    }

    /**
     * List files contained in a path
     *
     * @param bool $all Whether to return only visible or all files
     */
    public static function listFiles(string $path, bool $all = false): Generator
    {
        return static::listContents($path, $all ? self::LIST_FILES | self::LIST_HIDDEN : self::LIST_FILES);
    }

    /**
     * List directories contained in a path
     *
     * @param bool $all Whether to return only visible or all directories
     */
    public static function listDirectories(string $path, bool $all = false): Generator
    {
        return static::listContents($path, $all ? self::LIST_DIRECTORIES | self::LIST_HIDDEN : self::LIST_DIRECTORIES);
    }

    /**
     * Touch a file or directory
     */
    public static function touch(string $path): bool
    {
        static::assertExists($path, true);
        return @touch($path);
    }

    /**
     * Convert bytes to a human-readable size
     */
    public static function bytesToSize(int $bytes): string
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
     */
    public static function shorthandToBytes(string $shorthand): int
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
     */
    public static function randomName(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Generate a temporary file name
     *
     * @param string $prefix Optional file name prefix
     */
    public static function temporaryName(string $prefix = ''): string
    {
        return $prefix . static::randomName();
    }

    /**
     * Return the message string of the last stream error
     */
    protected static function getLastStreamErrorMessage(string $default = ''): string
    {
        $message = error_get_last()['message'] ?? '';
        // Stream errors are in the form %s(%s): %s, we only need the trailing part
        if (preg_match('/^.*\(.*\): (.*)/', $message, $matches)) {
            return $matches[1];
        }
        return $default;
    }
}
