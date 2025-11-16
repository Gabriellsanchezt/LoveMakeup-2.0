<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Catalogo extends Conexion {
    private $objcategoria;
    private $objproducto;

    public function __construct() {
        parent::__construct();
        $this->objcategoria = new Categoria();
        $this->objproducto = new Producto();
    }

    public function obtenerProductosMasVendidos() {
        return $this->objproducto->MasVendidos();
    }

    public function obtenerProductosActivos() {
        return $this->objproducto->ProductosActivos();
    }

    public function obtenerPorCategoria($categoriaId) {
        $conex = $this->getConex1();
        try {
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
            ";
            $stmt = $conex->prepare($sql);
            $stmt->bindParam(':categoriaId', $categoriaId, \PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    public function obtenerCategorias() {
        return $this->objcategoria->consultar();
    }

    public function buscarProductos($termino) {
        $conex = $this->getConex1();
        try {
            $sql = "
                SELECT *  
                FROM producto  
                WHERE estatus = 1  
                  AND (nombre LIKE :busqueda OR marca LIKE :busqueda)
            ";
            $stmt = $conex->prepare($sql);
            $busqueda = '%' . $termino . '%';
            $stmt->bindParam(':busqueda', $busqueda, \PDO::PARAM_STR);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }


    /*tasa dolar*/
    public function consultaTasaUltima() {
    $conex = $this->getConex1();
    try {
        $sql = "SELECT tasa_bs FROM tasa_dolar ORDER BY fecha DESC LIMIT 1";
        $stmt = $conex->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetchColumn();
        $conex = null;
        return $resultado;
    } catch (\PDOException $e) {
        if ($conex) {
            $conex = null;
        }
        throw $e;
    }
    }
}
?>