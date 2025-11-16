<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;
class VentaWeb extends Conexion
{
    public function procesarPedido($jsonDatos)
    {
        $d = json_decode($jsonDatos, true)['datos'];

        try {
            $this->validarStockCarrito($d['carrito']);

            // 1) Registrar dirección
            $idDireccion = $this->registrarDireccion([
                'id_metodoentrega' => $d['id_metodoentrega'],
                'cedula' => $d['id_persona'], // tu controlador manda id_persona = CÉDULA
                'direccion_envio' => $d['direccion_envio'],
                'sucursal_envio' => $d['sucursal_envio']
            ]);

            // 2) Registrar pedido  
            $idPedido = $this->registrarPedido([
                'tipo' => $d['tipo'],
                'fecha' => $d['fecha'],
                'estatus' => $d['estado'],
                'precio_total_usd' => $d['precio_total_usd'],
                'precio_total_bs' => $d['precio_total_bs'],
                'cedula' => $d['id_persona'],
                'id_direccion' => $idDireccion
            ]);

            // 3) Registrar pago (3 tablas)
            $idPago = $this->registrarPago($d);

            // 4) Actualizar pedido con id_pago
            $this->asignarPagoAPedido($idPedido, $idPago);

            // 5) Insertar detalles + descontar stock
            foreach ($d['carrito'] as $item) {
                $precio = $item['cantidad'] >= $item['cantidad_mayor']
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

            return [
                'success' => true,
                'id_pedido' => $idPedido,
                'message' => 'Pedido registrado correctamente'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    private function registrarDireccion($d)
    {
        $conex = $this->getConex1();
        $sql = "INSERT INTO direccion(id_metodoentrega, cedula, direccion_envio, sucursal_envio)
                VALUES(:id_metodoentrega, :cedula, :direccion_envio, :sucursal_envio)";
        $stmt = $conex->prepare($sql);
        $stmt->execute($d);
        return $conex->lastInsertId();
    }

    private function registrarPedido($d)
    {
        $conex = $this->getConex1();
        $sql = "INSERT INTO pedido(tipo, fecha, estatus, precio_total_usd, precio_total_bs, 
                                   cedula, id_direccion, id_pago)
                VALUES(:tipo, :fecha, :estatus, :precio_total_usd, :precio_total_bs,
                       :cedula, :id_direccion, NULL)";
        $stmt = $conex->prepare($sql);
        $stmt->execute($d);
        return $conex->lastInsertId();
    }

    private function registrarPago($d)
    {
        $conex = $this->getConex1();

        /* A) detalle_pago */
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

        /* B) referencia_pago */
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

        /* C) comprobante_pago (imagen) */
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

    private function asignarPagoAPedido($idPedido, $idPago)
    {
        $conex = $this->getConex1();
        $stmt = $conex->prepare(
            "UPDATE pedido SET id_pago = :id_pago WHERE id_pedido = :id_pedido"
        );
        $stmt->execute(['id_pago' => $idPago, 'id_pedido' => $idPedido]);
    }

    private function registrarDetalle($d)
    {
        $conex = $this->getConex1();
        $stmt = $conex->prepare(
            "INSERT INTO pedido_detalles(id_pedido, id_producto, cantidad, precio_unitario)
             VALUES(:id_pedido, :id_producto, :cantidad, :precio_unitario)"
        );
        $stmt->execute($d);
    }

    private function actualizarStock($id, $cant)
    {
        $conex = $this->getConex1();
        $stmt = $conex->prepare(
            "UPDATE producto SET stock_disponible = stock_disponible - :cant
             WHERE id_producto = :id"
        );
        $stmt->execute(['cant' => $cant, 'id' => $id]);
    }

    private function validarStockCarrito($carrito)
    {
        $conex = $this->getConex1();
        foreach ($carrito as $item) {
            $stmt = $conex->prepare("SELECT stock_disponible FROM producto WHERE id_producto = :id");
            $stmt->execute(['id' => $item['id']]);
            $p = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$p) throw new \Exception("Producto no encontrado");
            if ($item['cantidad'] > $p['stock_disponible'])
                throw new \Exception("Stock insuficiente");
        }
    }
}
?>
