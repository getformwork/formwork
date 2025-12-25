<?php

namespace Formwork\Http\Session\Handler;

use Formwork\Utils\Exceptions\FileSystemException;
use Formwork\Utils\FileSystem;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    /**
     * Session files path
     */
    protected string $path;

    /**
     * Session files prefix
     */
    protected string $prefix = 'sess_';

    /**
     * Open file handles
     *
     * @var array<string, resource>
     */
    protected array $handles = [];

    /**
     * @param string|null $path Session files path. If null, the default path will be used (as per "session.save_path" ini setting or system temp directory)
     */
    public function __construct(?string $path = null)
    {
        $this->path = FileSystem::normalizePath($path ?? $this->getDefaultPath());
    }

    /**
     * Initialize session
     */
    public function open(string $savePath, string $name): bool
    {
        try {
            if (!FileSystem::isDirectory($this->path, false)) {
                FileSystem::createDirectory($this->path, recursive: true);
            }
        } catch (FileSystemException) {
            return false;
        }

        return true;
    }

    /**
     * Close session
     */
    public function close(): bool
    {
        $success = true;

        foreach ($this->handles as $handle) {
            if (@flock($handle, LOCK_UN) === false) {
                $success = false;
            }

            @fclose($handle);
        }

        $this->handles = [];

        return $success;
    }

    /**
     * Read session data
     */
    public function read(string $sessionId): string|false
    {
        $file = $this->filePath($sessionId);

        $handle = $this->handles[$sessionId] ?? null;

        // Reuse a previously opened handle only if it's still a valid resource.
        if ($handle === null || !is_resource($handle)) {
            unset($this->handles[$sessionId]);
            $handle = $this->getHandle($file);
        }

        if ($handle === false) {
            return '';
        }

        clearstatcache(filename: $file);
        rewind($handle);

        $data = (string) stream_get_contents($handle);

        $this->handles[$sessionId] = $handle;

        return $data;
    }

    /**
     * Write session data
     */
    public function write(string $sessionId, string $data): bool
    {
        $file = $this->filePath($sessionId);

        $handle = $this->handles[$sessionId] ?? $this->getHandle($file);

        if ($handle === false) {
            return false;
        }

        unset($this->handles[$sessionId]);

        $result = $this->writeToHandle($handle, $data);

        if ($result) {
            @chmod($file, 0o600 & ~umask());
        }

        return $result;
    }

    /**
     * Destroy a session
     */
    public function destroy(string $sessionId): bool
    {
        $file = $this->filePath($sessionId);

        if (!FileSystem::isFile($file, false)) {
            return true;
        }

        try {
            FileSystem::deleteFile($file);
            return true;
        } catch (FileSystemException) {
            return false;
        }
    }

    /**
     * Garbage collect old sessions
     */
    public function gc(int $maxLifetime): int|false
    {
        $now = time();
        $deleted = 0;

        if (!FileSystem::isDirectory($this->path, false)) {
            return 0;
        }

        foreach (FileSystem::listFiles($this->path, true) as $item) {
            if (!str_starts_with($item, $this->prefix)) {
                continue;
            }

            $file = FileSystem::joinPaths($this->path, $item);

            if (!FileSystem::isFile($file, false)) {
                continue;
            }

            if (FileSystem::lastModifiedTime($file) + $maxLifetime < $now) {
                try {
                    FileSystem::deleteFile($file);
                } catch (FileSystemException) {
                    continue;
                }
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Validate a session ID
     */
    public function validateId(string $sessionId): bool
    {
        return FileSystem::isFile($this->filePath($sessionId), false);
    }

    /**
     * Update session timestamp
     */
    public function updateTimestamp(string $sessionId, string $data): bool
    {
        $file = $this->filePath($sessionId);

        if (FileSystem::isFile($file, false)) {
            try {
                return FileSystem::touch($file);
            } catch (FileSystemException) {
                return false;
            }
        }

        // If file does not exist, create it with provided data
        return $this->write($sessionId, $data);
    }

    /**
     * Get a file handle with exclusive lock
     *
     * @return false|resource
     */
    protected function getHandle(string $file)
    {
        if (($handle = @fopen($file, 'c+b')) === false) {
            return false;
        }

        if (flock($handle, LOCK_EX) === false) {
            fclose($handle);
            return false;
        }

        return $handle;
    }

    /**
     * Write data using an open handle, then release lock and close.
     *
     * @param resource $handle
     */
    protected function writeToHandle($handle, string $data): bool
    {
        if (!is_resource($handle)) {
            return false;
        }

        if (ftruncate($handle, 0) === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }

        rewind($handle);

        $written = fwrite($handle, $data);

        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $written !== false && $written === strlen($data);
    }

    /**
     * Get the default session path from php.ini or system temp directory
     */
    protected function getDefaultPath(): string
    {
        $path = ini_get('session.save_path') ?: sys_get_temp_dir();

        // session.save_path can be in the form "N;/path", use the part after the last semicolon
        if (($pos = strrpos($path, ';')) !== false) {
            return substr($path, $pos + 1);
        }

        return $path;
    }

    /**
     * Get the file path for a given session ID
     */
    protected function filePath(string $sessionId): string
    {
        // Sanitize session ID to prevent directory traversal
        $sessionId = basename($sessionId);
        return FileSystem::joinPaths($this->path, "{$this->prefix}{$sessionId}");
    }
}
