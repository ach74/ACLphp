<?php

class Database {

    private $host = "localhost";
    private $db_name = "acl_test";
    private $username = "root";
    private $password = "";
    private $con;

    public function getConexion() {
        try {
            $this->con = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->username, $this->password);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->con;
    }

}
