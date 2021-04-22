<?php

namespace Formwork\Utils;

use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\Exceptions\FileSystemException;
use Generator;
use InvalidArgumentException;

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
     * Default mode for created files
     *
     * @var int
     */
    protected const DEFAULT_FILE_MODE = 0666;

    /**
     * Default mode for created directories
     *
     * @var int
     */
    protected const DEFAULT_DIRECTORY_MODE = 0777;

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
     * Normalize path slashes
     */
    public static function normalizePath(string $path): string
    {
        return Path::normalize($path, DS);
    }

    /**
     * Join paths and normalize the result
     */
    public static function joinPaths(string ...$paths): string
    {
        return Path::join($paths, DS);
    }

    /**
     * Resolve a relative path against current working directory
     */
    public static function resolvePath(string $path): string
    {
        return Path::resolve($path, static::cwd(), DS);
    }

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
     * Return current working directory
     */
    public static function cwd(): string
    {
        if (($cwd = @getcwd()) !== false) {
            return $cwd;
        }
        throw new FileSystemException('Cannot get current working directory');
    }

    /**
     * Return whether a file or a directory is visible (starts with a dot) or not
     */
    public static function isVisible(string $path): bool
    {
        return !Str::startsWith(basename($path), '.');
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
     * @param bool $value Whether to assert if file exists or not
     */
    public static function assertExists(string $path, bool $value = true): void
    {
        if ($value === true && !static::exists($path)) {
            throw new FileNotFoundException(sprintf('File "%s" not found', $path));
        }
        if ($value === false && static::exists($path)) {
            throw new FileSystemException(sprintf('%s "%s" already exists', !static::isDirectory($path) ? 'File' : 'Directory', $path));
        }
    }

    /**
     * Return whether a file or directory is readable
     */
    public static function isReadable(string $path, bool $assertExists = true): bool
    {
        if ($assertExists) {
            static::assertExists($path);
        }
        return @is_readable($path);
    }

    /**
     * Return whether a file or a directory is writable
     */
    public static function isWritable(string $path, bool $assertExists = true): bool
    {
        if ($assertExists) {
            static::assertExists($path);
        }
        return @is_writable($path);
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
     * Return whether a path corresponds to a symbolic link
     */
    public static function isLink(string $path, bool $assertExists = true): bool
    {
        if (@is_link($path)) {
            return true;
        }
        if ($assertExists) {
            // Assert existence after we are sure it's not a link because `exists()` would check its target
            static::assertExists($path);
        }
        return false;
    }

    /**
     * Get access time of a file or a directory
     */
    public static function accessTime(string $path): int
    {
        static::assertExists($path);
        if (($time = @fileatime($path)) !== false) {
            return $time;
        }
        throw new FileSystemException(sprintf('Cannot get access time of "%s": %s', $path, static::getLastErrorMessage()));
    }

    /**
     * Get creation time of a file or a directory
     */
    public static function creationTime(string $path): int
    {
        static::assertExists($path);
        if (($time = @filectime($path)) !== false) {
            return $time;
        }
        throw new FileSystemException(sprintf('Cannot get creation time of "%s": %s', $path, static::getLastErrorMessage()));
    }

    /**
     * Get last modified time of a file or a directory
     */
    public static function lastModifiedTime(string $path): int
    {
        static::assertExists($path);
        if (($time = @filemtime($path)) !== false) {
            return $time;
        }
        throw new FileSystemException(sprintf('Cannot get last modified time of "%s": %s', $path, static::getLastErrorMessage()));
    }

    /**
     * Return whether a directory has been modified since a given time
     */
    public static function directoryModifiedSince(string $directory, int $time): bool
    {
        if (!static::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
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
     * Update last modified and access time of a file or a directory
     */
    public static function touch(string $path): bool
    {
        static::assertExists($path, true);
        if (@touch($path)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot touch "%s": %s', $path, static::getLastErrorMessage()));
    }

    /**
     * Get an integer representing permissions of a file or a directory
     */
    public static function mode(string $path): int
    {
        static::assertExists($path);
        if (($mode = @fileperms($path)) !== false) {
            return $mode;
        }
        throw new FileSystemException(sprintf('Cannot get permissions of "%s": %s', $path, static::getLastErrorMessage()));
    }

    /**
     * Get file or directory size in bytes
     */
    public static function size(string $path): int
    {
        if (static::isFile($path)) {
            return static::fileSize($path);
        }
        if (static::isDirectory($path)) {
            return static::directorySize($path);
        }
        throw new FileSystemException(sprintf('Cannot get size for "%s": unsupported file type (%s)', $path, @filetype($path)));
    }

    /**
     * Get file size in bytes
     */
    public static function fileSize(string $file): int
    {
        if (!static::isFile($file)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $file argument', __METHOD__));
        }
        if (($size = @filesize($file)) !== false) {
            return $size;
        }
        throw new FileSystemException(sprintf('Cannot get file size for "%s": %s', $file, static::getLastErrorMessage()));
    }

    /**
     * Get directory size in bytes recursively
     */
    public static function directorySize(string $directory): int
    {
        if (!static::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        $size = 0;
        foreach (static::listContents($directory, self::LIST_ALL) as $item) {
            $itemPath = static::joinPaths($directory, $item);
            $size += static::size($itemPath);
        }
        return $size;
    }

    /**
     * Delete a file, a directory or a symbolic link
     *
     * @param bool $recursive Whether to delete files recursively or not
     */
    public static function delete(string $path, bool $recursive = false): bool
    {
        if (static::isLink($path)) {
            return static::deleteLink($path);
        }
        if (static::isFile($path)) {
            return static::deleteFile($path);
        }
        if (static::isDirectory($path)) {
            return static::deleteDirectory($path, $recursive);
        }
        throw new FileSystemException(sprintf('Cannot delete "%s": unsupported file type (%s)', $path, @filetype($path)));
    }

    /**
     * Delete a file
     */
    public static function deleteFile(string $file): bool
    {
        if (!static::isFile($file)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $file argument', __METHOD__));
        }
        if (@unlink($file)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot delete file "%s": %s', $file, static::getLastErrorMessage()));
    }

    /**
     * Delete a directory
     *
     * @param bool $recursive Whether to delete directory content recursively or not
     */
    public static function deleteDirectory(string $directory, bool $recursive = false): bool
    {
        if (!static::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        if ($recursive) {
            foreach (static::listContents($directory, self::LIST_ALL) as $item) {
                $itemPath = static::joinPaths($directory, $item);
                static::delete($itemPath, $recursive);
            }
        } else {
            if (!static::isEmptyDirectory($directory)) {
                throw new FileSystemException(sprintf('Directory "%s" must be empty to be deleted', $directory));
            }
        }
        if (@rmdir($directory)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot delete directory "%s": %s', $directory, static::getLastErrorMessage()));
    }

    /**
     * Delete a symbolic link
     */
    public static function deleteLink(string $link): bool
    {
        if (!static::isLink($link)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only links as $link argument', __METHOD__));
        }
        // On Windows symbolic links pointing to a directory have to be removed with `rmdir()`
        // see https://bugs.php.net/bug.php?id=52176
        if (@unlink($link) || (DS === '\\' && @rmdir($link))) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot delete symbolic link "%s": %s', $link, static::getLastErrorMessage()));
    }

    /**
     * Copy a file, a directory or a symbolic link
     *
     * @param bool $overwrite Whether to overwrite destination or not
     */
    public static function copy(string $source, string $destination, bool $overwrite = false): bool
    {
        if (static::isLink($source)) {
            return static::copyLink($source, $destination, $overwrite);
        }
        if (static::isFile($source)) {
            return static::copyFile($source, $destination, $overwrite);
        }
        if (static::isDirectory($source)) {
            return static::copyDirectory($source, $destination, $overwrite);
        }
        throw new FileSystemException(sprintf('Cannot copy "%s": unsupported file type (%s)', $source, @filetype($source)));
    }

    /**
     * Copy a file to another path
     *
     * @param bool $overwrite Whether to overwrite destination or not
     */
    public static function copyFile(string $source, string $destination, bool $overwrite = false): bool
    {
        if (!static::isFile($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $source argument', __METHOD__));
        }
        if (!$overwrite) {
            static::assertExists($destination, false);
        }
        if (@copy($source, $destination)) {
            @chmod($destination, @fileperms($source));
            return true;
        }
        throw new FileSystemException(sprintf('Cannot copy file "%s": %s', $source, static::getLastErrorMessage()));
    }

    /**
     * Copy a directory to another path
     *
     * @param bool $overwrite Whether to overwrite destination or not
     */
    public static function copyDirectory(string $source, string $destination, bool $overwrite = false): bool
    {
        if (!static::isDirectory($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $source argument', __METHOD__));
        }
        if (!$overwrite) {
            static::assertExists($destination, false);
        }
        if (!static::exists($destination)) {
            static::createDirectory($destination, true);
        }
        @chmod($destination, @fileperms($source));
        try {
            foreach (static::listContents($source, self::LIST_ALL) as $item) {
                $sourceItemPath = static::joinPaths($source, $item);
                $destinationItemPath = static::joinPaths($destination, $item);
                static::copy($sourceItemPath, $destinationItemPath, $overwrite);
            }
        } catch (FileSystemException $e) {
            // Delete destination directory if something fails
            static::deleteDirectory($destination, true);
            throw $e;
        }
        return true;
    }

    /**
     * Copy a symbolic link to another path
     *
     * @param bool $overwrite Whether to overwrite destination or not
     */
    public static function copyLink(string $source, string $destination, bool $overwrite = false): bool
    {
        if (!static::isLink($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only links as $source argument', __METHOD__));
        }
        if (!$overwrite) {
            static::assertExists($destination, false);
        } elseif (FileSystem::exists($destination)) {
            FileSystem::delete($destination, true);
        }
        return static::createLink(static::readLink($source), $destination, false);
    }

    /**
     * Move a file, a directory or a symbolic link
     *
     * @param bool $overwrite Whether to overwrite destination file or not
     */
    public static function move(string $source, string $destination, bool $overwrite = false): bool
    {
        if (static::isLink($source)) {
            return static::moveLink($source, $destination, $overwrite);
        }
        if (static::isFile($source)) {
            return static::moveFile($source, $destination, $overwrite);
        }
        if (static::isDirectory($source)) {
            return static::moveDirectory($source, $destination, $overwrite);
        }
        throw new FileSystemException(sprintf('Cannot move "%s": unsupported file type (%s)', $source, @filetype($source)));
    }

    /**
     * Move a file to another path
     *
     * @param bool $overwrite Whether to overwrite destination file or not
     */
    public static function moveFile(string $source, string $destination, bool $overwrite = false): bool
    {
        if (!static::isFile($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $source argument', __METHOD__));
        }
        if (!$overwrite) {
            static::assertExists($destination, false);
        }
        if (@rename($source, $destination)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot move file "%s": %s', $source, static::getLastErrorMessage()));
    }

    /**
     * Move a directory to another path
     *
     * @param bool $overwrite Whether to overwrite destination directory or not
     */
    public static function moveDirectory(string $source, string $destination, bool $overwrite = false): bool
    {
        if (!static::isDirectory($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $source argument', __METHOD__));
        }
        // Make a copy instead of directly trasferring files to avoid messing up
        // with an incomplete state if something fails
        static::copyDirectory($source, $destination, $overwrite);
        static::deleteDirectory($source, true);
        return true;
    }

    /**
     * Move a symbolic link to another path
     *
     * @param bool $overwrite Whether to overwrite destination directory or not
     */
    public static function moveLink(string $source, string $destination, bool $overwrite = false): bool
    {
        if (!static::isLink($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only links as $source argument', __METHOD__));
        }
        static::copyLink($source, $destination, $overwrite);
        static::deleteLink($source);
        return true;
    }

    /**
     * Read the content of a file
     */
    public static function read(string $file): string
    {
        if (!static::isFile($file)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $file argument', __METHOD__));
        }
        if (!static::isReadable($file)) {
            throw new FileSystemException(sprintf('Cannot read "%s": file exists but is not readable', $file));
        }
        if (($data = @file_get_contents($file)) !== false) {
            return $data;
        }
        throw new FileSystemException(sprintf('Cannot read "%s": %s', $file, static::getLastErrorMessage()));
    }

    /**
     * List files and directories contained in a path
     *
     * @param int $flags Any of FileSystem::LIST_FILES, FileSystem::LIST_DIRECTORIES, FileSystem::LIST_HIDDEN, FileSystem::LIST_VISIBLE, FileSystem::LIST_ALL flags
     */
    public static function listContents(string $directory, int $flags = self::LIST_VISIBLE): Generator
    {
        if (!static::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        $handle = @opendir($directory);
        if ($handle === false) {
            throw new FileSystemException(sprintf('Cannot open the directory "%s": %s', $directory, static::getLastErrorMessage()));
        }
        while (($item = @readdir($handle)) !== false) {
            if (in_array($item, self::IGNORED_FILES, true)) {
                continue;
            }
            if (!($flags & self::LIST_HIDDEN) && !static::isVisible($item)) {
                continue;
            }
            $itemPath = static::joinPaths($directory, $item);
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
    public static function listRecursive(string $directory, int $flags = self::LIST_VISIBLE): Generator
    {
        if (!static::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        foreach (static::listContents($directory, $flags) as $item) {
            $itemPath = static::joinPaths($directory, $item);
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
    public static function listFiles(string $directory, bool $all = false): Generator
    {
        return static::listContents($directory, $all ? self::LIST_FILES | self::LIST_HIDDEN : self::LIST_FILES);
    }

    /**
     * List directories contained in a path
     *
     * @param bool $all Whether to return only visible or all directories
     */
    public static function listDirectories(string $directory, bool $all = false): Generator
    {
        return static::listContents($directory, $all ? self::LIST_DIRECTORIES | self::LIST_HIDDEN : self::LIST_DIRECTORIES);
    }

    /**
     * Read the target of a symbolic link
     */
    public static function readLink(string $link): string
    {
        if (!static::isLink($link)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only links as $link argument', __METHOD__));
        }
        // Use `realpath()` on Windows because `readlink()` returns the canonicalized path
        if (($target = DS === '\\' ? @realpath($link) : @readlink($link)) !== false) {
            return $target;
        }
        throw new FileSystemException(sprintf('Cannot resolve symbolic link "%s": %s', $link, static::getLastErrorMessage()));
    }

    /**
     * Create a new file with empty content
     */
    public static function createFile(string $file): bool
    {
        // x+ mode checks file existence atomically
        if (($handle = @fopen($file, 'x+')) !== false) {
            @fclose($handle);
            @chmod($file, self::DEFAULT_FILE_MODE & ~umask());
            return true;
        }
        throw new FileSystemException(sprintf('Cannot create file "%s": %s', $file, static::getLastErrorMessage()));
    }

    /**
     * Try to create a temporary file in the specified directory and return its path
     */
    public static function createTemporaryFile(string $directory, string $prefix = ''): string
    {
        $attempts = 0;
        while ($attempts++ < 10) {
            $temporaryFile = static::joinPaths($directory, static::randomName($prefix));
            try {
                static::createFile($temporaryFile);
            } catch (FileSystemException $e) {
                continue;
            }
            return $temporaryFile;
        }
        throw new FileSystemException('Cannot create a temporary file');
    }

    /**
     * Write content to file atomically
     */
    public static function write(string $file, string $content): bool
    {
        if (static::exists($file) && !static::isFile($file)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $file argument', __METHOD__));
        }
        if (static::exists($file) && !static::isWritable($file)) {
            throw new FileSystemException(sprintf('Cannot write "%s": file exists but is not writable', $file));
        }
        $temporaryFile = static::createTemporaryFile(dirname($file));
        if (@file_put_contents($temporaryFile, $content, LOCK_EX) === false) {
            throw new FileSystemException(sprintf('Cannot write "%s": %s', $file, static::getLastErrorMessage()));
        }
        if (static::exists($file)) {
            @chmod($temporaryFile, @fileperms($file));
        }
        return static::moveFile($temporaryFile, $file, true);
    }

    /**
     * Create a empty directory
     *
     * @param bool $recursive Whether to create directory recursively
     */
    public static function createDirectory(string $directory, bool $recursive = false): bool
    {
        if (@mkdir($directory, self::DEFAULT_DIRECTORY_MODE, $recursive)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot create directory "%s": %s', $directory, static::getLastErrorMessage()));
    }

    /**
     * Create a symbolic link
     *
     * @param bool $assertExists Whether to assert the existence of the link target
     */
    public static function createLink(string $target, string $link, bool $assertExists = true): bool
    {
        if ($assertExists) {
            static::assertExists($target);
        }
        // On Windows `symlink()` may require an absolute path
        if (@symlink($target, $link) || (DS === '\\' && @symlink(static::resolvePath($target), $link))) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot create symbolic link "%s": %s', $link, static::getLastErrorMessage()));
    }

    /**
     * Convert bytes to a human-readable size
     */
    public static function formatSize(int $bytes): string
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
    public static function randomName(string $prefix = ''): string
    {
        return $prefix . bin2hex(random_bytes(8));
    }

    /**
     * Return the message string of the last error
     */
    protected static function getLastErrorMessage(): string
    {
        return Str::after(error_get_last()['message'] ?? '', ': ');
    }
}
