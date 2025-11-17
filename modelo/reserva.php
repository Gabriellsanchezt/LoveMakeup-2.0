<?php

namespace LoveMakeup\Proyecto\Modelo;

require_once(__DIR__ . '/../config/conexion.php');
use LoveMakeup\Proyecto\Config\Conexion;

class Reservas extends Conexion {
    public function __construct() {
        parent::__construct();
    }

    public function procesarReserva($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'] ?? null;

        try {
            switch ($operacion) {
                case 'eliminar':
                    return $this->eliminarReserva($datosProcesar);
                case 'cambiar_estado':
                    return $this->cambiarEstadoReserva($datosProcesar);
                case 'consultar':
                    return $this->consultarReservasCompletas();
                case 'consultar_personas':
                    return $this->consultarPersonas();
                case 'consultar_productos':
                    return $this->consultarProductos();
                case 'consultar_reserva':
                    return $this->consultarReserva($datosProcesar);
                case 'consultar_detalle':
                    return $this->consultarDetallesReserva($datosProcesar);
                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }

    private function eliminarReserva($id) {
        $conex = null;
        try {
            $conex = $this->getConex1();
            $conex->beginTransaction();

            $sqlDetalles = "SELECT id_producto, cantidad FROM pedido_detalles WHERE id_pedido = ?";
            $stmtDetalles = $conex->prepare($sqlDetalles);
            $stmtDetalles->execute([$id]);
            $detalles = $stmtDetalles->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($detalles as $detalle) {
                $sqlUpdateStock = "UPDATE producto SET stock_disponible = stock_disponible + ? WHERE id_producto = ?";
                $stmtStock = $conex->prepare($sqlUpdateStock);
                $stmtStock->execute([$detalle['cantidad'], $detalle['id_producto']]);
            }

            $sqlEliminar = "UPDATE pedido SET estatus = '0' WHERE id_pedido = ?";
            $stmtEliminar = $conex->prepare($sqlEliminar);
            $stmtEliminar->execute([$id]);

            $conex->commit();
            $conex = null;
            return ['respuesta' => 1, 'msg' => 'Reserva eliminada correctamente'];
        } catch (\Exception $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            return ['respuesta' => 0, 'msg' => 'Error al eliminar la reserva'];
        }
    }

    private function cambiarEstadoReserva($datos) {
        $conex = $this->getConex1();
        try {
            $sql = "UPDATE pedido SET estatus = ? WHERE id_pedido = ?";
            $stmt = $conex->prepare($sql);
            if ($stmt->execute([$datos['estado'], $datos['id_pedido']])) {
                $conex = null;
                return ['respuesta' => 1, 'msg' => 'Estado actualizado'];
            } else {
                $conex = null;
                return ['respuesta' => 0, 'msg' => 'No se pudo actualizar el estado'];
            }
        } catch (\Exception $e) {
            if ($conex) $conex = null;
            return ['respuesta' => 0, 'msg' => 'Error al actualizar el estado: ' . $e->getMessage()];
        }
    }

    public function consultarReservasCompletas() {
        $conex1 = $this->getConex1();
        $conex2 = $this->getConex2();
        
        try {
            // Consultar pedidos de reserva (tipo = '3') desde la base de datos 1
            $sql = "SELECT 
                        p.id_pedido,
                        p.tipo,
                        p.fecha,
                        p.estatus as estado,
                        p.precio_total_bs,
                        p.precio_total_usd,
                        p.id_pago,
                        p.cedula,
                        rp.banco_emisor as banco,
                        cp.imagen
                    FROM pedido p
                    LEFT JOIN detalle_pago dp ON p.id_pago = dp.id_pago
                    LEFT JOIN referencia_pago rp ON dp.id_pago = rp.id_pago
                    LEFT JOIN comprobante_pago cp ON dp.id_pago = cp.id_pago
                    WHERE p.tipo = '3'
                    ORDER BY p.fecha DESC";

            $stmt = $conex1->prepare($sql);
            $stmt->execute();
            $reservas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Obtener datos del cliente desde la base de datos 2 (usuario y persona)
            if (!empty($reservas) && $conex2) {
                foreach ($reservas as &$reserva) {
                    if (!empty($reserva['cedula'])) {
                        try {
                            // Convertir cedula de int (BD1) a string (BD2)
                            $cedula_str = strval($reserva['cedula']);
                            
                            // Obtener datos del cliente desde usuario y persona
                            $sqlCliente = "SELECT per.nombre, per.apellido, u.id_usuario as id_persona
                                          FROM usuario u
                                          INNER JOIN persona per ON u.cedula = per.cedula
                                          WHERE per.cedula = ? AND u.estatus = 1
                                          LIMIT 1";
                            $stmtCliente = $conex2->prepare($sqlCliente);
                            $stmtCliente->execute([$cedula_str]);
                            $cliente = $stmtCliente->fetch(\PDO::FETCH_ASSOC);
                            
                            if ($cliente) {
                                $reserva['nombre'] = $cliente['nombre'];
                                $reserva['apellido'] = $cliente['apellido'];
                                $reserva['id_persona'] = $cliente['id_persona'];
                            } else {
                                $reserva['nombre'] = null;
                                $reserva['apellido'] = null;
                                $reserva['id_persona'] = null;
                            }
                        } catch (\PDOException $e) {
                            // Si falla la consulta del cliente, dejar valores por defecto
                            $reserva['nombre'] = null;
                            $reserva['apellido'] = null;
                            $reserva['id_persona'] = null;
                        }
                    } else {
                        $reserva['nombre'] = null;
                        $reserva['apellido'] = null;
                        $reserva['id_persona'] = null;
                    }
                }
                unset($reserva); // Liberar referencia
            }
            
            return $reservas;
        } catch (\PDOException $e) {
            error_log("Error en consultarReservasCompletas: " . $e->getMessage());
            throw $e;
        } finally {
            if ($conex1) $conex1 = null;
            if ($conex2) $conex2 = null;
        }
    }

    public function consultarDetallesReserva($id_pedido) {
        $conex = $this->getConex1();
        try {
            $sql = "SELECT 
                        pd.id_producto,
                        pr.nombre,
                        pd.cantidad,
                        pd.precio_unitario
                    FROM pedido_detalles pd
                    JOIN producto pr ON pd.id_producto = pr.id_producto
                    WHERE pd.id_pedido = ?";

            $stmt = $conex->prepare($sql);
            $stmt->execute([$id_pedido]);
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return $resultado;
        } catch (\Exception $e) {
            if ($conex) $conex = null;
            return [];
        }
    }

    private function consultarReserva($id_pedido) {
        $conex = $this->getConex1();
        try {
            $sql = "SELECT * FROM pedido WHERE id_pedido = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$id_pedido]);
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            $conex = null;
            return $resultado;
        } catch (\Exception $e) {
            if ($conex) $conex = null;
            return null;
        }
    }

    private function consultarPersonas() {
        // Consultar desde usuario y persona (base de datos 2)
        $conex2 = $this->getConex2();
        try {
            $sql = "SELECT u.id_usuario as id_persona, per.nombre, per.apellido 
                    FROM usuario u
                    INNER JOIN persona per ON u.cedula = per.cedula
                    WHERE u.estatus = 1 AND u.id_rol = 1
                    ORDER BY per.nombre ASC";
            $stmt = $conex2->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex2 = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex2) $conex2 = null;
            // Si falla, devolver array vacío
            return [];
        }
    }

    private function consultarProductos() {
        $conex = $this->getConex1();
        try {
            $sql = "SELECT id_producto, nombre, stock_disponible, precio_detal as precio FROM producto WHERE estatus = 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return $resultado;
        } catch (\Exception $e) {
            if ($conex) $conex = null;
            return [];
        }
    }
}
