<?php

namespace LoveMakeup\Proyecto\Modelo;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use LoveMakeup\Proyecto\Config\Conexion;

class PedidoWeb extends Conexion {
   

     function __construct() {
        parent::__construct();
      
    }

    public function procesarPedidoweb($jsonDatos){
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];

        try {
            switch($operacion){
                case 'confirmar':
                    return $this->confirmarPedido($datosProcesar);

                case 'eliminar':
                    return $this->eliminarPedido($datosProcesar);
                
                    

                case 'delivery':
                        return $this->actualizarDelivery($datosProcesar); 
                           
                 
                case 'tracking':
                         return $this->actualizarTracking($datosProcesar);  
                
                case  'enviar':
                    return $this->enviarPedido($datosProcesar);  
                
                case 'entregar':   
                     return $this->entregarPedido($datosProcesar);  

                default:
                    return    ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e){
                return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
        }
    }
    public function consultarPedidosCompletos() {

        $conex1 = $this->getConex1();
    
        try {
    
            // ======================================================
            // 1) CONSULTA PRINCIPAL (BD1) - INCLUYE REFERENCIA Y COMPROBANTE
            // ======================================================
            $sql = "SELECT 
                        p.id_pedido,
                        p.tipo,
                        p.fecha,
                        p.estatus,
                        p.precio_total_bs,
                        p.precio_total_usd,
                        p.tracking,
                        p.cedula,                  -- viene del cliente (persona)
                        p.id_direccion,
                        p.id_pago,
    
                        d.direccion_envio AS direccion,
                        d.sucursal_envio AS sucursal,
    
                        me.id_entrega AS id_metodoentrega,
                        me.nombre AS metodo_entrega,
                        me.descripcion AS descripcion_entrega,
    
                        dp.id_pago AS detalle_pago_id,
                        dp.monto AS pago_monto,
                        dp.monto_usd AS pago_monto_usd,
                        dp.id_metodopago AS id_metodopago,
    
                        rp.referencia AS referencia_bancaria,
                        rp.banco_emisor,
                        rp.banco_receptor,
                        rp.telefono_emisor,
    
                        cp.imagen AS comprobante_imagen,
    
                        mp.id_metodopago AS mp_id,
                        mp.nombre AS metodo_pago,
                        mp.descripcion AS descripcion_pago
    
                    FROM pedido p
                    LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
                    LEFT JOIN metodo_entrega me ON d.id_metodoentrega = me.id_entrega
    
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
            // 2) CONSULTA CLIENTE EN BD2 - CORREGIDA
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
            // 3) ASOCIAR CLIENTE Y LIMPIEZA DE CAMPOS
            // ======================================================
            foreach ($pedidos as &$p) {
    
                // Mapear campos de pago/referencia/comprobante (si vienen)
                $p['id_pago'] = $p['id_pago'] ?? null;
                $p['detalle_pago_id'] = $p['detalle_pago_id'] ?? null;
                $p['pago_monto'] = isset($p['pago_monto']) ? $p['pago_monto'] : null;
                $p['pago_monto_usd'] = isset($p['pago_monto_usd']) ? $p['pago_monto_usd'] : null;
                $p['id_metodopago'] = $p['id_metodopago'] ?? null;
    
                $p['referencia_bancaria'] = $p['referencia_bancaria'] ?? null;
                $p['banco_emisor'] = $p['banco_emisor'] ?? null;
                $p['banco_receptor'] = $p['banco_receptor'] ?? null;
                $p['telefono_emisor'] = $p['telefono_emisor'] ?? null;
                $p['comprobante_imagen'] = $p['comprobante_imagen'] ?? null;
    
                // Cliente: si existe cédula en pedido, buscamos en BD2
                if (!empty($p['cedula'])) {
                    try {
                        $stmtCliente->execute([
                            'cedula' => $p['cedula']
                        ]);
    
                        $cliente2 = $stmtCliente->fetch(\PDO::FETCH_ASSOC);
    
                        if ($cliente2) {
                            $p['nombre_cliente']   = $cliente2['nombre'];
                            $p['apellido_cliente'] = $cliente2['apellido'];
                            $p['telefono']         = $cliente2['telefono'];
                            $p['correo_cliente']   = $cliente2['correo'];
                            $p['estatus_usuario']  = $cliente2['estatus'];
                        } else {
                            // No existe en BD2 — dejar la info mínima que haya en BD1
                            $p['nombre_cliente']   = $p['nombre_cliente'] ?? 'No registrado';
                            $p['apellido_cliente'] = $p['apellido_cliente'] ?? '';
                            $p['telefono']         = $p['telefono'] ?? null;
                            $p['correo_cliente']   = $p['correo_cliente'] ?? null;
                            $p['estatus_usuario']  = null;
                        }
                    } catch (\PDOException $e) {
                        // Error consultando BD2 → valores por defecto
                        $p['nombre_cliente']   = $p['nombre_cliente'] ?? 'No registrado';
                        $p['apellido_cliente'] = $p['apellido_cliente'] ?? '';
                        $p['telefono']         = $p['telefono'] ?? null;
                        $p['correo_cliente']   = $p['correo_cliente'] ?? null;
                        $p['estatus_usuario']  = null;
                    }
                } else {
                    // Pedido sin cédula
                    $p['nombre_cliente']   = $p['nombre_cliente'] ?? 'Desconocido';
                    $p['apellido_cliente'] = $p['apellido_cliente'] ?? '';
                    $p['telefono']         = $p['telefono'] ?? null;
                    $p['correo_cliente']   = $p['correo_cliente'] ?? null;
                    $p['estatus_usuario']  = null;
                }
    
                // (opcional) Normalizar nombre del método de pago/entrega
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
    
    

public function consultarDetallesPedido($id_pedido) {
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

    private function confirmarPedido($id_pedido) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
    
            // Obtener el método de entrega del pedido
            $sqlMetodo = "SELECT me.id_entrega, me.nombre FROM pedido p
                          LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
                          LEFT JOIN metodo_entrega me ON d.id_metodoentrega = me.id_entrega
                          WHERE p.id_pedido = ?";
            $stmtMetodo = $conex->prepare($sqlMetodo);
            $stmtMetodo->execute([$id_pedido]);
            $metodo = $stmtMetodo->fetch(\PDO::FETCH_ASSOC);
    
            if (!$metodo) {
                $conex->rollBack();
                return ['respuesta' => 0, 'msg' => 'No se encontró el método de entrega'];
            }
    
            // Decidir el nuevo estatus según método de entrega
            $nuevoestatus = 2;  // Default
            if (strtolower($metodo['nombre']) === 'delivery' || $metodo['id_entrega'] == 2) {
                $nuevoestatus = 3;
            }
    
            $sql = "UPDATE pedido SET estatus = ? WHERE id_pedido = ?";
            $stmt = $conex->prepare($sql);
            if ($stmt->execute([$nuevoestatus, $id_pedido])) {
                $conex->commit();
                $conex = null;
                return ['respuesta' => 1, 'msg' => 'Pedido confirmado'];
            } else {
                $conex->rollBack();
                $conex = null;
                return ['respuesta' => 0, 'msg' => 'No se pudo confirmar el pedido'];
            }
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    private function enviarPedido($id_pedido) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            $sql = "UPDATE pedido SET estatus = 4 WHERE id_pedido = ?";
            $stmt = $conex->prepare($sql);
            if ($stmt->execute([$id_pedido])) {
                $conex->commit();  // <-- Aquí debes confirmar la transacción
                $conex = null;
                return ['respuesta' => 1, 'msg' => 'Pedido confirmado'];
            } else {
                $conex->rollBack();
                $conex = null;
                return ['respuesta' => 'error', 'msg' => 'No se pudo confirmar el pedido'];
            }
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    private function entregarPedido($id_pedido) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            $sql = "UPDATE pedido SET estatus = 5 WHERE id_pedido = ?";
            $stmt = $conex->prepare($sql);
            if ($stmt->execute([$id_pedido])) {
                $conex->commit();  // <-- Aquí debes confirmar la transacción
                $conex = null;
                return ['respuesta' => 1, 'msg' => 'Pedido confirmado'];
            } else {
                $conex->rollBack();
                $conex = null;
                return ['respuesta' => 'error', 'msg' => 'No se pudo confirmar el pedido'];
            }
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }
    
    




    private function actualizarDelivery($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
    
            $sql = "UPDATE pedido SET estatus = ?, direccion = ? WHERE id_pedido = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['estatus_delivery'], $datos['direccion'], $datos['id_pedido']]);
    
            $conex->commit();
            return ['respuesta' => 1, 'msg' => 'estatus actualizado correctamente'];
        } catch (\Exception $e) {
            $conex->rollBack();
            error_log("Error al actualizar delivery: " . $e->getMessage());
            return ['respuesta' => 0, 'msg' => 'Error al actualizar estatus'];
        }
    }
    

    private function actualizarTracking($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
    
            $sql = "UPDATE pedido SET tracking = ? WHERE id_pedido = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['tracking'], $datos['id_pedido']]);
    
            $conex->commit();
    
            // Enviar correo al cliente
            $this->enviarCorreoTracking($datos['correo_cliente'], $datos['tracking'], $datos['nombre_cliente']);
    
            return ['respuesta' => 1, 'msg' => 'Tracking actualizado y correo enviado'];
        } catch (\Exception $e) {
            $conex->rollBack();
            error_log("Error al actualizar tracking: " . $e->getMessage());
            return ['respuesta' => 0, 'msg' => 'Error al actualizar el tracking'];
        }
    }


    private function enviarCorreoTracking($correo, $tracking, $nombre_cliente) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'lovemakeupca.help@gmail.com'; // Tu dirección de correo de Gmail
            $mail->Password = 'jzvfcfjqfggndqdl'; // Tu contraseña de Gmail o contraseña de aplicación
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
    
            $mail->setFrom('lovemakeupca.help@gmail.com', 'Love Makeup');
            $mail->addAddress($correo, $nombre_cliente);
            $mail->Subject = 'Informacion de Envio: Numero de Tracking';
            $mail->isHTML(true);


            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2 style='color:#df059d;'>¡Tu pedido ya fue enviado!</h2>
                <p>Hola <strong>$nombre_cliente</strong>,</p>
                <p>Gracias por tu compra en <strong>LoveMakeup C.A</strong>.</p>
                <p>Tu número de tracking es: <strong style='font-size: 18px;'>$tracking</strong></p>
                <p>Podrás utilizarlo para rastrear tu pedido.</p>
                <hr>
                <p>Si tienes alguna duda, contáctanos:</p>
                 <p><strong>LoveMakeup C.A</strong> es tu mejor aliado en productos de belleza y maquillaje. ¡Descubre tu mejor versión con nosotros!</p>

      <p>Telf.: +58 424 5115414<br> Correo: <a href='mailto:help.lovemakeupca@gmail.com'>help.lovemakeupca@gmail.com</a></p>

<!-- Redes Sociales -->
<div style='text-align: center; margin-top: 30px;'>
  <a href='https://www.instagram.com/lovemakeupyk/' target='_blank' style='margin: 0 10px;'>
    <img src='https://cdn-icons-png.flaticon.com/24/1384/1384031.png' alt='Instagram' style='vertical-align: middle;'>
  </a>
  <a href='https://www.facebook.com/lovemakeupyk/' target='_blank' style='margin: 0 10px;'>
    <img src='https://cdn-icons-png.flaticon.com/24/1384/1384005.png' alt='Facebook' style='vertical-align: middle;'>
  </a>
  <a href='https://wa.me/584245115414' target='_blank' style='margin: 0 10px;'>
    <img src='https://cdn-icons-png.flaticon.com/24/733/733585.png' alt='WhatsApp' style='vertical-align: middle;'>
  </a>
</div>
                <p style='font-size: 12px; color: #888;'>© 2025 LoveMakeup C.A. Todos los derechos reservados.</p>
            </body>
            </html>";
    
            $mail->send();
        } catch (\Exception $e) {
            error_log("Error al enviar correo tracking: " . $e->getMessage());
            // Nunca interrumpas el flujo por esto
        }
    }

   
}

?>
