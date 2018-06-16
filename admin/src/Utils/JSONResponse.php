<?php

namespace Formwork\Admin\Utils;
use Formwork\Utils\Header;

class JSONResponse {

	protected $status;
	protected $data;

	public function __construct($data, $status = 200) {
		$this->status = $status;
		$this->data = $data;
	}

	public function send() {
		Header::contentType('application/json; charset=utf-8');
		if ($this->status != 200) Header::status($this->status);
		echo json_encode($this->data);
		exit;
	}

	public static function success($message, $status = 200, $data = array()) {
		return new static(array(
			'status' => 'success',
			'message' => $message,
			'code' => $status,
			'data' => $data
		), $status);
	}

	public static function error($message, $status = 400, $data = array()) {
		return new static(array(
			'status' => 'error',
			'message' => $message,
			'code' => $status,
			'data' => $data
		), $status);
	}

}
