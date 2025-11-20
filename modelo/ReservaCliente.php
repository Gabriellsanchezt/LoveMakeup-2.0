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

    private function validarStockCarrito($carrito) {
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
}
