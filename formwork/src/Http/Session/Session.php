<?php

namespace Formwork\Http\Session;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Http\Request;
use Formwork\Http\Session\Handler\FileSessionHandler;
use Formwork\Http\Utils\Cookie;
use Formwork\Http\Utils\Header;
use Formwork\Utils\Str;
use InvalidArgumentException;
use RuntimeException;

class Session implements Arrayable
{
    use DataArrayable;
    use DataMultipleGetter {
        has as protected baseHas;
        get as protected baseGet;
    }
    use DataMultipleSetter {
        set as protected baseSet;
        remove as protected baseRemove;
    }

    /**
     * Session name used for cookies
     */
    protected const string SESSION_NAME = 'formwork_session';

    /**
     * Key used to store messages in session data
     */
    protected const string SESSION_MESSAGES_KEY = '_formwork_messages';

    /**
     * Regex pattern for session IDs
     */
    protected const string SESSION_ID_REGEX = '/^[a-z0-9,-]{22,256}$/i';

    /**
     * Session messages
     */
    protected Messages $messages;

    /**
     * Session name
     */
    protected string $name = self::SESSION_NAME;

    /**
     * Whether the session has been started
     */
    protected bool $started = false;

    /**
     * Session duration in seconds
     */
    protected int $duration = 0;

    /**
     * Session save path
     */
    protected string $path;

    public function __construct(
        protected Request $request,
    ) {
        if (!extension_loaded('session')) {
            throw new RuntimeException('Sessions extension not available');
        }

        if (session_status() === PHP_SESSION_DISABLED) {
            throw new RuntimeException('Sessions disabled by PHP configuration');
        }
    }

    /**
     * Check if a session with a given ID exists
     */
    public function exists(string $id): bool
    {
        if (!$this->started) {
            $this->start();
        }

        if (session_id() === $id) {
            return true;
        }

        $this->destroy();

        return false;
    }

    /**
     * Start the session
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session already started');
        }

        session_name($this->name);

        if (PHP_VERSION_ID < 80400) {
            // Set session ID length and bits per character as of PHP 8.4+
            ini_set('session.sid_length', 32);
            ini_set('session.sid_bits_per_character', 4);
        }

        if (!session_id()) {
            $id = $this->request->cookies()->get($this->name, '');

            if (!preg_match(self::SESSION_ID_REGEX, $id)) {
                $id = '';
            }

            session_id($id);
        }

        session_set_save_handler(new FileSessionHandler($this->path ?? null));

        session_start([
            'cache_limiter'   => '',
            'cache_expire'    => 0,
            'use_strict_mode' => true,
        ]);

        Header::send('Cache-Control', 'no-store, no-cache, must-revalidate');

        if (($id = session_id()) === false) {
            throw new RuntimeException('Cannot get session id');
        }

        Cookie::send($this->name, $id, $this->getCookieOptions());

        $this->data = &$_SESSION;

        $this->started = true;
    }

    /**
     * Save session data
     */
    public function save(): void
    {
        if (!$this->started) {
            return;
        }

        session_write_close();

        $this->started = false;
    }

    /**
     * Destroy the session
     */
    public function destroy(): void
    {
        session_destroy();

        Cookie::remove($this->name, $this->getCookieOptions());

        $this->started = false;
    }

    /**
     * Regenerate the session ID
     */
    public function regenerate(bool $preserveData = true): void
    {
        $data = [];
        $moveData = static function (array &$source, array &$destination): void {
            foreach ($source as $key => $value) {
                $destination[$key] = $value;
                unset($source[$key]);
            }
        };
        if (session_status() === PHP_SESSION_ACTIVE) {
            if ($preserveData) {
                $moveData($_SESSION, $data);
            }
            session_destroy();
        }
        $newId = session_create_id();
        if ($newId === false) {
            throw new RuntimeException('Cannot create new session id');
        }
        session_id($newId);
        $this->start();

        if ($preserveData) {
            $moveData($data, $_SESSION);
        }
    }

    /**
     * Return whether the session has been started
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Get the session name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Set the session name
     */
    public function setName(string $name): void
    {
        if ($this->started) {
            throw new RuntimeException('Cannot set session name: session already started');
        }

        $this->name = $name;
    }

    /**
     * Set the session duration
     */
    public function setDuration(int $duration): void
    {
        $this->duration = $duration;

        if ($this->started) {
            if (($id = session_id()) === false) {
                throw new RuntimeException('Cannot get session id');
            }
            Cookie::send($this->name, $id, $this->getCookieOptions());
        }
    }

    /**
     * Set the session files path
     */
    public function setPath(string $path): void
    {
        if ($this->started) {
            throw new RuntimeException('Cannot set session save path: session already started');
        }

        $this->path = $path;
    }

    /**
     * Get session messages
     */
    public function messages(): Messages
    {
        if (!$this->started) {
            $this->start();
        }

        if (isset($this->messages)) {
            return $this->messages;
        }

        $this->data[self::SESSION_MESSAGES_KEY] ??= [];

        return $this->messages = new Messages($this->data[self::SESSION_MESSAGES_KEY]);
    }

    /**
     * Return whether the session has the given key
     */
    public function has(string $key): bool
    {
        if (!$this->started) {
            $this->start();
        }

        if (Str::startsWith($key, self::SESSION_MESSAGES_KEY)) {
            throw new InvalidArgumentException(sprintf('The key "%s" is reserved', self::SESSION_MESSAGES_KEY));
        }

        return $this->baseHas($key);
    }

    /**
     * Get the session value for the given key
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->started) {
            $this->start();
        }

        if (Str::startsWith($key, self::SESSION_MESSAGES_KEY)) {
            throw new InvalidArgumentException(sprintf('The key "%s" is reserved', self::SESSION_MESSAGES_KEY));
        }

        return $this->baseGet($key, $default);
    }

    /**
     * Remove the session value for the given key
     */
    public function remove(string $key): void
    {
        if (!$this->started) {
            $this->start();
        }

        if (Str::startsWith($key, self::SESSION_MESSAGES_KEY)) {
            throw new InvalidArgumentException(sprintf('The key "%s" is reserved', self::SESSION_MESSAGES_KEY));
        }

        $this->baseRemove($key);
    }

    /**
     * Set the session value for the given key
     */
    public function set(string $key, mixed $value): void
    {
        if (!$this->started) {
            $this->start();
        }

        if (Str::startsWith($key, self::SESSION_MESSAGES_KEY)) {
            throw new InvalidArgumentException(sprintf('The key "%s" is reserved', self::SESSION_MESSAGES_KEY));
        }

        $this->baseSet($key, $value);
    }

    /**
     * Get the session cookie options
     *
     * @return array{expires: int, path: string, secure: bool, httpOnly: bool, sameSite: Cookie::SAMESITE_LAX|Cookie::SAMESITE_NONE|Cookie::SAMESITE_STRICT}
     */
    protected function getCookieOptions(): array
    {
        $options = [
            'expires'  => 0,
            'path'     => $this->request->root(),
            'secure'   => $this->request->isSecure(),
            'httpOnly' => true,
            'sameSite' => Cookie::SAMESITE_STRICT,
        ];

        if ($this->duration > 0) {
            $options['expires'] = time() + $this->duration;
        }

        return $options;
    }
}
