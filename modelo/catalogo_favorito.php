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
    

    public function obtenerProductosActivos() {
        return $this->objproducto->ProductosActivos();
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
        return $this->objcategoria->consultar();
    }
   
    public function buscarProductos($termino) {
    $conex = $this->getConex1();
    try {
        $sql = "
            SELECT p.*,
                   c.nombre AS nombre_categoria,
                   m.nombre AS nombre_marca,
                   (
                     SELECT pi.url_imagen
                     FROM producto_imagen pi
                     WHERE pi.id_producto = p.id_producto
                     ORDER BY pi.id_imagen ASC
                     LIMIT 1
                   ) AS imagen
            FROM producto p
            INNER JOIN categoria c ON p.id_categoria = c.id_categoria
            INNER JOIN marca m ON p.id_marca = m.id_marca
            WHERE p.estatus = 1
              AND (p.nombre LIKE :busqueda OR m.nombre LIKE :busqueda)
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

}
?>