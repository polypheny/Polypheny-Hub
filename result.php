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

    //get users
    public $users = [];

    //hub datasets
    public $datasets = [];

    /*
    public $currentPage;//number
    public $highestPage;//number
    public $table;//string
    public $tables;//string[]
    public $error;//string
    public $info;//Debug
    public $type;//string "table" or "view"
    */

    function loginResult( $id, $user, $secret, $admin ) {
        $this->id = $id;
        $this->user = $user;
        $this->secret = $secret;
        if ( $admin == 1 ) {
            $this->loginStatus = 2;
        } else {
            $this->loginStatus = 1;
        }
        return $this;
    }

    public function header( $header ) {
        $this->header = $header;
        return $this;
    }

    public function data( $data ) {
        $this->data = $data;
        return $this;
    }

    public function addDataset ( $ds ) {
        array_push( $this->datasets, $ds );
    }

    public function addUser ( $user ) {
        array_push( $this->users, $user );
    }

    public function error( $error ) {
        $this->error = $error;
        return $this;
    }

    public function message( $msg ) {
        $this->message = $msg;
        return $this;
    }

    public function asJson() {
        return json_encode( $this );
    }
}

class Dataset {
    public $name;
    public $description;
    public $lines;
    public $zipSize;
    public $uploaded;
    public $pub;
    public $dsId;
    public $file;
    public $username;
    public $userId;

    function __construct( $name, $description, $lines, $zipSize, $uploaded, $pub, $dsId, $file, $username, $userId ) {
        $this->name = $name;
        $this->description = $description;
        $this->lines = $lines;
        $this->zipSize = $zipSize;
        $this->uploaded = $uploaded;
        $this->pub = $pub;
        $this->dsId = $dsId;
        $this->file = $file;
        $this->username = $username;
        $this->userId = $userId;
    }
}

class HubUser {
    public $id;
    public $name;
    public $email;
    public $admin;

    public function __construct($id, $user, $email, $isAdmin) {
        $this->id = $id;
        $this->name = $user;
        $this->email = $email;
        $this->admin = $isAdmin;
    }

}
