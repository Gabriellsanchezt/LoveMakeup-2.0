<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Catalogopedido extends Conexion{
  
   
    public function __construct() {
        parent::__construct(); // Llama al constructor de la clase padre

   
    }
    
    
    public function consultarPedidosCompletosCatalogo() {

        $conex1 = $this->getConex1();
    
        try {
    
            // ======================================================
            // 1) CONSULTA PRINCIPAL (BD1) - INCLUYE REFERENCIA, COMPROBANTE Y DELIVERY
            // ======================================================
            $sql = "SELECT 
                        p.id_pedido,
                        p.tipo,
                        p.fecha,
                        p.estatus,
                        p.precio_total_bs,
                        p.precio_total_usd,
                        p.tracking,
                        p.cedula,
                        p.id_direccion,
                        p.id_pago,
    
                        -- DIRECCIÓN
                        d.direccion_envio AS direccion,
                        d.sucursal_envio AS sucursal,
                        d.id_delivery,
    
                        -- DELIVERY
                        del.nombre AS delivery_nombre,
                        del.tipo AS delivery_tipo,
                        del.contacto AS delivery_contacto,
    
                        -- MÉTODO ENTREGA
                        me.id_entrega AS id_metodoentrega,
                        me.nombre AS metodo_entrega,
                        me.descripcion AS descripcion_entrega,
    
                        -- PAGO
                        dp.id_pago AS detalle_pago_id,
                        dp.monto AS pago_monto,
                        dp.monto_usd AS pago_monto_usd,
                        dp.id_metodopago AS id_metodopago,
    
                        -- REFERENCIA
                        rp.referencia AS referencia_bancaria,
                        rp.banco_emisor,
                        rp.banco_receptor,
                        rp.telefono_emisor,
    
                        -- COMPROBANTE
                        cp.imagen AS comprobante_imagen,
    
                        -- MÉTODO PAGO
                        mp.id_metodopago AS mp_id,
                        mp.nombre AS metodo_pago,
                        mp.descripcion AS descripcion_pago
    
                    FROM pedido p
                    LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
                    LEFT JOIN metodo_entrega me ON d.id_metodoentrega = me.id_entrega
    
                    -- AÑADIDO DELIVERY
                    LEFT JOIN delivery del ON d.id_delivery = del.id_delivery
    
                    LEFT JOIN detalle_pago dp ON p.id_pago = dp.id_pago
                    LEFT JOIN referencia_pago rp ON dp.id_pago = rp.id_pago
                    LEFT JOIN comprobante_pago cp ON dp.id_pago = cp.id_pago
    
                    LEFT JOIN metodo_pago mp ON dp.id_metodopago = mp.id_metodopago
    
                    WHERE p.tipo = 2
                    ORDER BY p.fecha DESC";
    
            $stmt = $conex1->prepare($sql);
            $stmt->execute();
            $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
            if (empty($pedidos)) {
                return [];
            }
    
            // ======================================================
            // 2) CONSULTA CLIENTE EN BD2 - IGUAL QUE TENÍAS
            // ======================================================
            $conex2 = $this->getConex2();
    
            $sqlCliente = "SELECT 
                                per.cedula,
                                per.nombre,
                                per.apellido,
                                per.telefono,
                                per.correo,
                                u.estatus
                           FROM usuario u
                           INNER JOIN persona per ON u.cedula = per.cedula
                           WHERE per.cedula = :cedula";
    
            $stmtCliente = $conex2->prepare($sqlCliente);
    
            // ======================================================
            // 3) ASOCIAR CLIENTE Y LIMPIEZA DE CAMPOS (NO SE TOCÓ)
            // ======================================================
            foreach ($pedidos as &$p) {
    
                // Normalizar campos opcionales
                $p['id_pago'] = $p['id_pago'] ?? null;
                $p['detalle_pago_id'] = $p['detalle_pago_id'] ?? null;
                $p['pago_monto'] = $p['pago_monto'] ?? null;
                $p['pago_monto_usd'] = $p['pago_monto_usd'] ?? null;
    
                $p['referencia_bancaria'] = $p['referencia_bancaria'] ?? null;
                $p['banco_emisor'] = $p['banco_emisor'] ?? null;
                $p['banco_receptor'] = $p['banco_receptor'] ?? null;
                $p['telefono_emisor'] = $p['telefono_emisor'] ?? null;
                $p['comprobante_imagen'] = $p['comprobante_imagen'] ?? null;
    
                // Cliente en BD2
                if (!empty($p['cedula'])) {
                    try {
                        $stmtCliente->execute(['cedula' => $p['cedula']]);
                        $cliente2 = $stmtCliente->fetch(\PDO::FETCH_ASSOC);
    
                        if ($cliente2) {
                            $p['nombre_cliente']   = $cliente2['nombre'];
                            $p['apellido_cliente'] = $cliente2['apellido'];
                            $p['telefono']         = $cliente2['telefono'];
                            $p['correo_cliente']   = $cliente2['correo'];
                            $p['estatus_usuario']  = $cliente2['estatus'];
                        } else {
                            $p['nombre_cliente']   = 'No registrado';
                            $p['apellido_cliente'] = '';
                            $p['telefono'] = null;
                            $p['correo_cliente'] = null;
                            $p['estatus_usuario'] = null;
                        }
                    } catch (\PDOException $e) {
                        $p['nombre_cliente']   = 'No registrado';
                        $p['apellido_cliente'] = '';
                        $p['telefono'] = null;
                        $p['correo_cliente'] = null;
                        $p['estatus_usuario'] = null;
                    }
                } else {
                    $p['nombre_cliente']   = 'Desconocido';
                    $p['apellido_cliente'] = '';
                    $p['telefono'] = null;
                    $p['correo_cliente'] = null;
                    $p['estatus_usuario'] = null;
                }
    
                $p['metodo_entrega'] = $p['metodo_entrega'] ?? null;
                $p['metodo_pago'] = $p['metodo_pago'] ?? null;
            }
    
            unset($p);
    
            return $pedidos;
    
        } catch (\PDOException $e) {
            error_log("Error en consultarPedidosCompletos: " . $e->getMessage());
            throw $e;
        }
    }
    
    
    

public function consultarDetallesPedidoCatalogo($id_pedido) {
    $sql = "SELECT 
                pd.id_producto,
                pr.nombre AS nombre,
                pr.descripcion,
                pd.cantidad,
                pd.precio_unitario,
                (pd.cantidad * pd.precio_unitario) AS subtotal
            FROM pedido_detalles pd
            JOIN producto pr ON pd.id_producto = pr.id_producto
            WHERE pd.id_pedido = ?";

    $stmt = $this->getConex1()->prepare($sql);
    $stmt->execute([$id_pedido]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}
    private function eliminarPedido($id_pedido) {
        try {
            $conex = $this->getconex1();
            $conex->beginTransaction();

            $sqlDetalles = "SELECT id_producto, cantidad FROM pedido_detalles WHERE id_pedido = ?";
            $stmtDetalles = $conex->prepare($sqlDetalles);
            $stmtDetalles->execute([$id_pedido]);
            $detalles = $stmtDetalles->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($detalles as $detalle) {
                $sqlUpdateStock = "UPDATE producto SET stock_disponible = stock_disponible + ? WHERE id_producto = ?";
                $stmtStock = $conex->prepare($sqlUpdateStock);
                $stmtStock->execute([$detalle['cantidad'], $detalle['id_producto']]);
            }

            $sqlEliminar = "UPDATE pedido SET estatus = 0 WHERE id_pedido = ?";
            $stmtEliminar = $conex->prepare($sqlEliminar);
            $stmtEliminar->execute([$id_pedido]);

            $conex->commit();
         
            $conex = null;
            return ['respuesta' => 1, 'msg' => 'Pedido eliminado correctamente'];
        } catch (\Exception $e) {
            $conex->rollBack();
            error_log("Error al eliminar pedido: " . $e->getMessage());
            return ['respuesta' => 0, 'msg' => 'Error al eliminar el pedido'];
            $conex = null;
        
        }
    }
}
 

?>