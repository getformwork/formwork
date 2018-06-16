<?php

namespace Formwork\Admin;
use Formwork\Core\Formwork;
use Formwork\Data\Collection;
use Formwork\Utils\FileSystem;
use Exception;
use Spyc;

class Users extends Collection {

	public function has($user) {
		return isset($this->items[$user]);
	}

	public function get($user) {
		if ($this->has($user)) return $this->items[$user];
	}

	public static function load() {
		$users = array();
		foreach (FileSystem::listFiles(ACCOUNTS_PATH) as $file) {
			$parsedData = Spyc::YAMLLoadString(FileSystem::read(ACCOUNTS_PATH . $file));
			$users[$parsedData['username']] = new User($parsedData);
		}
		return new static($users);
	}

}
