<?php

namespace Formwork\Http\Session;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;
use Formwork\Http\Request;
use Formwork\Http\Utils\Cookie;
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

    protected const SESSION_NAME = 'formwork_session';

    protected const SESSION_MESSAGES_KEY = '_formwork_messages';

    protected const SESSION_ID_REGEX = '/^[a-z0-9,-]{22,256}$/i';

    protected Request $request;

    protected Messages $messages;

    protected string $name = self::SESSION_NAME;

    protected bool $started = false;

    protected int $duration = 0;

    public function __construct(Request $request)
    {
        if (!extension_loaded('session')) {
            throw new RuntimeException('Sessions extension not available');
        }

        if (session_status() === PHP_SESSION_DISABLED) {
            throw new RuntimeException('Sessions disabled by PHP configuration');
        }

        $this->request = $request;
    }

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

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session already started');
        }

        session_name($this->name);

        if (!session_id()) {
            $id = $this->request->cookies()->get($this->name, '');

            if (!preg_match(self::SESSION_ID_REGEX, $id)) {
                $id = '';
            }

            session_id($id);
        }

        session_start([
            'use_strict_mode' => true,
        ]);

        if (($id = session_id()) === false) {
            throw new RuntimeException('Cannot get session id');
        }

        Cookie::send($this->name, $id, $this->getCookieOptions());

        $this->data = &$_SESSION;

        $this->started = true;
    }

    public function destroy(): void
    {
        session_destroy();

        Cookie::remove($this->name, $this->getCookieOptions());

        $this->started = false;
    }

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

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if ($this->started) {
            throw new RuntimeException('Cannot set session name: session already started');
        }

        $this->name = $name;
    }

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
