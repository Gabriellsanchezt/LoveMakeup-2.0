<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Catalogo extends Conexion {
    private $conex1;
    private $conex2;

    public function __construct() {
        parent::__construct(); // Llama al constructor de la clase padre

        // Obtener las conexiones de la clase padre
        $this->conex1 = $this->getConex1();
        $this->conex2 = $this->getConex2();
    
         // Verifica si las conexiones son exitosas
        if (!$this->conex1) {
            die('Error al conectar con la primera base de datos');
        }

        if (!$this->conex2) {
            die('Error al conectar con la segunda base de datos');
        }
    }
    

    public function obtenerProductosActivos() {
        $sql = "SELECT * FROM producto WHERE estatus = 1";  // Filtra por productos activos (estatus = 1)
        $consulta = $this->conex1->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }
    

    public function obtenerPorCategoria($categoriaId) {
        $sql = "
            SELECT 
                producto.*, 
                categoria.nombre AS nombre_categoria 
            FROM 
                producto 
            INNER JOIN 
                categoria ON producto.id_categoria = categoria.id_categoria
            WHERE 
                producto.estatus = 1 AND producto.id_categoria = :categoriaId
        ";  // Filtra por estatus y categoría
        $consulta = $this->conex1->prepare($sql);
        $consulta->bindParam(':categoriaId', $categoriaId);
        $consulta->execute();
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    
    public function obtenerCategorias() {
      $sql = "SELECT id_categoria, nombre FROM categoria WHERE estatus = 1"; // Solo lo necesario
      $consulta = $this->conex1->prepare($sql);
      $consulta->execute();
      return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }
   
    public function buscarProductos($termino) {
        $sql = "
            SELECT * 
            FROM producto 
            WHERE estatus = 1 
              AND (nombre LIKE :busqueda OR marca LIKE :busqueda)
        ";
        $consulta = $this->conex1->prepare($sql);
        $busqueda = '%' . $termino . '%';
        $consulta->bindParam(':busqueda', $busqueda, \PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }
}





?>