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
            $sql = "UPDATE pedido SET estatus = ?, tipo = 1 WHERE id_pedido = ?";
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
            // Consultar reservas con todos los datos de pago y entrega
            $sql = "SELECT 
                        p.id_pedido,
                        p.tipo,
                        p.fecha,
                        p.estatus AS estado,
                        p.precio_total_bs,
                        p.precio_total_usd,
                        p.cedula,
                        rp.banco_emisor AS banco_emisor,
                        rp.banco_receptor AS banco_receptor,
                        rp.referencia AS referencia_bancaria,
                        rp.telefono_emisor,
                        cp.imagen AS comprobante_imagen,
                        mp.nombre AS metodo_pago,
                        me.nombre AS metodo_entrega
                    FROM pedido p
                    LEFT JOIN detalle_pago dp ON p.id_pago = dp.id_pago
                    LEFT JOIN referencia_pago rp ON dp.id_pago = rp.id_pago
                    LEFT JOIN comprobante_pago cp ON dp.id_pago = cp.id_pago
                    LEFT JOIN metodo_pago mp ON dp.id_metodopago = mp.id_metodopago
                    LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
                    LEFT JOIN metodo_entrega me ON d.id_metodoentrega = me.id_entrega
                    WHERE p.tipo = '3'
                    ORDER BY p.fecha DESC";
    
            $stmt = $conex1->prepare($sql);
            $stmt->execute();
            $reservas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
            // Obtener datos del cliente desde la base de datos 2
            if (!empty($reservas) && $conex2) {
                foreach ($reservas as &$reserva) {
                    if (!empty($reserva['cedula'])) {
                        try {
                            $cedula_str = strval($reserva['cedula']);
                            $sqlCliente = "SELECT per.nombre, per.apellido, u.id_usuario AS id_persona
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
                unset($reserva);
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
                        pr.descripcion,
                        pd.cantidad,
                        pd.precio_unitario,
                        (pd.cantidad * pd.precio_unitario) AS subtotal
                    FROM pedido_detalles pd
                    INNER JOIN producto pr ON pd.id_producto = pr.id_producto
                    WHERE pd.id_pedido = ?";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$id_pedido]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        } finally {
            $conex = null;
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


    public function detectarInyeccionSQL($valor) {
        if (empty($valor)) {
            return false;
        }
        $valor_lower = strtolower($valor);
        $patrones_peligrosos = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(--|\#|\/\*|\*\/)/',
            '/(\bor\b.*\b1\s*=\s*1\b)/i',
            '/(\bdrop\b|\btruncate\b|\balter\b)\s+\btable\b/i'
        ];
        foreach ($patrones_peligrosos as $patron) {
            if (preg_match($patron, $valor_lower)) {
                return true;
            }
        }
        return false;
    }
    
    public function sanitizarString($valor, $max = 100){
    
        if($this->detectarInyeccionSQL($valor)){
            return '';
        }
    
        $valor = trim($valor);
        $valor = strip_tags($valor);
        $valor = preg_replace('/[\'";<>]/', '', $valor);
    
        return substr($valor,0,$max);
    }
    
    public function sanitizarEntero($valor, $min = null, $max = null) {
        if (!is_numeric($valor)) {
            return null;
        }
        $valor = (int)$valor;
        if ($min !== null && $valor < $min) {
            return null;
        }
        if ($max !== null && $valor > $max) {
            return null;
        }
        return $valor;
    }
    
    public function sanitizarDecimal($valor, $min = null, $max = null) {
        if (!is_numeric($valor)) {
            return null;
        }
        $valor = (float)$valor;
        if ($min !== null && $valor < $min) {
            return null;
        }
        if ($max !== null && $valor > $max) {
            return null;
        }
        return $valor;
    }
    
    public function validarReferenciaBancaria($referencia) {
        if (empty($referencia)) {
            return false;
        }
        if (!preg_match('/^[0-9\-\s]+$/', $referencia)) {
            return false;
        }
        if (strlen($referencia) > 50) {
            return false;
        }
        return true;
    }
    
    public function validarTelefono($telefono) {
        if (empty($telefono)) {
            return false;
        }
        if (!preg_match('/^[0-9\-\s\(\)]+$/', $telefono)) {
            return false;
        }
        $longitud = strlen(preg_replace('/[^0-9]/', '', $telefono));
        if ($longitud < 7 || $longitud > 15) {
            return false;
        }
        return true;
    }
    
    
    
    /**
     * Valida que el banco sea válido (lista de bancos permitidos)
     */
    public function validarBanco($banco) {
        if (empty($banco)) {
            return false;
        }
        $bancos_validos = [
            '0102-Banco De Venezuela',
            '0156-100% Banco ',
            '0172-Bancamiga Banco Universal,C.A',
            '0114-Bancaribe',
            '0171-Banco Activo',
            '0166-Banco Agricola De Venezuela',
            '0128-Bancon Caroni',
            '0163-Banco Del Tesoro',
            '0175-Banco Digital De Los Trabajadores, Banco Universal',
            '0115-Banco Exterior',
            '0151-Banco Fondo Comun',
            '0173-Banco Internacional De Desarrollo',
            '0105-Banco Mercantil',
            '0191-Banco Nacional De Credito',
            '0138-Banco Plaza',
            '0137-Banco Sofitasa',
            '0104-Banco Venezolano De Credito',
            '0168-Bancrecer',
            '0134-Banesco',
            '0177-Banfanb',
            '0146-Bangente',
            '0174-Banplus',
            '0108-BBVA Provincial',
            '0157-Delsur Banco Universal',
            '0601-Instituto Municipal De Credito Popular',
            '0178-N58 Banco Digital Banco Microfinanciero S.A',
            '0169-R4 Banco Microfinanciero C.A.'
        ];
        return in_array($banco, $bancos_validos, true);
    }
    
    
    /* Valida que un producto exista y esté activo
    */
    public function validarProductoActivo($id_producto) {
        $conex = $this->getConex1();
        $stmt = $conex->prepare("SELECT id_producto FROM producto WHERE id_producto = :id AND estatus = 1");
        $stmt->execute(['id' => $id_producto]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Valida que el banco_destino sea válido (solo 2 opciones permitidas)
     */
    public function validarBancoDestino($banco_destino) {
        if (empty($banco_destino)) {
            return false;
        }
        $bancos_destino_validos = [
            '0102-Banco De Venezuela',
            '0105-Banco Mercantil'
        ];
        return in_array($banco_destino, $bancos_destino_validos, true);
    }
    
    
    
}
