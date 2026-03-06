<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class ReservaCliente extends Conexion {

    private $objmetodopago;

    public function __construct() {
        parent::__construct();
        $this->objmetodopago = new MetodoPago();
    }

    public function obtenerMetodosPago() {
        return $this->objmetodopago->obtenerMetodos();
    }

    public function procesarReserva($jsonDatos) {
        $datos = json_decode($jsonDatos, true);

        if (!isset($datos['operacion']) || $datos['operacion'] !== 'registrar_reserva') {
            return ['success' => false, 'message' => 'Operación no válida.'];
        }

        $d = $datos['datos'];
        $d['tipo'] = 3; // tipo = reserva
        $conex = $this->getConex1();

        try {
            $conex->beginTransaction();

            // Validar stock
            $this->validarStockCarrito($d['carrito']);

            // 1) Registrar pedido
            $idPedido = $this->registrarPedido([
                'tipo' => $d['tipo'],
                'fecha' => $d['fecha'] ?? date('Y-m-d H:i:s'),
                'estatus' => 1,
                'precio_total_usd' => $d['precio_total_usd'],
                'precio_total_bs' => $d['precio_total_bs'],
                'cedula' => $d['id_persona'],
                'id_direccion' => null
            ]);

            // 2) Registrar pago (3 tablas)
            $idPago = $this->registrarPago([
                'id_metodopago' => $d['id_metodopago'],
                'referencia_bancaria' => $d['referencia_bancaria'],
                'telefono_emisor' => $d['telefono_emisor'],
                'banco_destino' => $d['banco_destino'],
                'banco' => $d['banco'],
                'monto' => $d['monto'],
                'monto_usd' => $d['monto_usd'],
                'imagen' => $d['imagen']
            ]);

            // 3) Asignar pago al pedido
            $this->asignarPagoAPedido($idPedido, $idPago);

            // 4) Registrar detalles y actualizar stock
            foreach ($d['carrito'] as $item) {
                $precio = ($item['cantidad'] >= $item['cantidad_mayor'])
                          ? $item['precio_mayor']
                          : $item['precio_detal'];

                $this->registrarDetalle([
                    'id_pedido' => $idPedido,
                    'id_producto' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $precio
                ]);

                $this->actualizarStock($item['id'], $item['cantidad']);
            }

            // 5) Registrar reserva
            $this->registrarReserva($idPedido);

            $conex->commit();

            return [
                'success' => true,
                'id_pedido' => $idPedido,
                'message' => 'Reserva registrada correctamente'
            ];

        } catch (\Exception $e) {
            if ($conex->inTransaction()) $conex->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ========================================
    // 🔹 REGISTRAR PEDIDO
    // ========================================
    private function registrarPedido($d) {
        $conex = $this->getConex1();
        $sql = "INSERT INTO pedido(tipo, fecha, estatus, precio_total_usd, precio_total_bs, 
                                   cedula, id_direccion, id_pago)
                VALUES(:tipo, :fecha, :estatus, :precio_total_usd, :precio_total_bs,
                       :cedula, :id_direccion, NULL)";
        $stmt = $conex->prepare($sql);
        $stmt->execute($d);
        return $conex->lastInsertId();
    }

    // ========================================
    // 🔹 REGISTRAR PAGO (3 tablas)
    // ========================================
    private function registrarPago($d) {
        $conex = $this->getConex1();

        // 1) detalle_pago
        $stmt = $conex->prepare(
            "INSERT INTO detalle_pago(monto, monto_usd, id_metodopago)
             VALUES(:monto, :monto_usd, :id_metodopago)"
        );
        $stmt->execute([
            'monto' => $d['monto'],
            'monto_usd' => $d['monto_usd'],
            'id_metodopago' => $d['id_metodopago']
        ]);
        $idPago = $conex->lastInsertId();

        // 2) referencia_pago
        $stmt = $conex->prepare(
            "INSERT INTO referencia_pago(id_pago, banco_emisor, banco_receptor, referencia, telefono_emisor)
             VALUES(:id_pago, :banco_emisor, :banco_receptor, :referencia, :telefono_emisor)"
        );
        $stmt->execute([
            'id_pago' => $idPago,
            'banco_emisor' => $d['banco'],
            'banco_receptor' => $d['banco_destino'],
            'referencia' => $d['referencia_bancaria'],
            'telefono_emisor' => $d['telefono_emisor']
        ]);

        // 3) comprobante_pago (imagen)
        if (!empty($d['imagen'])) {
            $stmt = $conex->prepare(
                "INSERT INTO comprobante_pago(id_pago, imagen)
                 VALUES(:id_pago, :imagen)"
            );
            $stmt->execute([
                'id_pago' => $idPago,
                'imagen' => $d['imagen']
            ]);
        }

        return $idPago;
    }

    private function asignarPagoAPedido($idPedido, $idPago) {
        $conex = $this->getConex1();
        $stmt = $conex->prepare("UPDATE pedido SET id_pago = :id_pago WHERE id_pedido = :id_pedido");
        $stmt->execute(['id_pago' => $idPago, 'id_pedido' => $idPedido]);
    }

    private function registrarDetalle($d) {
        $conex = $this->getConex1();
        $stmt = $conex->prepare(
            "INSERT INTO pedido_detalles(id_pedido, id_producto, cantidad, precio_unitario)
             VALUES(:id_pedido, :id_producto, :cantidad, :precio_unitario)"
        );
        $stmt->execute($d);
    }

    private function actualizarStock($id, $cantidad) {
        $conex = $this->getConex1();
        $stmt = $conex->prepare("UPDATE producto SET stock_disponible = stock_disponible - :cantidad WHERE id_producto = :id");
        $stmt->execute(['cantidad' => $cantidad, 'id' => $id]);
    }

    public function validarStockCarrito($carrito) {
        $conex = $this->getConex1();
        foreach ($carrito as $item) {
            $stmt = $conex->prepare("SELECT stock_disponible, nombre FROM producto WHERE id_producto = :id");
            $stmt->execute(['id' => $item['id']]);
            $p = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$p) throw new \Exception("Producto {$item['id']} no encontrado");
            if ($item['cantidad'] > $p['stock_disponible'])
                throw new \Exception("Stock insuficiente para {$p['nombre']}");
        }
    }

    private function registrarReserva($idPedido) {
        $conex = $this->getConex1();
        $stmt = $conex->prepare("INSERT INTO reserva(id_pedido) VALUES (:id_pedido)");
        $stmt->execute(['id_pedido' => $idPedido]);
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
