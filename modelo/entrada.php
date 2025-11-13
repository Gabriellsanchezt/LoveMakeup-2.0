<?php

namespace LoveMakeup\Proyecto\Modelo;

use Dompdf\Dompdf;
use Dompdf\Options;

use LoveMakeup\Proyecto\Config\Conexion;

class Entrada extends Conexion {
    
    public function __construct() {
        parent::__construct();
    }

    public function procesarCompra($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = isset($datos['datos']) ? $datos['datos'] : null;
        
        try {
            switch ($operacion) {
                case 'registrar':
                    return $this->ejecutarRegistro($datosProcesar);
                    
                case 'actualizar':
                    return $this->ejecutarActualizacion($datosProcesar);
                    
                case 'eliminar':
                    return $this->ejecutarEliminacion($datosProcesar);
                    
                case 'consultar':
                    return $this->ejecutarConsulta();
                    
                case 'consultarDetalles':
                    return $this->ejecutarConsultaDetalles($datosProcesar);
                    
                case 'consultarProductos':
                    return $this->ejecutarConsultaProductos();
                    
                case 'consultarProveedores':
                    return $this->ejecutarConsultaProveedores();
                    
                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }

    private function ejecutarRegistro($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            // Validación de datos básicos
            if (empty($datos['fecha_entrada']) || empty($datos['id_proveedor']) || empty($datos['productos'])) {
                throw new \Exception('Datos incompletos');
            }
            
            // Validar formato de fecha
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_entrada'])) {
                throw new \Exception('Formato de fecha inválido');
            }
            
            // Validar que el proveedor exista y esté activo
            $sql = "SELECT COUNT(*) FROM proveedor WHERE id_proveedor = :id_proveedor AND estatus = 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_proveedor' => intval($datos['id_proveedor'])]);
            if ($stmt->fetchColumn() == 0) {
                throw new \Exception('El proveedor seleccionado no existe o está inactivo');
            }
            
            // Validar que haya al menos un producto
            if (!is_array($datos['productos']) || count($datos['productos']) == 0) {
                throw new \Exception('Debe agregar al menos un producto');
            }
            
            // Validar stock máximo y datos de productos
            foreach ($datos['productos'] as $index => $producto) {
                // Validar que el producto tenga los campos requeridos
                if (!isset($producto['id_producto']) || !isset($producto['cantidad']) || 
                    !isset($producto['precio_unitario']) || !isset($producto['precio_total'])) {
                    throw new \Exception('Datos incompletos del producto en la posición ' . ($index + 1));
                }
                
                // Validar tipos y valores
                $id_producto = intval($producto['id_producto']);
                $cantidad = intval($producto['cantidad']);
                $precio_unitario = floatval($producto['precio_unitario']);
                $precio_total = floatval($producto['precio_total']);
                
                if ($id_producto <= 0) {
                    throw new \Exception('ID de producto inválido en la posición ' . ($index + 1));
                }
                
                if ($cantidad <= 0) {
                    throw new \Exception('La cantidad debe ser mayor a cero en la posición ' . ($index + 1));
                }
                
                if ($precio_unitario < 0) {
                    throw new \Exception('El precio unitario no puede ser negativo en la posición ' . ($index + 1));
                }
                
                if ($precio_total < 0) {
                    throw new \Exception('El precio total no puede ser negativo en la posición ' . ($index + 1));
                }
                
                // Validar coherencia de precios (con tolerancia de 0.01 para errores de redondeo)
                $precio_calculado = round($cantidad * $precio_unitario, 2);
                $precio_total_redondeado = round($precio_total, 2);
                if (abs($precio_calculado - $precio_total_redondeado) > 0.01) {
                    throw new \Exception('El precio total no coincide con la cantidad por precio unitario en la posición ' . ($index + 1));
                }
                
                // Validar que el producto exista y esté activo
                $sql = "SELECT stock_disponible, stock_maximo FROM producto WHERE id_producto = :id_producto AND estatus = 1";
                $stmt = $conex->prepare($sql);
                $stmt->execute(['id_producto' => $id_producto]);
                $prod_info = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$prod_info) {
                    throw new \Exception('El producto ID ' . $id_producto . ' no existe o está inactivo');
                }
                
                // Validar stock máximo
                $stockTotal = $prod_info['stock_disponible'] + $cantidad;
                if ($stockTotal > $prod_info['stock_maximo']) {
                    throw new \Exception('La cantidad ingresada para el producto ID: ' . $id_producto . 
                                     ' superaría el stock máximo permitido (' . $prod_info['stock_maximo'] . ')');
                }
            }
            
            // Insertar cabecera con fecha y hora actual
            // Combinar la fecha seleccionada con la hora actual
            $fecha_entrada = $datos['fecha_entrada'];
            $hora_actual = date('H:i:s');
            $fecha_hora_entrada = $fecha_entrada . ' ' . $hora_actual;
            
            $sql = "INSERT INTO compra(fecha_entrada, id_proveedor) VALUES (:fecha_entrada, :id_proveedor)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([
                'fecha_entrada' => $fecha_hora_entrada,
                'id_proveedor' => $datos['id_proveedor']
            ]);
            $id_compra = $conex->lastInsertId();
            
            // Insertar detalles (ya validados arriba)
            foreach ($datos['productos'] as $producto) {
                // Insertar detalle
                $sql = "INSERT INTO compra_detalles(cantidad, precio_total, precio_unitario, id_compra, id_producto) 
                       VALUES (:cantidad, :precio_total, :precio_unitario, :id_compra, :id_producto)";
                $stmt = $conex->prepare($sql);
                $stmt->execute([
                    'cantidad' => intval($producto['cantidad']),
                    'precio_total' => floatval($producto['precio_total']),
                    'precio_unitario' => floatval($producto['precio_unitario']),
                    'id_compra' => $id_compra,
                    'id_producto' => intval($producto['id_producto'])
                ]);
                
                // Actualizar stock
                $sql = "UPDATE producto SET stock_disponible = stock_disponible + :cantidad 
                       WHERE id_producto = :id_producto";
                $stmt = $conex->prepare($sql);
                $stmt->execute([
                    'cantidad' => intval($producto['cantidad']),
                    'id_producto' => intval($producto['id_producto'])
                ]);
            }
            
            $conex->commit();
            $conex = null;
            
            return ['respuesta' => 1, 'mensaje' => 'Compra registrada exitosamente', 'id_compra' => $id_compra];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    private function ejecutarActualizacion($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            // Validación de datos básicos
            if (empty($datos['id_compra']) || empty($datos['fecha_entrada']) || empty($datos['id_proveedor']) || empty($datos['productos'])) {
                throw new \Exception('Datos incompletos');
            }
            
            // Validar ID de compra
            $id_compra = intval($datos['id_compra']);
            if ($id_compra <= 0) {
                throw new \Exception('ID de compra inválido');
            }
            
            // Verificar que existe la compra
            $sql = "SELECT COUNT(*) FROM compra WHERE id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_compra' => $id_compra]);
            if ($stmt->fetchColumn() == 0) {
                throw new \Exception('La compra no existe');
            }
            
            // Validar formato de fecha
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_entrada'])) {
                throw new \Exception('Formato de fecha inválido');
            }
            
            // Validar que el proveedor exista y esté activo
            $sql = "SELECT COUNT(*) FROM proveedor WHERE id_proveedor = :id_proveedor AND estatus = 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_proveedor' => intval($datos['id_proveedor'])]);
            if ($stmt->fetchColumn() == 0) {
                throw new \Exception('El proveedor seleccionado no existe o está inactivo');
            }
            
            // Validar que haya al menos un producto
            if (!is_array($datos['productos']) || count($datos['productos']) == 0) {
                throw new \Exception('Debe agregar al menos un producto');
            }
            
            // Validar stock máximo y datos de productos
            foreach ($datos['productos'] as $index => $producto) {
                // Validar que el producto tenga los campos requeridos
                if (!isset($producto['id_producto']) || !isset($producto['cantidad']) || 
                    !isset($producto['precio_unitario']) || !isset($producto['precio_total'])) {
                    throw new \Exception('Datos incompletos del producto en la posición ' . ($index + 1));
                }
                
                // Validar tipos y valores
                $id_producto = intval($producto['id_producto']);
                $cantidad = intval($producto['cantidad']);
                $precio_unitario = floatval($producto['precio_unitario']);
                $precio_total = floatval($producto['precio_total']);
                
                if ($id_producto <= 0) {
                    throw new \Exception('ID de producto inválido en la posición ' . ($index + 1));
                }
                
                if ($cantidad <= 0) {
                    throw new \Exception('La cantidad debe ser mayor a cero en la posición ' . ($index + 1));
                }
                
                if ($precio_unitario < 0) {
                    throw new \Exception('El precio unitario no puede ser negativo en la posición ' . ($index + 1));
                }
                
                if ($precio_total < 0) {
                    throw new \Exception('El precio total no puede ser negativo en la posición ' . ($index + 1));
                }
                
                // Validar coherencia de precios
                $precio_calculado = round($cantidad * $precio_unitario, 2);
                $precio_total_redondeado = round($precio_total, 2);
                if (abs($precio_calculado - $precio_total_redondeado) > 0.01) {
                    throw new \Exception('El precio total no coincide con la cantidad por precio unitario en la posición ' . ($index + 1));
                }
                
                // Validar que el producto exista y esté activo
                $sql = "SELECT p.stock_disponible, p.stock_maximo, COALESCE(cd.cantidad, 0) as cantidad_actual 
                       FROM producto p 
                       LEFT JOIN compra_detalles cd ON cd.id_producto = p.id_producto 
                       AND cd.id_compra = :id_compra 
                       WHERE p.id_producto = :id_producto AND p.estatus = 1";
                $stmt = $conex->prepare($sql);
                $stmt->execute([
                    'id_compra' => $id_compra,
                    'id_producto' => $id_producto
                ]);
                $prod_info = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$prod_info) {
                    throw new \Exception('El producto ID ' . $id_producto . ' no existe o está inactivo');
                }
                
                // Validar stock máximo
                $stockTotal = ($prod_info['stock_disponible'] - $prod_info['cantidad_actual']) + $cantidad;
                if ($stockTotal > $prod_info['stock_maximo']) {
                    throw new \Exception('La cantidad ingresada para el producto ID: ' . $id_producto . 
                                     ' superaría el stock máximo permitido (' . $prod_info['stock_maximo'] . ')');
                }
            }
            
            // Actualizar cabecera - mantener la hora original si existe, o agregar hora actual
            // Primero obtener la fecha/hora actual de la compra
            $sql_select = "SELECT fecha_entrada FROM compra WHERE id_compra = :id_compra";
            $stmt_select = $conex->prepare($sql_select);
            $stmt_select->execute(['id_compra' => $id_compra]);
            $fecha_actual = $stmt_select->fetchColumn();
            
            // Si la fecha actual tiene hora, mantenerla; si no, agregar hora actual
            $fecha_entrada = $datos['fecha_entrada'];
            if ($fecha_actual && strlen($fecha_actual) > 10 && strpos($fecha_actual, ' ') !== false) {
                // Ya tiene hora, mantenerla
                $hora_existente = substr($fecha_actual, 11);
                $fecha_hora_entrada = $fecha_entrada . ' ' . $hora_existente;
            } else {
                // No tiene hora, agregar hora actual
                $hora_actual = date('H:i:s');
                $fecha_hora_entrada = $fecha_entrada . ' ' . $hora_actual;
            }
            
            $sql = "UPDATE compra SET fecha_entrada = :fecha_entrada, id_proveedor = :id_proveedor 
                   WHERE id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute([
                'fecha_entrada' => $fecha_hora_entrada,
                'id_proveedor' => $datos['id_proveedor'],
                'id_compra' => $id_compra
            ]);
            
            // Obtener detalles actuales para ajustar stock
            $sql = "SELECT id_producto, cantidad FROM compra_detalles WHERE id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_compra' => $id_compra]);
            $detalles_actuales = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Restar stock actual
            foreach ($detalles_actuales as $detalle) {
                $sql = "UPDATE producto SET stock_disponible = stock_disponible - :cantidad 
                       WHERE id_producto = :id_producto";
                $stmt = $conex->prepare($sql);
                $stmt->execute([
                    'cantidad' => $detalle['cantidad'],
                    'id_producto' => $detalle['id_producto']
                ]);
            }
            
            // Eliminar detalles actuales
            $sql = "DELETE FROM compra_detalles WHERE id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_compra' => $datos['id_compra']]);
            
            // Insertar nuevos detalles (ya validados arriba)
            foreach ($datos['productos'] as $producto) {
                // Insertar detalle
                $sql = "INSERT INTO compra_detalles(cantidad, precio_total, precio_unitario, id_compra, id_producto) 
                       VALUES (:cantidad, :precio_total, :precio_unitario, :id_compra, :id_producto)";
                $stmt = $conex->prepare($sql);
                $stmt->execute([
                    'cantidad' => intval($producto['cantidad']),
                    'precio_total' => floatval($producto['precio_total']),
                    'precio_unitario' => floatval($producto['precio_unitario']),
                    'id_compra' => $id_compra,
                    'id_producto' => intval($producto['id_producto'])
                ]);
                
                // Actualizar stock
                $sql = "UPDATE producto SET stock_disponible = stock_disponible + :cantidad 
                       WHERE id_producto = :id_producto";
                $stmt = $conex->prepare($sql);
                $stmt->execute([
                    'cantidad' => intval($producto['cantidad']),
                    'id_producto' => intval($producto['id_producto'])
                ]);
            }
            
            $conex->commit();
            $conex = null;
            return ['respuesta' => 1, 'mensaje' => 'Compra actualizada exitosamente'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    private function ejecutarEliminacion($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            // Validar ID de compra
            if (empty($datos['id_compra'])) {
                throw new \Exception('ID de compra no proporcionado');
            }
            
            $id_compra = intval($datos['id_compra']);
            if ($id_compra <= 0) {
                throw new \Exception('ID de compra inválido');
            }
            
            // Verificar que existe la compra
            $sql = "SELECT COUNT(*) FROM compra WHERE id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_compra' => $id_compra]);
            if ($stmt->fetchColumn() == 0) {
                throw new \Exception('La compra no existe');
            }
            
            // Obtener detalles para ajustar stock
            $sql = "SELECT id_producto, cantidad FROM compra_detalles WHERE id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_compra' => $id_compra]);
            $detalles = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Verificar stock disponible antes de restar
            foreach ($detalles as $detalle) {
                $sql = "SELECT stock_disponible FROM producto WHERE id_producto = :id_producto";
                $stmt = $conex->prepare($sql);
                $stmt->execute(['id_producto' => $detalle['id_producto']]);
                $stock_actual = $stmt->fetchColumn();
                
                // Validar que el stock actual sea suficiente para restar la cantidad de la compra
                // Esto evita que el stock quede negativo
                if ($stock_actual === false || $stock_actual < $detalle['cantidad']) {
                    throw new \Exception('No se puede eliminar la compra porque el producto ID: ' . $detalle['id_producto'] . 
                                     ' no tiene suficiente stock disponible. Stock actual: ' . ($stock_actual !== false ? $stock_actual : 0) . 
                                     ', cantidad a restar: ' . $detalle['cantidad']);
                }
            }
            
            // Actualizar stock
            foreach ($detalles as $detalle) {
                $sql = "UPDATE producto SET stock_disponible = stock_disponible - :cantidad 
                       WHERE id_producto = :id_producto";
                $stmt = $conex->prepare($sql);
                $stmt->execute([
                    'cantidad' => $detalle['cantidad'],
                    'id_producto' => $detalle['id_producto']
                ]);
            }
            
            // Eliminar detalles
            $sql = "DELETE FROM compra_detalles WHERE id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_compra' => $id_compra]);
            
            // Eliminar cabecera
            $sql = "DELETE FROM compra WHERE id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_compra' => $id_compra]);
            
            $conex->commit();
            $conex = null;
            return ['respuesta' => 1, 'mensaje' => 'Compra eliminada exitosamente'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    private function ejecutarConsulta() {
        $conex = $this->getConex1();
        try {
            $sql = "SELECT c.id_compra, c.fecha_entrada, p.nombre as proveedor_nombre, p.telefono as proveedor_telefono, p.id_proveedor 
                   FROM compra c 
                   JOIN proveedor p ON c.id_proveedor = p.id_proveedor 
                   ORDER BY c.id_compra DESC";
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $conex = null;
            return ['respuesta' => 1, 'datos' => $resultado];
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    private function ejecutarConsultaDetalles($datos) {
        $conex = $this->getConex1();
        try {
            // Validar que se proporcione el ID de compra
            if (empty($datos['id_compra'])) {
                throw new \Exception('ID de compra no proporcionado');
            }
            
            $id_compra = intval($datos['id_compra']);
            if ($id_compra <= 0) {
                throw new \Exception('ID de compra inválido');
            }
            
            $sql = "SELECT cd.id_detalle_compra, cd.cantidad, cd.precio_total, cd.precio_unitario, 
                   p.id_producto, p.nombre as producto_nombre, m.nombre as marca 
                   FROM compra_detalles cd 
                   JOIN producto p ON cd.id_producto = p.id_producto 
                   LEFT JOIN marca m ON p.id_marca = m.id_marca
                   WHERE cd.id_compra = :id_compra";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_compra' => $id_compra]);
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return ['respuesta' => 1, 'datos' => $resultado];
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    private function ejecutarConsultaProductos() {
        $conex = $this->getConex1();
        try {
            $sql = "SELECT p.id_producto, p.nombre, m.nombre as marca, p.stock_disponible 
                   FROM producto p 
                   LEFT JOIN marca m ON p.id_marca = m.id_marca 
                   WHERE p.estatus = 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return ['respuesta' => 1, 'datos' => $resultado];
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    private function ejecutarConsultaProveedores() {
        $conex = $this->getConex1();
        try {
            $sql = "SELECT id_proveedor, nombre FROM proveedor WHERE estatus = 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return ['respuesta' => 1, 'datos' => $resultado];
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }
}
?>