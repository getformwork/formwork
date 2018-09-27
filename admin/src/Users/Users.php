<?php

namespace Formwork\Admin\Users;

use Formwork\Data\Collection;
use Formwork\Parsers\YAML;
use Formwork\Utils\FileSystem;

class Users extends Collection
{
    public function has($user)
    {
        return isset($this->items[$user]);
    }

    public function get($user)
    {
        if ($this->has($user)) {
            return $this->items[$user];
        }
    }

    public static function load()
    {
        $users = array();
        foreach (FileSystem::listFiles(ACCOUNTS_PATH) as $file) {
            $parsedData = YAML::parseFile(ACCOUNTS_PATH . $file);
            $users[$parsedData['username']] = new User($parsedData);
        }
        return new static($users);
    }
}
