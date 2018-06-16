<?php

namespace Formwork\Admin\Utils;

class Log extends Registry {

	protected $limit;

	public function __construct($filename, $limit = 128) {
		parent::__construct($filename);
		$this->limit = $limit;
	}

	public function set() {
		list($value) = func_get_args();
		$timestamp = (string) microtime(true);
		parent::set($timestamp, $value);
		return $timestamp;
	}

	public function save() {
		if (count($this->storage) > $this->limit) {
			$this->storage = array_slice($this->storage, -$this->limit, null, true);
		}
		parent::save();
	}

}
