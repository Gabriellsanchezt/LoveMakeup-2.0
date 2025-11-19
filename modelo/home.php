<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Home extends Conexion {

    public function __construct() {
        parent::__construct();
    }

    public function consultarMasVendidos() {
    $conex = $this->getConex1();
    try {
        $sql = "
            SELECT 
                producto.id_producto,
                producto.nombre AS nombre_producto,
                SUM(pedido_detalles.cantidad) AS cantidad_vendida,
                SUM(pedido_detalles.cantidad * pedido_detalles.precio_unitario) AS total_vendido
            FROM producto
            INNER JOIN pedido_detalles 
                ON producto.id_producto = pedido_detalles.id_producto
            INNER JOIN pedido 
                ON pedido.id_pedido = pedido_detalles.id_pedido
            WHERE pedido.estatus = '2'
            GROUP BY producto.id_producto, producto.nombre
            ORDER BY cantidad_vendida DESC
            LIMIT 5
        ";

        $stmt = $conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);

    } catch (\PDOException $e) {
        throw $e;
    }
}


    public function consultarTotales() {
    $conex = $this->getConex1();
    try {
        $sql = "
            SELECT 
                SUM(precio_total_usd) AS total_ventas,
                SUM(CASE WHEN tipo = '2' THEN precio_total_usd ELSE 0 END) AS total_web,
                COUNT(CASE WHEN tipo = '2' THEN 1 END) AS cantidad_pedidos_web
            FROM pedido
            WHERE estatus = '2'
        ";

        $stmt = $conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);

    } catch (\PDOException $e) {
        throw $e;
    }
}

    public function consultarTotalesPendientes() {
    $conex = $this->getConex1();
    try {
        $sql = "
            SELECT 
                COUNT(id_pedido) AS cantidad_pedidos_pendientes
            FROM pedido
            WHERE estatus = '1'
              AND tipo IN (2, 3)
        ";

        $stmt = $conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);

    } catch (\PDOException $e) {
        throw $e;
    }
}

}
?>