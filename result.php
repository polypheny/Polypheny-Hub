<?php
class Result {
	// fields must be public, so they will be json_encoded
	public $message;
	public $data;// string[][]
	public $header;// string[]
	public $error;//string

	//login
	public $id;
	public $user;
	public $secret;
	public $loginStatus;//2 if admin, 1 if user

	/*
	public $currentPage;//number
	public $highestPage;//number
	public $table;//string
	public $tables;//string[]
	public $error;//string
	public $info;//Debug
	public $type;//string "table" or "view"
	*/

	function loginResult( $id, $user, $secret, $admin ){
		$this->id = $id;
		$this->user = $user;
		$this->secret = $secret;
		if($admin == 1){
			$this->loginStatus = 2;
		} else {
			$this->loginStatus = 1;
		}
		return $this;
	}

	public function header( $header ){
		$this->header = $header;
		return $this;
	}

	public function data( $data ){
		$this->data = $data;
		return $this;
	}

	public function error( $error ){
		$this->error = $error;
		return $this;
	}

	public function message( $msg ){
		$this->message = $msg;
		return $this;
	}

	public function asJson() {
		return json_encode($this);
	}
}
