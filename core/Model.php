<?php
class Model {
    protected $db;
    protected $internaldb;


    public function __construct() {
        $this->db = new Database();

    }
}