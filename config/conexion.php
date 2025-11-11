<?php

namespace LoveMakeup\Proyecto\Config;

require_once (__DIR__.'/config.php');

class Conexion {
    private $conex1;
    private $conex2;

    public function __construct() {
        try {
            // Primera conexión
            $this->conex1 = new \PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME_1.";charset=utf8", DB_USER, DB_PASS);
            $this->conex1->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Segunda conexión
            $this->conex2 = new \PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME_2.";charset=utf8", DB_USER, DB_PASS);
            $this->conex2->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        } catch (\PDOException $e) {
            die ("Conexión Fallida: ".$e->getMessage());
        }
    }

    public function getConex1() {
        return $this->conex1;
    }

    public function getConex2() {
        return $this->conex2;
    }
}
?>