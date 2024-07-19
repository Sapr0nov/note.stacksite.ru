<?php

namespace App\Classes\dbController;

class DbClass
{
    public $MYSQLI;
    function __construct($server='127.0.0.1', $user='', $pswd='', $db=''){
        $this->MYSQLI = new \mysqli($server, $user, $pswd, $db);
        if ($this->MYSQLI->connect_errno) {
            throw new \Exception($this->MYSQLI->connect_error, 1);
        }
    }

}
?>