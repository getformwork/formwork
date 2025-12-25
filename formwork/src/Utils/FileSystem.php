<?php

namespace Formwork\Utils;

use Formwork\Traits\StaticClass;
use Formwork\Utils\Exceptions\FileNotFoundException;
use Formwork\Utils\Exceptions\FileSystemException;
use Generator;
use InvalidArgumentException;

final class FileSystem
{
    use StaticClass;

    /**
     * List only files flag
     */
    public const int LIST_FILES = 1;

    /**
     * List only directories flag
     */
    public const int LIST_DIRECTORIES = 2;

    /**
     * List hidden files flag
     */
    public const int LIST_HIDDEN = 4;

    /**
     * Exclude empty directories flag
     */
    public const int LIST_EXCLUDE_EMPTY_DIRECTORIES = 8;

    /**
     * List visible files and directories flag
     */
    public const int LIST_VISIBLE = self::LIST_FILES | self::LIST_DIRECTORIES;

    /**
     * List visible and hidden files and directories flag
     */
    public const int LIST_ALL = self::LIST_FILES | self::LIST_DIRECTORIES | self::LIST_HIDDEN;

    /**
     * Maximum path length provided by the system
     */
    public const int MAX_PATH_LENGTH = PHP_MAXPATHLEN - 2;

    /**
     * Maximum directory or filename length
     */
    public const int MAX_NAME_LENGTH = 255;

    /**
     * Default mode for created files
     */
    private const int DEFAULT_FILE_MODE = 0o666;

    /**
     * Default mode for created directories
     */
    private const int DEFAULT_DIRECTORY_MODE = 0o777;

    /**
     * Array containing files to ignore
     *
     * @var list<string>
     */
    private const array IGNORED_FILES = ['.', '..'];

    /**
     * Array containing units of measurement for human-readable file sizes
     *
     * @var list<string>
     */
    private const array FILE_SIZE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    /**
     * Normalize path slashes
     */
    public static function normalizePath(string $path): string
    {
        return Path::normalize($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Join paths and normalize the result
     */
    public static function joinPaths(string ...$paths): string
    {
        return Path::join($paths, DIRECTORY_SEPARATOR);
    }

    /**
     * Resolve a relative path against current working directory
     */
    public static function resolvePath(string $path): string
    {
        return Path::resolve($path, self::cwd(), DIRECTORY_SEPARATOR);
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
     *
     * @throws FileSystemException If unable to get current working directory
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
    public static function mimeType(string $file): string
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
     *
     * @throws FileNotFoundException If file should exist but doesn't
     * @throws FileSystemException   If file should not exist but does
     */
    public static function assertExists(string $path, bool $value = true): void
    {
        if ($value && !self::exists($path)) {
            throw new FileNotFoundException(sprintf('File "%s" not found', $path));
        }
        if ($value) {
            return;
        }
        if (!self::exists($path)) {
            return;
        }
        throw new FileSystemException(sprintf('%s "%s" already exists', self::isDirectory($path) ? 'Directory' : 'File', $path));
    }

    /**
     * Return whether a file or directory is readable
     */
    public static function isReadable(string $path, bool $assertExists = true): bool
    {
        if ($assertExists) {
            self::assertExists($path);
        }
        return @is_readable($path);
    }

    /**
     * Return whether a file or a directory is writable
     */
    public static function isWritable(string $path, bool $assertExists = true): bool
    {
        if ($assertExists) {
            self::assertExists($path);
        }
        return @is_writable($path);
    }

    /**
     * Return whether a path corresponds to a file
     */
    public static function isFile(string $path, bool $assertExists = true): bool
    {
        if ($assertExists) {
            self::assertExists($path);
        }
        return @is_file($path);
    }

    /**
     * Return whether a path corresponds to a directory
     */
    public static function isDirectory(string $path, bool $assertExists = true): bool
    {
        if ($assertExists) {
            self::assertExists($path);
        }
        return @is_dir($path);
    }

    /**
     * Return whether a directory is empty
     */
    public static function isEmptyDirectory(string $path, bool $assertExists = true): bool
    {
        if (!self::isDirectory($path, $assertExists)) {
            return false;
        }
        foreach (self::listContents($path, self::LIST_ALL) as $item) {
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
            self::assertExists($path);
        }
        return false;
    }

    /**
     * Get access time of a file or a directory
     *
     * @throws FileSystemException If unable to get access time
     */
    public static function accessTime(string $path): int
    {
        self::assertExists($path);
        if (($time = @fileatime($path)) !== false) {
            return $time;
        }
        throw new FileSystemException(sprintf('Cannot get access time of "%s": %s', $path, self::getLastErrorMessage()));
    }

    /**
     * Get creation time of a file or a directory
     *
     * @throws FileSystemException If unable to get creation time
     */
    public static function creationTime(string $path): int
    {
        self::assertExists($path);
        if (($time = @filectime($path)) !== false) {
            return $time;
        }
        throw new FileSystemException(sprintf('Cannot get creation time of "%s": %s', $path, self::getLastErrorMessage()));
    }

    /**
     * Get last modified time of a file or a directory
     *
     * @throws FileSystemException If unable to get last modified time
     */
    public static function lastModifiedTime(string $path): int
    {
        self::assertExists($path);
        if (($time = @filemtime($path)) !== false) {
            return $time;
        }
        throw new FileSystemException(sprintf('Cannot get last modified time of "%s": %s', $path, self::getLastErrorMessage()));
    }

    /**
     * Return whether a directory has been modified since a given time
     *
     * @param int $time The timestamp to compare against
     *
     * @throws InvalidArgumentException If the path is not a directory
     */
    public static function directoryModifiedSince(string $directory, int $time): bool
    {
        if (!self::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        if (self::lastModifiedTime($directory) > $time) {
            return true;
        }
        foreach (self::listContents($directory) as $item) {
            $path = self::joinPaths($directory, $item);
            if (self::lastModifiedTime($path) > $time) {
                return true;
            }
            if (!self::isDirectory($path)) {
                continue;
            }
            if (!self::directoryModifiedSince($path, $time)) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * Update last modified and access time of a file or a directory
     *
     * @throws FileSystemException If unable to touch the path
     */
    public static function touch(string $path): true
    {
        self::assertExists($path, true);
        if (@touch($path)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot touch "%s": %s', $path, self::getLastErrorMessage()));
    }

    /**
     * Get an integer representing permissions of a file or a directory
     *
     * @throws FileSystemException If unable to get permissions
     */
    public static function mode(string $path): int
    {
        self::assertExists($path);
        if (($mode = @fileperms($path)) !== false) {
            return $mode;
        }
        throw new FileSystemException(sprintf('Cannot get permissions of "%s": %s', $path, self::getLastErrorMessage()));
    }

    /**
     * Get file or directory size in bytes
     *
     * @throws FileSystemException If unable to get size or unsupported file type
     */
    public static function size(string $path): int
    {
        if (self::isFile($path)) {
            return self::fileSize($path);
        }
        if (self::isDirectory($path)) {
            return self::directorySize($path);
        }
        throw new FileSystemException(sprintf('Cannot get size for "%s": unsupported file type (%s)', $path, @filetype($path)));
    }

    /**
     * Get file size in bytes
     *
     * @throws InvalidArgumentException If the path is not a file
     * @throws FileSystemException      If unable to get file size
     */
    public static function fileSize(string $file): int
    {
        if (!self::isFile($file)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $file argument', __METHOD__));
        }
        if (($size = @filesize($file)) !== false) {
            return $size;
        }
        throw new FileSystemException(sprintf('Cannot get file size for "%s": %s', $file, self::getLastErrorMessage()));
    }

    /**
     * Get directory size in bytes recursively
     *
     * @throws InvalidArgumentException If the path is not a directory
     */
    public static function directorySize(string $directory): int
    {
        if (!self::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        $size = 0;
        foreach (self::listContents($directory, self::LIST_ALL) as $item) {
            $itemPath = self::joinPaths($directory, $item);
            $size += self::size($itemPath);
        }
        return $size;
    }

    /**
     * Delete a file, a directory or a symbolic link
     *
     * @param bool $recursive Whether to delete files recursively or not
     *
     * @throws FileSystemException If unable to delete or unsupported file type
     */
    public static function delete(string $path, bool $recursive = false): true
    {
        if (self::isLink($path)) {
            return self::deleteLink($path);
        }
        if (self::isFile($path)) {
            return self::deleteFile($path);
        }
        if (self::isDirectory($path)) {
            return self::deleteDirectory($path, $recursive);
        }
        throw new FileSystemException(sprintf('Cannot delete "%s": unsupported file type (%s)', $path, @filetype($path)));
    }

    /**
     * Delete a file
     *
     * @throws InvalidArgumentException If the path is not a file
     * @throws FileSystemException      If unable to delete the file
     */
    public static function deleteFile(string $file): true
    {
        if (!self::isFile($file)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $file argument', __METHOD__));
        }
        if (@unlink($file)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot delete file "%s": %s', $file, self::getLastErrorMessage()));
    }

    /**
     * Delete a directory
     *
     * @param bool $recursive Whether to delete directory content recursively or not
     *
     * @throws InvalidArgumentException If the path is not a directory
     * @throws FileSystemException      If unable to delete the directory
     */
    public static function deleteDirectory(string $directory, bool $recursive = false): true
    {
        if (!self::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        if ($recursive) {
            foreach (self::listContents($directory, self::LIST_ALL) as $item) {
                $itemPath = self::joinPaths($directory, $item);
                self::delete($itemPath, $recursive);
            }
        } elseif (!self::isEmptyDirectory($directory)) {
            throw new FileSystemException(sprintf('Directory "%s" must be empty to be deleted', $directory));
        }
        if (@rmdir($directory)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot delete directory "%s": %s', $directory, self::getLastErrorMessage()));
    }

    /**
     * Delete a symbolic link
     *
     * @throws InvalidArgumentException If the path is not a symbolic link
     * @throws FileSystemException      If unable to delete the symbolic link
     */
    public static function deleteLink(string $link): true
    {
        if (!self::isLink($link)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only links as $link argument', __METHOD__));
        }
        // On Windows symbolic links pointing to a directory have to be removed with `rmdir()`
        // see https://bugs.php.net/bug.php?id=52176
        if (@unlink($link) || (DIRECTORY_SEPARATOR === '\\' && @rmdir($link))) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot delete symbolic link "%s": %s', $link, self::getLastErrorMessage()));
    }

    /**
     * Copy a file, a directory or a symbolic link
     *
     * @param bool $overwrite Whether to overwrite destination or not
     *
     * @throws FileSystemException If unable to copy or unsupported file type
     */
    public static function copy(string $source, string $destination, bool $overwrite = false): true
    {
        if (self::isLink($source)) {
            return self::copyLink($source, $destination, $overwrite);
        }
        if (self::isFile($source)) {
            return self::copyFile($source, $destination, $overwrite);
        }
        if (self::isDirectory($source)) {
            return self::copyDirectory($source, $destination, $overwrite);
        }
        throw new FileSystemException(sprintf('Cannot copy "%s": unsupported file type (%s)', $source, @filetype($source)));
    }

    /**
     * Copy a file to another path
     *
     * @param bool $overwrite Whether to overwrite destination or not
     *
     * @throws InvalidArgumentException If the source is not a file
     * @throws FileSystemException      If unable to copy the file
     */
    public static function copyFile(string $source, string $destination, bool $overwrite = false): true
    {
        if (!self::isFile($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $source argument', __METHOD__));
        }
        if (!$overwrite) {
            self::assertExists($destination, false);
        }
        if (@copy($source, $destination) && ($perms = @fileperms($source))) {
            @chmod($destination, $perms);
            return true;
        }
        throw new FileSystemException(sprintf('Cannot copy file "%s": %s', $source, self::getLastErrorMessage()));
    }

    /**
     * Copy a directory to another path
     *
     * @param bool $overwrite Whether to overwrite destination or not
     *
     * @throws InvalidArgumentException If the source is not a directory
     * @throws FileSystemException      If unable to copy the directory
     */
    public static function copyDirectory(string $source, string $destination, bool $overwrite = false): true
    {
        if (!self::isDirectory($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $source argument', __METHOD__));
        }
        if (!$overwrite) {
            self::assertExists($destination, false);
        }
        if (!self::exists($destination)) {
            self::createDirectory($destination, true);
        }
        if (($perms = @fileperms($source))) {
            @chmod($destination, $perms);
        }
        try {
            foreach (self::listContents($source, self::LIST_ALL) as $item) {
                $sourceItemPath = self::joinPaths($source, $item);
                $destinationItemPath = self::joinPaths($destination, $item);
                self::copy($sourceItemPath, $destinationItemPath, $overwrite);
            }
        } catch (FileSystemException $e) {
            // Delete destination directory if something fails
            self::deleteDirectory($destination, true);
            throw $e;
        }
        return true;
    }

    /**
     * Copy a symbolic link to another path
     *
     * @param bool $overwrite Whether to overwrite destination or not
     *
     * @throws InvalidArgumentException If the source is not a symbolic link
     * @throws FileSystemException      If unable to copy the symbolic link
     */
    public static function copyLink(string $source, string $destination, bool $overwrite = false): true
    {
        if (!self::isLink($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only links as $source argument', __METHOD__));
        }
        if (!$overwrite) {
            self::assertExists($destination, false);
        } elseif (FileSystem::exists($destination)) {
            FileSystem::delete($destination, true);
        }
        return self::createLink(self::readLink($source), $destination, false);
    }

    /**
     * Move a file, a directory or a symbolic link
     *
     * @param bool $overwrite Whether to overwrite destination file or not
     *
     * @throws FileSystemException If unable to move or unsupported file type
     */
    public static function move(string $source, string $destination, bool $overwrite = false): true
    {
        if (self::isLink($source)) {
            return self::moveLink($source, $destination, $overwrite);
        }
        if (self::isFile($source)) {
            return self::moveFile($source, $destination, $overwrite);
        }
        if (self::isDirectory($source)) {
            return self::moveDirectory($source, $destination, $overwrite);
        }
        throw new FileSystemException(sprintf('Cannot move "%s": unsupported file type (%s)', $source, @filetype($source)));
    }

    /**
     * Move a file to another path
     *
     * @param bool $overwrite Whether to overwrite destination file or not
     *
     * @throws InvalidArgumentException If the source is not a file
     * @throws FileSystemException      If unable to move the file
     */
    public static function moveFile(string $source, string $destination, bool $overwrite = false): true
    {
        if (!self::isFile($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $source argument', __METHOD__));
        }
        if (!$overwrite) {
            self::assertExists($destination, false);
        }
        if (@rename($source, $destination)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot move file "%s": %s', $source, self::getLastErrorMessage()));
    }

    /**
     * Move a directory to another path
     *
     * @param bool $overwrite Whether to overwrite destination directory or not
     *
     * @throws InvalidArgumentException If the source is not a directory
     */
    public static function moveDirectory(string $source, string $destination, bool $overwrite = false): true
    {
        if (!self::isDirectory($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $source argument', __METHOD__));
        }
        // Make a copy instead of directly trasferring files to avoid messing up
        // with an incomplete state if something fails
        self::copyDirectory($source, $destination, $overwrite);
        self::deleteDirectory($source, true);
        return true;
    }

    /**
     * Move a symbolic link to another path
     *
     * @param bool $overwrite Whether to overwrite destination directory or not
     *
     * @throws InvalidArgumentException If the source is not a symbolic link
     */
    public static function moveLink(string $source, string $destination, bool $overwrite = false): true
    {
        if (!self::isLink($source)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only links as $source argument', __METHOD__));
        }
        self::copyLink($source, $destination, $overwrite);
        self::deleteLink($source);
        return true;
    }

    /**
     * Read the content of a file
     *
     * @throws InvalidArgumentException If the path is not a file
     * @throws FileSystemException      If unable to read the file
     */
    public static function read(string $file): string
    {
        if (!self::isFile($file)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $file argument', __METHOD__));
        }
        if (!self::isReadable($file)) {
            throw new FileSystemException(sprintf('Cannot read "%s": file exists but is not readable', $file));
        }
        if (($data = @file_get_contents($file)) !== false) {
            return $data;
        }
        throw new FileSystemException(sprintf('Cannot read "%s": %s', $file, self::getLastErrorMessage()));
    }

    /**
     * List files and directories contained in a path
     *
     * @param int-mask-of<self::LIST_*> $flags Any of `FileSystem::LIST_FILES`, `FileSystem::LIST_DIRECTORIES`, `FileSystem::LIST_EXCLUDE_EMPTY_DIRECTORIES`, `FileSystem::LIST_HIDDEN`, `FileSystem::LIST_VISIBLE`, `FileSystem::LIST_ALL` flags
     *
     * @throws InvalidArgumentException If the path is not a directory
     * @throws FileSystemException      If unable to open the directory
     *
     * @return Generator<int, string>
     */
    public static function listContents(string $directory, int $flags = self::LIST_VISIBLE): Generator
    {
        if (!self::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        $handle = @opendir($directory);
        if ($handle === false) {
            throw new FileSystemException(sprintf('Cannot open the directory "%s": %s', $directory, self::getLastErrorMessage()));
        }
        while (($item = @readdir($handle)) !== false) {
            if (in_array($item, self::IGNORED_FILES, true)) {
                continue;
            }
            if (!($flags & self::LIST_HIDDEN) && !self::isVisible($item)) {
                continue;
            }
            $itemPath = self::joinPaths($directory, $item);
            if (!($flags & self::LIST_FILES) && self::isFile($itemPath)) {
                continue;
            }
            if (!($flags & self::LIST_DIRECTORIES) && self::isDirectory($itemPath)) {
                continue;
            }
            if (($flags & self::LIST_EXCLUDE_EMPTY_DIRECTORIES) && self::isEmptyDirectory($itemPath)) {
                continue;
            }
            yield $item;
        }
        @closedir($handle);
    }

    /**
     * Recursively list files and directories contained in a path
     *
     * @param int-mask-of<self::LIST_*> $flags Any of `FileSystem::LIST_FILES`, `FileSystem::LIST_DIRECTORIES`, `FileSystem::LIST_EXCLUDE_EMPTY_DIRECTORIES`, `FileSystem::LIST_HIDDEN`, `FileSystem::LIST_VISIBLE`, `FileSystem::LIST_ALL` flags
     *
     * @throws InvalidArgumentException If the path is not a directory
     *
     * @return Generator<int, string>
     */
    public static function listRecursive(string $directory, int $flags = self::LIST_VISIBLE): Generator
    {
        if (!self::isDirectory($directory)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only directories as $directory argument', __METHOD__));
        }
        foreach (self::listContents($directory, $flags) as $item) {
            $itemPath = self::joinPaths($directory, $item);
            if (self::isDirectory($itemPath)) {
                foreach (self::listRecursive($itemPath, $flags) as $childItem) {
                    yield self::joinPaths($item, $childItem);
                }
            } else {
                yield $item;
            }
        }
    }

    /**
     * List files contained in a path
     *
     * @param bool $includeHidden Whether to return only visible or all files
     *
     * @return Generator<int, string>
     */
    public static function listFiles(string $directory, bool $includeHidden = false): Generator
    {
        $flags = self::LIST_FILES;
        if ($includeHidden) {
            $flags |= self::LIST_HIDDEN;
        }
        return self::listContents($directory, $flags);
    }

    /**
     * List directories contained in a path
     *
     * @param bool $includeHidden Whether to return only visible or all directories
     * @param bool $includeEmpty  Whether to include empty directories in the result
     *
     * @return Generator<int, string>
     */
    public static function listDirectories(string $directory, bool $includeHidden = false, bool $includeEmpty = true): Generator
    {
        $flags = self::LIST_DIRECTORIES;
        if ($includeHidden) {
            $flags |= self::LIST_HIDDEN;
        }
        if (!$includeEmpty) {
            $flags |= self::LIST_EXCLUDE_EMPTY_DIRECTORIES;
        }
        return self::listContents($directory, $flags);
    }

    /**
     * Read the target of a symbolic link
     *
     * @throws InvalidArgumentException If the path is not a symbolic link
     * @throws FileSystemException      If unable to resolve the symbolic link
     */
    public static function readLink(string $link): string
    {
        if (!self::isLink($link)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only links as $link argument', __METHOD__));
        }
        // Use `realpath()` on Windows because `readlink()` returns the canonicalized path
        if (($target = DIRECTORY_SEPARATOR === '\\' ? @realpath($link) : @readlink($link)) !== false) {
            return $target;
        }
        throw new FileSystemException(sprintf('Cannot resolve symbolic link "%s": %s', $link, self::getLastErrorMessage()));
    }

    /**
     * Create a new file with empty content
     *
     * @throws FileSystemException If unable to create the file
     */
    public static function createFile(string $file): true
    {
        // x+ mode checks file existence atomically
        if (($handle = @fopen($file, 'x+')) !== false) {
            @fclose($handle);
            @chmod($file, self::DEFAULT_FILE_MODE & ~umask());
            return true;
        }
        throw new FileSystemException(sprintf('Cannot create file "%s": %s', $file, self::getLastErrorMessage()));
    }

    /**
     * Try to create a temporary file in the specified directory and return its path
     *
     * @param string $directory The directory where to create the temporary file
     * @param string $prefix    Optional prefix for the temporary file name
     *
     * @throws FileSystemException If unable to create a temporary file after 10 attempts
     */
    public static function createTemporaryFile(string $directory, string $prefix = ''): string
    {
        $attempts = 0;
        while ($attempts++ < 10) {
            $temporaryFile = self::joinPaths($directory, self::randomName($prefix));
            try {
                self::createFile($temporaryFile);
            } catch (FileSystemException) {
                continue;
            }
            return $temporaryFile;
        }
        throw new FileSystemException('Cannot create a temporary file');
    }

    /**
     * Write content to file atomically
     *
     * @throws InvalidArgumentException If the path exists but is not a file
     * @throws FileSystemException      If unable to write to the file
     */
    public static function write(string $file, string $content): true
    {
        if (self::exists($file) && !self::isFile($file)) {
            throw new InvalidArgumentException(sprintf('%s() accepts only files as $file argument', __METHOD__));
        }
        if (self::exists($file) && !self::isWritable($file)) {
            throw new FileSystemException(sprintf('Cannot write "%s": file exists but is not writable', $file));
        }
        $temporaryFile = self::createTemporaryFile(dirname($file));
        if (@file_put_contents($temporaryFile, $content, LOCK_EX) === false) {
            throw new FileSystemException(sprintf('Cannot write "%s": %s', $file, self::getLastErrorMessage()));
        }
        if (self::exists($file) && ($perms = @fileperms($file))) {
            @chmod($temporaryFile, $perms);
        }
        return self::moveFile($temporaryFile, $file, true);
    }

    /**
     * Create a empty directory
     *
     * @param bool $recursive Whether to create directory recursively
     *
     * @throws FileSystemException If unable to create the directory
     */
    public static function createDirectory(string $directory, bool $recursive = false): true
    {
        if (@mkdir($directory, self::DEFAULT_DIRECTORY_MODE, $recursive)) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot create directory "%s": %s', $directory, self::getLastErrorMessage()));
    }

    /**
     * Create a symbolic link
     *
     * @param bool $assertExists Whether to assert the existence of the link target
     *
     * @throws FileSystemException If unable to create the symbolic link
     */
    public static function createLink(string $target, string $link, bool $assertExists = true): true
    {
        if ($assertExists) {
            self::assertExists($target);
        }
        // On Windows `symlink()` may require an absolute path
        if (@symlink($target, $link) || (DIRECTORY_SEPARATOR === '\\' && @symlink(self::resolvePath($target), $link))) {
            return true;
        }
        throw new FileSystemException(sprintf('Cannot create symbolic link "%s": %s', $link, self::getLastErrorMessage()));
    }

    /**
     * Convert bytes to a human-readable size
     */
    public static function formatSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $exp = min((int) floor(log($bytes, 1024)), count(self::FILE_SIZE_UNITS) - 1);
        return round($bytes / 1024 ** $exp, 2) . ' ' . self::FILE_SIZE_UNITS[$exp];
    }

    /**
     * Convert shorthand bytes notation to an integer
     *
     * @param string $shorthand Shorthand bytes notation (e.g., '1K', '2M', '3G')
     *
     * @throws InvalidArgumentException If the shorthand notation is invalid
     *
     * @see https://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
     */
    public static function shorthandToBytes(string $shorthand): int
    {
        $shorthand = trim($shorthand);
        if (!preg_match('/^(\d+)([K|M|G]?)$/i', $shorthand, $matches)) {
            throw new InvalidArgumentException(sprintf('Invalid shorthand bytes notation "%s"', $shorthand));
        }
        [, $value, $unit] = $matches;
        return match (strtoupper($unit)) {
            'K'     => (int) $value * 1024,
            'M'     => (int) $value * 1024 ** 2,
            'G'     => (int) $value * 1024 ** 3,
            default => (int) $value,
        };
    }

    /**
     * Generate a random file name
     *
     * @param string $prefix Optional prefix for the random name
     */
    public static function randomName(string $prefix = ''): string
    {
        return $prefix . bin2hex(random_bytes(8));
    }

    /**
     * Return the message string of the last error
     */
    private static function getLastErrorMessage(): string
    {
        return Str::after(error_get_last()['message'] ?? '', ': ');
    }
}
