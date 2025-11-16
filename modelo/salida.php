<?php

namespace LoveMakeup\Proyecto\Modelo;

require_once(__DIR__ . '/../config/conexion.php');
use LoveMakeup\Proyecto\Config\Conexion;

class Salida extends Conexion {
    
    function __construct() {
        parent::__construct();
    }

    public function procesarVenta($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];
        
        try {
            switch ($operacion) {
                case 'registrar':
                    return $this->ejecutarRegistro($datosProcesar);
                    
                case 'actualizar':
                    return $this->ejecutarActualizacion($datosProcesar);
                    
                case 'eliminar':
                    return $this->ejecutarEliminacion($datosProcesar);
                    
                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }

    private function ejecutarRegistro($datos) {
        // Validaciones previas
        if (!isset($datos['id_persona']) || $datos['id_persona'] <= 0) {
            throw new \Exception('ID de persona no válido');
        }
        
        if (!isset($datos['precio_total']) || $datos['precio_total'] <= 0) {
            throw new \Exception('Precio total no válido');
        }
        
        if (!isset($datos['detalles']) || empty($datos['detalles'])) {
            throw new \Exception('No hay producto en la venta');
        }

        $conex1 = $this->getConex1();
        $conex2 = $this->getConex2();
        try {
            // Obtener la cédula desde el id_usuario (base de datos 2)
            $sql_cedula = "SELECT cedula FROM usuario WHERE id_usuario = :id_usuario AND estatus = 1";
            $stmt_cedula = $conex2->prepare($sql_cedula);
            $stmt_cedula->execute(['id_usuario' => $datos['id_persona']]);
            $usuario = $stmt_cedula->fetch(\PDO::FETCH_ASSOC);
            
            if (!$usuario || empty($usuario['cedula'])) {
                throw new \Exception('El cliente no existe o está inactivo');
            }
            
            $cedula = intval($usuario['cedula']); // Convertir a int para la BD1
            
            $conex1->beginTransaction();
            // Insertar cabecera del pedido con los campos correctos según la estructura de la BD
            // La tabla pedido usa cedula (int), no id_usuario
            $sql = "INSERT INTO pedido(tipo, fecha, estatus, precio_total_usd, precio_total_bs, cedula) 
                    VALUES ('1', NOW(), '2', :precio_total_usd, :precio_total_bs, :cedula)";
            $stmt = $conex1->prepare($sql);
            $stmt->execute([
                'precio_total_usd' => $datos['precio_total'],
                'precio_total_bs' => $datos['precio_total_bs'] ?? 0.00, // Usar el valor calculado desde el frontend
                'cedula' => $cedula // cédula del cliente
            ]);
            $id_pedido = $conex1->lastInsertId();
            
            // Insertar registro en la tabla venta con fecha y hora
            $sql_venta = "INSERT INTO venta(id_pedido, fecha_confirmacion) VALUES (:id_pedido, NOW())";
            $stmt_venta = $conex1->prepare($sql_venta);
            $stmt_venta->execute(['id_pedido' => $id_pedido]);
            
            // Insertar detalles
            foreach ($datos['detalles'] as $detalle) {
                // Validar datos del detalle
                if (!isset($detalle['id_producto']) || $detalle['id_producto'] <= 0) {
                    throw new \Exception('ID de producto no válido en detalle');
                }
                if (!isset($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                    throw new \Exception('Cantidad no válida en detalle');
                }
                if (!isset($detalle['precio_unitario']) || $detalle['precio_unitario'] <= 0) {
                    throw new \Exception('Precio unitario no válido en detalle');
                }
                // Verificar stock
                $stock = $this->verificarStock($detalle['id_producto']);
                if ($stock < $detalle['cantidad']) {
                    throw new \Exception('Stock insuficiente para el producto ID: ' . $detalle['id_producto']);
                }
                
                // Insertar detalle del pedido
                $sql_detalle = "INSERT INTO pedido_detalles(id_pedido, id_producto, cantidad, precio_unitario) 
                                   VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario)";
                $stmt_detalle = $conex1->prepare($sql_detalle);
                $stmt_detalle->execute([
                    'id_pedido' => $id_pedido,
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario']
                ]);
                
                // Actualizar stock
                $sql_stock = "UPDATE producto SET stock_disponible = stock_disponible - :cantidad 
                                   WHERE id_producto = :id_producto";
                $stmt_stock = $conex1->prepare($sql_stock);
                $stmt_stock->execute([
                    'cantidad' => $detalle['cantidad'],
                    'id_producto' => $detalle['id_producto']
                ]);
            }
            $conex1->commit();
            $conex1 = null;
            $conex2 = null;
            return ['respuesta' => 1, 'accion' => 'incluir', 'id_pedido' => $id_pedido];
        } catch (\Exception $e) {
            if ($conex1) {
                $conex1->rollBack();
                $conex1 = null;
            }
            if ($conex2) {
                $conex2 = null;
            }
            throw $e;
        }
    }

    private function ejecutarActualizacion($datos) {
        // Validaciones previas
        if (!isset($datos['id_pedido']) || $datos['id_pedido'] <= 0) {
            throw new \Exception('ID de pedido no válido');
        }
        
        if (!isset($datos['estado']) || !in_array($datos['estado'], ['0', '1', '2', '3', '4', '5'])) {
            throw new \Exception('Estado no válido');
        }

        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            // Usa estatus en lugar de estado
            $sql = "UPDATE pedido SET estatus = :estatus";
            $params = ['estatus' => $datos['estado']];
            
            if ($datos['estado'] == '2') {
                $sql .= ", tipo = '2'";
            }
            
            if (!empty($datos['direccion'])) {
                $sql .= ", id_direccion = :id_direccion";
                $params['id_direccion'] = $datos['direccion'];
            }
            
            $sql .= " WHERE id_pedido = :id_pedido";
            $params['id_pedido'] = $datos['id_pedido'];
            
            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($params);
            
            if ($resultado) {
                $conex->commit();
                $conex = null;
                return ['respuesta' => 1, 'accion' => 'actualizar'];
            }
            
            $conex->rollBack();
            $conex = null;
            return ['respuesta' => 0, 'mensaje' => 'Error al actualizar la venta'];
            
        } catch (\Exception $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    private function ejecutarEliminacion($datos) {
        // Validaciones previas
        if (!isset($datos['id_pedido']) || $datos['id_pedido'] <= 0) {
            throw new \Exception('ID de pedido no válido');
        }

        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            // Verificar que el pedido existe
            $sql_verificar = "SELECT id_pedido FROM pedido WHERE id_pedido = :id_pedido";
            $stmt_verificar = $conex->prepare($sql_verificar);
            $stmt_verificar->execute(['id_pedido' => $datos['id_pedido']]);
            
            if (!$stmt_verificar->fetch()) {
                throw new \Exception('El pedido no existe');
            }
            
            // Recuperar detalles para devolver stock
            $sql_detalles = "SELECT id_producto, cantidad FROM pedido_detalles WHERE id_pedido = :id_pedido";
            $stmt_detalles = $conex->prepare($sql_detalles);
            $stmt_detalles->execute(['id_pedido' => $datos['id_pedido']]);
            $detalles = $stmt_detalles->fetchAll(\PDO::FETCH_ASSOC);
            
            // Devolver stock
            foreach ($detalles as $detalle) {
                $sql_stock = "UPDATE producto SET stock_disponible = stock_disponible + :cantidad 
                                   WHERE id_producto = :id_producto";
                $stmt_stock = $conex->prepare($sql_stock);
                $stmt_stock->execute([
                    'cantidad' => $detalle['cantidad'],
                    'id_producto' => $detalle['id_producto']
                ]);
            }
            
            // Eliminar detalles
            $sql_eliminar_detalles = "DELETE FROM pedido_detalles WHERE id_pedido = :id_pedido";
            $stmt_eliminar_detalles = $conex->prepare($sql_eliminar_detalles);
            $stmt_eliminar_detalles->execute(['id_pedido' => $datos['id_pedido']]);
            
            // Eliminar cabecera
            $sql_eliminar = "DELETE FROM pedido WHERE id_pedido = :id_pedido";
            $stmt_eliminar = $conex->prepare($sql_eliminar);
            $stmt_eliminar->execute(['id_pedido' => $datos['id_pedido']]);
            
            $conex->commit();
            $conex = null;
            return ['respuesta' => 1, 'accion' => 'eliminar'];
            
        } catch (\Exception $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    public function consultarVentas() {
        $conex = $this->getConex1();
        try {
            // Consulta para ventas PRESENCIALES usando la tabla venta con relaciones relevantes
            // Relaciones incluidas (solo para venta presencial):
            // venta -> pedido (FK: id_pedido)
            // pedido -> detalle_pago (FK: id_pago) -> metodo_pago (FK: id_metodopago) - Métodos de pago
            // NOTA: Datos de usuario y persona se obtienen por separado ya que están en otra base de datos
            // NO incluye: direccion, metodo_entrega, delivery, tracking (solo para pedidos web)
            $sql = "SELECT v.id_venta,
                    v.id_pedido,
                    v.fecha_confirmacion,
                    p.tipo,
                    p.fecha,
                    p.estatus as estado,
                    p.precio_total_usd,
                    p.precio_total_bs,
                    p.cedula,
                    p.id_pago,
                    dp.id_pago as id_detalle_pago,
                    dp.monto,
                    dp.monto_usd,
                    mp.id_metodopago,
                    mp.nombre as metodo_pago_nombre,
                    mp.descripcion as metodo_pago_descripcion,
                    mp.requiere_banco
                    FROM venta v
                    INNER JOIN pedido p ON v.id_pedido = p.id_pedido
                    LEFT JOIN detalle_pago dp ON p.id_pago = dp.id_pago
                    LEFT JOIN metodo_pago mp ON dp.id_metodopago = mp.id_metodopago
                    WHERE CAST(p.tipo AS CHAR) = '1' 
                    AND (CAST(p.estatus AS CHAR) = '1' OR CAST(p.estatus AS CHAR) = '2')
                    ORDER BY p.id_pedido DESC";
            
            error_log("SQL consultarVentas: " . $sql);
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Obtener datos de cliente desde la otra base de datos si es necesario
            if (!empty($resultado)) {
                $conex2 = $this->getConex2();
                foreach ($resultado as &$venta) {
                    if (!empty($venta['cedula'])) {
                        try {
                            // La cedula viene como int de BD1, convertir a string para BD2
                            $cedula_str = strval($venta['cedula']);
                            $sqlCliente = "SELECT u.id_usuario, per.cedula, per.nombre, per.apellido, per.telefono, per.correo, u.estatus as estatus_usuario
                                          FROM usuario u
                                          INNER JOIN persona per ON u.cedula = per.cedula
                                          WHERE per.cedula = :cedula AND u.estatus = 1";
                            $stmtCliente = $conex2->prepare($sqlCliente);
                            $stmtCliente->execute(['cedula' => $cedula_str]);
                            $cliente = $stmtCliente->fetch(\PDO::FETCH_ASSOC);
                            
                            if ($cliente) {
                                $venta['cliente'] = $cliente['nombre'] . ' ' . $cliente['apellido'];
                                $venta['cedula'] = $cliente['cedula'];
                                $venta['id_usuario'] = $cliente['id_usuario'];
                                $venta['nombre_cliente'] = $cliente['nombre'];
                                $venta['apellido_cliente'] = $cliente['apellido'];
                                $venta['telefono'] = $cliente['telefono'];
                                $venta['correo'] = $cliente['correo'];
                                $venta['estatus_usuario'] = $cliente['estatus_usuario'];
                            } else {
                                $venta['cliente'] = 'Sin cliente';
                                $venta['cedula'] = null;
                                $venta['id_usuario'] = null;
                                $venta['nombre_cliente'] = null;
                                $venta['apellido_cliente'] = null;
                                $venta['telefono'] = null;
                                $venta['correo'] = null;
                                $venta['estatus_usuario'] = null;
                            }
                        } catch (\PDOException $e) {
                            // Si falla la consulta del cliente, dejar valores por defecto
                            $venta['cliente'] = 'Sin cliente';
                            $venta['cedula'] = null;
                            $venta['id_usuario'] = null;
                            $venta['nombre_cliente'] = null;
                            $venta['apellido_cliente'] = null;
                            $venta['telefono'] = null;
                            $venta['correo'] = null;
                            $venta['estatus_usuario'] = null;
                        }
                    } else {
                        $venta['cliente'] = 'Sin cliente';
                        $venta['cedula'] = null;
                        $venta['id_usuario'] = null;
                        $venta['nombre_cliente'] = null;
                        $venta['apellido_cliente'] = null;
                        $venta['telefono'] = null;
                        $venta['correo'] = null;
                        $venta['estatus_usuario'] = null;
                    }
                }
                unset($venta); // Liberar referencia
            }
            
            error_log("Resultados encontrados: " . count($resultado));
            if (count($resultado) > 0) {
                error_log("Primer resultado: " . json_encode($resultado[0]));
            }
            
            $conex = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            error_log("Error en consultarVentas: " . $e->getMessage());
            error_log("SQL Error Info: " . json_encode($stmt->errorInfo() ?? []));
            throw $e;
        }
    }

    public function consultarCliente($datos) {
        // Validar datos de entrada
        if (!isset($datos['cedula']) || empty($datos['cedula'])) {
            throw new \Exception('Cédula no proporcionada');
        }

        $conex = $this->getConex2();
        try {
            // Consultar desde usuario y persona según la nueva estructura de BD
            $sql = "SELECT u.id_usuario as id_persona, per.cedula, per.nombre, per.apellido, per.correo, per.telefono 
                    FROM usuario u
                    INNER JOIN persona per ON u.cedula = per.cedula
                    WHERE per.cedula = :cedula AND u.estatus = 1";
            
            $stmt = $conex->prepare($sql);
            $stmt->execute(['cedula' => $datos['cedula']]);
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            $conex = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    public function registrarCliente($datos) {
        // Validar datos de entrada
        $campos_requeridos = ['cedula', 'nombre', 'apellido', 'telefono', 'correo'];
        foreach ($campos_requeridos as $campo) {
            if (!isset($datos[$campo]) || empty($datos[$campo])) {
                throw new \Exception("Campo {$campo} es obligatorio");
            }
        }

        // Validar formato de cédula
        if (!preg_match('/^[0-9]{7,8}$/', $datos['cedula'])) {
            throw new \Exception('Formato de cédula no válido');
        }

        // Validar formato de teléfono
        if (!preg_match('/^0[0-9]{10}$/', $datos['telefono'])) {
            throw new \Exception('Formato de teléfono no válido');
        }

        // Validar formato de correo
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Formato de correo no válido');
        }

        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            // Verificar si la cédula ya existe en persona
            $sql_verificar_persona = "SELECT cedula FROM persona WHERE cedula = :cedula";
            $stmt_verificar_persona = $conex->prepare($sql_verificar_persona);
            $stmt_verificar_persona->execute(['cedula' => $datos['cedula']]);
            $persona_existe = $stmt_verificar_persona->fetch();
            
            // Verificar si ya tiene usuario con rol de cliente (id_rol = 1)
            $sql_verificar_cliente = "SELECT id_usuario FROM usuario WHERE cedula = :cedula AND id_rol = 1 AND estatus = 1";
            $stmt_verificar_cliente = $conex->prepare($sql_verificar_cliente);
            $stmt_verificar_cliente->execute(['cedula' => $datos['cedula']]);
            $cliente_existe = $stmt_verificar_cliente->fetch();
            
            // Si ya tiene rol de cliente, lanzar error
            if ($cliente_existe) {
                throw new \Exception('La cédula ya está registrada como cliente');
            }
            
            // Si la persona no existe, insertar en persona
            if (!$persona_existe) {
                $sql_persona = "INSERT INTO persona (cedula, nombre, apellido, telefono, correo, tipo_documento) 
                               VALUES (:cedula, :nombre, :apellido, :telefono, :correo, 'V')";
                $stmt_persona = $conex->prepare($sql_persona);
                $stmt_persona->execute([
                    'cedula' => $datos['cedula'],
                    'nombre' => $datos['nombre'],
                    'apellido' => $datos['apellido'],
                    'telefono' => $datos['telefono'],
                    'correo' => $datos['correo']
                ]);
            } else {
                // Si la persona existe, actualizar sus datos
                $sql_actualizar_persona = "UPDATE persona SET nombre = :nombre, apellido = :apellido, 
                                          telefono = :telefono, correo = :correo 
                                          WHERE cedula = :cedula";
                $stmt_actualizar_persona = $conex->prepare($sql_actualizar_persona);
                $stmt_actualizar_persona->execute([
                    'cedula' => $datos['cedula'],
                    'nombre' => $datos['nombre'],
                    'apellido' => $datos['apellido'],
                    'telefono' => $datos['telefono'],
                    'correo' => $datos['correo']
                ]);
            }
            
            // 2. Insertar en usuario con rol de cliente (id_rol = 1)
            $sql_usuario = "INSERT INTO usuario (cedula, clave, estatus, id_rol) 
                           VALUES (:cedula, '', 1, 1)";
            $stmt_usuario = $conex->prepare($sql_usuario);
            $stmt_usuario->execute(['cedula' => $datos['cedula']]);
            
            $id_usuario = $conex->lastInsertId();
            $conex->commit();
            $conex = null;
            return $id_usuario;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    public function existeCedula($datos) {
        if (!isset($datos['cedula']) || empty($datos['cedula'])) {
            throw new \Exception('Cédula no proporcionada');
        }

        $conex = $this->getConex2();
        try {
            // Verificar si la cédula existe en persona
            $sql = "SELECT cedula FROM persona WHERE cedula = :cedula";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['cedula' => $datos['cedula']]);
            $resultado = $stmt->rowCount() > 0;
            $conex = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    private function verificarStock($id_producto) {
        if (!$id_producto || $id_producto <= 0) {
            throw new \Exception('ID de producto no válido');
        }

        $conex = $this->getConex1();
        try {
            $sql = "SELECT stock_disponible FROM producto WHERE id_producto = :id_producto AND estatus = 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_producto' => $id_producto]);
            $resultado = $stmt->fetchColumn();
            $conex = null;
            return $resultado ? intval($resultado) : 0;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    public function consultarProductos() {
        $conex = $this->getConex1();
        try {
            // Hacer JOIN con marca para obtener el nombre de la marca
            // Solo mostrar productos activos con stock disponible mayor a 0
            $sql = "SELECT p.id_producto, 
                           p.nombre, 
                           p.descripcion, 
                           COALESCE(m.nombre, 'Sin marca') as marca, 
                           p.precio_detal, 
                           COALESCE(p.stock_disponible, 0) as stock_disponible 
                    FROM producto p
                    LEFT JOIN marca m ON p.id_marca = m.id_marca
                    WHERE p.estatus = 1 
                      AND COALESCE(p.stock_disponible, 0) > 0
                    ORDER BY p.nombre ASC";
            
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Cerrar conexión
            $conex = null;
            
            // Asegurar que siempre devolvamos un array
            if (!is_array($resultado)) {
                return [];
            }
            
            // Validar que los resultados tengan la estructura esperada
            foreach ($resultado as &$producto) {
                if (!isset($producto['id_producto'])) {
                    continue;
                }
                // Asegurar que todos los campos necesarios existan
                if (!isset($producto['nombre'])) {
                    $producto['nombre'] = '';
                }
                if (!isset($producto['marca'])) {
                    $producto['marca'] = 'Sin marca';
                }
                if (!isset($producto['precio_detal'])) {
                    $producto['precio_detal'] = 0;
                }
                if (!isset($producto['stock_disponible'])) {
                    $producto['stock_disponible'] = 0;
                }
            }
            unset($producto);
            
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            // Devolver array vacío en caso de error
            return [];
        } catch (\Exception $e) {
            if ($conex) {
                $conex = null;
            }
            // Devolver array vacío en caso de cualquier otro error
            return [];
        }
    }

    public function consultarMetodosPago() {
        $conex = $this->getConex1();
        try {
            $sql = "SELECT id_metodopago, nombre, descripcion 
                     FROM metodo_pago 
                     WHERE estatus = 1
                     ORDER BY nombre ASC";
            
            $stmt = $conex->prepare($sql);
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

    public function obtenerNombreMetodoPago($id_metodopago) {
        if (!$id_metodopago || $id_metodopago <= 0) {
            return '';
        }
        
        $conex = $this->getConex1();
        try {
            $sql = "SELECT nombre FROM metodo_pago WHERE id_metodopago = :id_metodopago AND estatus = 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_metodopago' => $id_metodopago]);
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            $conex = null;
            return $resultado ? $resultado['nombre'] : '';
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            return '';
        }
    }

    public function consultarDetallesPedido($id_pedido) {
        if (!$id_pedido || $id_pedido <= 0) {
            throw new \Exception('ID de pedido no válido');
        }

        $conex = $this->getConex1();
        try {
            // Consulta completa de detalles con todas las relaciones incluyendo marca
            $sql = "SELECT pd.id_detalle,
                    pd.id_pedido,
                    pd.id_producto,
                    pd.cantidad,
                    pd.precio_unitario,
                    (pd.cantidad * pd.precio_unitario) as precio_total,
                    p.nombre as nombre_producto,
                    p.descripcion as descripcion_producto,
                    p.precio_detal,
                    p.stock_disponible,
                    COALESCE(m.nombre, 'Sin marca') as marca,
                    m.id_marca,
                    c.id_categoria,
                    c.nombre as categoria_nombre
                    FROM pedido_detalles pd 
                    INNER JOIN producto p ON pd.id_producto = p.id_producto
                    LEFT JOIN marca m ON p.id_marca = m.id_marca
                    LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                    WHERE pd.id_pedido = :id_pedido
                    ORDER BY pd.id_detalle ASC";
            
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_pedido' => $id_pedido]);
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

    public function consultarClienteDetalle($id_pedido) {
        if (!$id_pedido || $id_pedido <= 0) {
            throw new \Exception('ID de pedido no válido');
        }

        $conex1 = $this->getConex1();
        $conex2 = $this->getConex2();
        try {
            // Primero obtener la cedula desde pedido (base de datos 1)
            $sql_pedido = "SELECT cedula FROM pedido WHERE id_pedido = :id_pedido";
            $stmt_pedido = $conex1->prepare($sql_pedido);
            $stmt_pedido->execute(['id_pedido' => $id_pedido]);
            $pedido = $stmt_pedido->fetch(\PDO::FETCH_ASSOC);
            
            if (!$pedido || empty($pedido['cedula'])) {
                return null;
            }
            
            // Convertir cedula de int a string para BD2
            $cedula_str = strval($pedido['cedula']);
            
            // Luego obtener los datos del cliente desde usuario y persona (base de datos 2)
            $sql = "SELECT per.cedula, per.nombre, per.apellido, per.telefono, per.correo 
                     FROM usuario u 
                     JOIN persona per ON u.cedula = per.cedula
                     WHERE per.cedula = :cedula AND u.estatus = 1";
            
            $stmt = $conex2->prepare($sql);
            $stmt->execute(['cedula' => $cedula_str]);
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $conex1 = null;
            $conex2 = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex1) $conex1 = null;
            if ($conex2) $conex2 = null;
            throw $e;
        }
    }

    public function consultarMetodosPagoVenta($id_pedido) {
        if (!$id_pedido || $id_pedido <= 0) {
            throw new \Exception('ID de pedido no válido');
        }

        $conex = $this->getConex1();
        try {
            // Consulta para obtener métodos de pago de la venta
            // detalle_pago se relaciona con pedido a través de id_pago
            $sql = "SELECT mp.nombre as nombre_metodo, dp.monto_usd, dp.monto as monto_bs, 
                           rp.referencia, rp.banco_emisor, 
                           rp.banco_receptor, rp.telefono_emisor
                    FROM pedido p
                    JOIN detalle_pago dp ON p.id_pago = dp.id_pago
                    JOIN metodo_pago mp ON dp.id_metodopago = mp.id_metodopago
                    LEFT JOIN referencia_pago rp ON dp.id_pago = rp.id_pago
                    WHERE p.id_pedido = :id_pedido";
            
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_pedido' => $id_pedido]);
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            return [];
        }
    }

    // NUEVOS MÉTODOS PÚBLICOS PARA CONTROLADOR
    public function registrarVentaPublico($datos) {
        return $this->registrarVentaPrivado($datos);
    }
    private function registrarVentaPrivado($datos) {
        // Log de depuración
        error_log("Iniciando registro de venta con datos: " . json_encode($datos));
        
        // Validaciones previas
        if (!isset($datos['id_persona']) || $datos['id_persona'] <= 0) {
            throw new \Exception('ID de persona no válido');
        }
        
        if (!isset($datos['precio_total']) || $datos['precio_total'] <= 0) {
            throw new \Exception('Precio total no válido');
        }
        
        if (!isset($datos['detalles']) || empty($datos['detalles'])) {
            throw new \Exception('No hay producto en la venta');
        }

        $conex1 = $this->getConex1();
        $conex2 = $this->getConex2();
        try {
            // Obtener la cédula desde el id_usuario (base de datos 2)
            $sql_cedula = "SELECT cedula FROM usuario WHERE id_usuario = ? AND estatus = 1";
            $stmt_cedula = $conex2->prepare($sql_cedula);
            $stmt_cedula->execute([$datos['id_persona']]);
            $usuario = $stmt_cedula->fetch(\PDO::FETCH_ASSOC);
            
            if (!$usuario || empty($usuario['cedula'])) {
                throw new \Exception('El cliente no existe o está inactivo');
            }
            
            $cedula = intval($usuario['cedula']); // Convertir a int para la BD1
            
            $conex1->beginTransaction();
            
            // Insertar cabecera del pedido con los campos correctos según la estructura de la BD
            // Estatus '2' = venta completada (según requerimiento del usuario)
            // La tabla pedido usa cedula (int), no id_usuario
            $sql = "INSERT INTO pedido(tipo, fecha, estatus, precio_total_usd, precio_total_bs, cedula) VALUES ('1', NOW(), '2', ?, ?, ?)";
            $params = [
                $datos['precio_total'],
                $datos['precio_total_bs'] ?? 0.00, // Usar el valor calculado desde el frontend
                $cedula // cédula del cliente
            ];
            
            error_log("SQL para insertar pedido: " . $sql);
            error_log("Parámetros del pedido: " . json_encode($params));
            
            $stmt = $conex1->prepare($sql);
            $stmt->execute($params);
            $id_pedido = $conex1->lastInsertId();
            
            error_log("ID del pedido insertado: " . $id_pedido);
            
            // Insertar registro en la tabla venta con fecha y hora
            $sql_venta = "INSERT INTO venta(id_pedido, fecha_confirmacion) VALUES (?, NOW())";
            $stmt_venta = $conex1->prepare($sql_venta);
            $stmt_venta->execute([$id_pedido]);
            error_log("Registro insertado en tabla venta para pedido ID: " . $id_pedido . " con fecha y hora: " . date('Y-m-d H:i:s'));

            // Procesar detalles de producto
            foreach ($datos['detalles'] as $detalle) {
                // Validar datos del detalle
                if (!isset($detalle['id_producto']) || $detalle['id_producto'] <= 0) {
                    throw new \Exception('ID de producto no válido en detalle');
                }
                if (!isset($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                    throw new \Exception('Cantidad no válida en detalle');
                }
                if (!isset($detalle['precio_unitario']) || $detalle['precio_unitario'] <= 0) {
                    throw new \Exception('Precio unitario no válido en detalle');
                }

                // Verificar que el producto existe y tiene stock
                $sql_verificar_producto = "SELECT stock_disponible, nombre FROM producto WHERE id_producto = ? AND estatus = 1";
                $stmt_verificar_producto = $conex1->prepare($sql_verificar_producto);
                $stmt_verificar_producto->execute([$detalle['id_producto']]);
                $producto = $stmt_verificar_producto->fetch(\PDO::FETCH_ASSOC);
                
                if (!$producto) {
                    throw new \Exception('El producto no existe o está inactivo');
                }
                
                if ($producto['stock_disponible'] < $detalle['cantidad']) {
                    throw new \Exception('Stock insuficiente para el producto: ' . $producto['nombre']);
                }

                // Insertar detalle del pedido
                $sql_det = "INSERT INTO pedido_detalles(id_pedido, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
                $params_det = [
                    $id_pedido,
                    $detalle['id_producto'],
                    $detalle['cantidad'],
                    $detalle['precio_unitario']
                ];
                
                error_log("SQL para insertar detalle: " . $sql_det);
                error_log("Parámetros del detalle: " . json_encode($params_det));
                
                $stmt_det = $conex1->prepare($sql_det);
                $stmt_det->execute($params_det);
                
                error_log("Detalle insertado para producto ID: " . $detalle['id_producto']);

                // Actualizar stock
                $sql_stock = "UPDATE producto SET stock_disponible = stock_disponible - ? WHERE id_producto = ?";
                $stmt_stock = $conex1->prepare($sql_stock);
                $stmt_stock->execute([$detalle['cantidad'], $detalle['id_producto']]);
            }

            // Registrar métodos de pago si existen
            $id_pago = null;
            if (isset($datos['metodos_pago']) && !empty($datos['metodos_pago'])) {
                $id_pago = $this->registrarMetodosPagoVenta($id_pedido, $datos['metodos_pago'], $conex1);
            }
            
            // Actualizar el pedido con el ID del pago si se registraron métodos de pago
            if ($id_pago) {
                $sql_update_pedido = "UPDATE pedido SET id_pago = ? WHERE id_pedido = ?";
                $stmt_update = $conex1->prepare($sql_update_pedido);
                $stmt_update->execute([$id_pago, $id_pedido]);
            }

            // Bitácora
            $bitacora = [
                'id_persona' => $datos['id_persona'],
                'accion' => 'Registro de venta',
                'descripcion' => 'Se registró una nueva venta con ID: ' . $id_pedido
            ];
            $this->registrarBitacora(json_encode($bitacora));
            
            error_log("Commit de la transacción exitoso");
            $conex1->commit();
            $conex1 = null;
            $conex2 = null;
            
            $respuesta_final = ['respuesta' => 1, 'id_pedido' => $id_pedido];
            error_log("Respuesta final del modelo: " . json_encode($respuesta_final));
            
            return $respuesta_final;
        } catch (\Exception $e) {
            error_log("Error en registro de venta: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if ($conex1) {
                $conex1->rollBack();
                $conex1 = null;
            }
            if ($conex2) {
                $conex2 = null;
            }
            
            $respuesta_error = ['respuesta' => 0, 'mensaje' => $e->getMessage()];
            error_log("Respuesta de error: " . json_encode($respuesta_error));
            
            return $respuesta_error;
        }
    }

    // Método para registrar los métodos de pago de una venta
    private function registrarMetodosPagoVenta($id_pedido, $metodos_pago, $conex) {
        $id_pago_principal = null;
        
        foreach ($metodos_pago as $metodo) {
            // Validar datos del método de pago
            if (!isset($metodo['id_metodopago']) || $metodo['id_metodopago'] <= 0) {
                throw new \Exception('ID de método de pago no válido');
            }
            
            if (!isset($metodo['monto_usd']) || $metodo['monto_usd'] <= 0) {
                throw new \Exception('Monto USD no válido para método de pago');
            }

            // 1. Insertar en detalle_pago (sin id_pedido, esa relación es al revés)
            $sql = "INSERT INTO detalle_pago (id_metodopago, monto_usd, monto) VALUES (?, ?, ?)";
            
            $params = [
                $metodo['id_metodopago'],
                $metodo['monto_usd'],
                $metodo['monto_bs'] ?? 0.00
            ];
            
            $stmt = $conex->prepare($sql);
            $stmt->execute($params);
            $id_pago = $conex->lastInsertId();
            
            // Guardar el primer id_pago para asociarlo al pedido
            if ($id_pago_principal === null) {
                $id_pago_principal = $id_pago;
            }
            
            // 2. Si hay datos de referencia bancaria, insertar en referencia_pago
            if (!empty($metodo['referencia']) || !empty($metodo['telefono_emisor']) || 
                !empty($metodo['banco_receptor']) || !empty($metodo['banco_emisor'])) {
                $sql_referencia = "INSERT INTO referencia_pago (
                    id_pago, banco_emisor, banco_receptor, referencia, telefono_emisor
                ) VALUES (?, ?, ?, ?, ?)";
                
                $params_referencia = [
                    $id_pago,
                    $metodo['banco_emisor'] ?? null,
                    $metodo['banco_receptor'] ?? null,
                    $metodo['referencia'] ?? null,
                    $metodo['telefono_emisor'] ?? null
                ];
                
                $stmt_referencia = $conex->prepare($sql_referencia);
                $stmt_referencia->execute($params_referencia);
            }
        }
        
        // Retornar el id_pago principal para asociarlo al pedido
        return $id_pago_principal;
    }

    public function actualizarVentaPublico($datos) {
        return $this->actualizarVentaPrivado($datos);
    }
    private function actualizarVentaPrivado($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            // Si el estado es 2 (vendido), cambiar el tipo a 2 también
            // Usa estatus en lugar de estado según la nueva estructura de BD
            if ($datos['estado'] == '2') {
                $sql = "UPDATE pedido SET estatus = ?, tipo = '2' WHERE id_pedido = ?";
            } else {
                $sql = "UPDATE pedido SET estatus = ? WHERE id_pedido = ?";
            }
            
            $params = [$datos['estado'], $datos['id_pedido']];
            $stmt = $conex->prepare($sql);
            $stmt->execute($params);
            
            // Bitácora
            $bitacora = [
                'id_persona' => $_SESSION['id'] ?? null,
                'accion' => 'Actualización de venta',
                'descripcion' => 'Se actualizó la venta con ID: ' . $datos['id_pedido'] . ' - Estado: ' . $datos['estado']
            ];
            $this->registrarBitacora(json_encode($bitacora));
            $conex->commit();
            $conex = null;
            return ['respuesta' => 1];
        } catch (\Exception $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }
    public function eliminarVentaPublico($datos) {
        return $this->eliminarVentaPrivado($datos);
    }
    private function eliminarVentaPrivado($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            $sql_verificar = "SELECT id_pedido FROM pedido WHERE id_pedido = ?";
            $stmt_verificar = $conex->prepare($sql_verificar);
            $stmt_verificar->execute([$datos['id_pedido']]);
            if (!$stmt_verificar->fetch()) {
                throw new \Exception('El pedido no existe');
            }
            $sql_detalles = "SELECT id_producto, cantidad FROM pedido_detalles WHERE id_pedido = ?";
            $stmt_detalles = $conex->prepare($sql_detalles);
            $stmt_detalles->execute([$datos['id_pedido']]);
            $detalles = $stmt_detalles->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($detalles as $detalle) {
                $sql_stock = "UPDATE producto SET stock_disponible = stock_disponible + ? WHERE id_producto = ?";
                $stmt_stock = $conex->prepare($sql_stock);
                $stmt_stock->execute([$detalle['cantidad'], $detalle['id_producto']]);
            }
            $sql_eliminar_detalles = "DELETE FROM pedido_detalles WHERE id_pedido = ?";
            $stmt_eliminar_detalles = $conex->prepare($sql_eliminar_detalles);
            $stmt_eliminar_detalles->execute([$datos['id_pedido']]);
            $sql_eliminar = "DELETE FROM pedido WHERE id_pedido = ?";
            $stmt_eliminar = $conex->prepare($sql_eliminar);
            $stmt_eliminar->execute([$datos['id_pedido']]);
            // Bitácora
            $bitacora = [
                'id_persona' => $_SESSION['id'] ?? null,
                'accion' => 'Eliminación de venta',
                'descripcion' => 'Se eliminó la venta con ID: ' . $datos['id_pedido']
            ];
            $this->registrarBitacora(json_encode($bitacora));
            $conex->commit();
            $conex = null;
            return ['respuesta' => 1];
        } catch (\Exception $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }
    public function consultarClientePublico($datos) {
        return $this->consultarClientePrivado($datos);
    }
    private function consultarClientePrivado($datos) {
        $conex = $this->getConex2();
        try {
            // Consultar desde usuario y persona según la nueva estructura de BD
            $sql = "SELECT u.id_usuario as id_persona, per.cedula, per.nombre, per.apellido, per.correo, per.telefono 
                    FROM usuario u
                    INNER JOIN persona per ON u.cedula = per.cedula
                    WHERE per.cedula = ? AND u.estatus = 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute([$datos['cedula']]);
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            $conex = null;
            return [
                'respuesta' => $resultado ? 1 : 0,
                'cliente' => $resultado
            ];
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }
    public function registrarClientePublico($datos) {
        return $this->registrarClientePrivado($datos);
    }
    private function registrarClientePrivado($datos) {
        // Validar datos de entrada
        $campos_requeridos = ['cedula', 'nombre', 'apellido', 'telefono', 'correo'];
        foreach ($campos_requeridos as $campo) {
            if (!isset($datos[$campo]) || empty($datos[$campo])) {
                throw new \Exception("Campo {$campo} es obligatorio");
            }
        }

        // Validar formato de cédula
        if (!preg_match('/^[0-9]{7,8}$/', $datos['cedula'])) {
            throw new \Exception('Formato de cédula no válido');
        }

        // Validar formato de teléfono
        if (!preg_match('/^0[0-9]{10}$/', $datos['telefono'])) {
            throw new \Exception('Formato de teléfono no válido');
        }

        // Validar formato de correo
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Formato de correo no válido');
        }

        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            // Verificar si la cédula ya existe en persona
            $sql_verificar_persona = "SELECT cedula FROM persona WHERE cedula = ?";
            $stmt_verificar_persona = $conex->prepare($sql_verificar_persona);
            $stmt_verificar_persona->execute([$datos['cedula']]);
            $persona_existe = $stmt_verificar_persona->fetch();
            
            // Verificar si ya tiene usuario con rol de cliente (id_rol = 1)
            $sql_verificar_cliente = "SELECT id_usuario FROM usuario WHERE cedula = ? AND id_rol = 1 AND estatus = 1";
            $stmt_verificar_cliente = $conex->prepare($sql_verificar_cliente);
            $stmt_verificar_cliente->execute([$datos['cedula']]);
            $cliente_existe = $stmt_verificar_cliente->fetch();
            
            // Si ya tiene rol de cliente, lanzar error
            if ($cliente_existe) {
                throw new \Exception('La cédula ya está registrada como cliente');
            }
            
            // Si la persona no existe, insertar en persona
            if (!$persona_existe) {
                $sql_persona = "INSERT INTO persona (cedula, nombre, apellido, telefono, correo, tipo_documento) VALUES (?, ?, ?, ?, ?, 'V')";
                $params_persona = [
                    $datos['cedula'],
                    $datos['nombre'],
                    $datos['apellido'],
                    $datos['telefono'],
                    $datos['correo']
                ];
                $stmt_persona = $conex->prepare($sql_persona);
                $stmt_persona->execute($params_persona);
            } else {
                // Si la persona existe, actualizar sus datos
                $sql_actualizar_persona = "UPDATE persona SET nombre = ?, apellido = ?, telefono = ?, correo = ? WHERE cedula = ?";
                $stmt_actualizar_persona = $conex->prepare($sql_actualizar_persona);
                $stmt_actualizar_persona->execute([
                    $datos['nombre'],
                    $datos['apellido'],
                    $datos['telefono'],
                    $datos['correo'],
                    $datos['cedula']
                ]);
            }
            
            // 2. Insertar en usuario con rol de cliente (id_rol = 1)
            $sql_usuario = "INSERT INTO usuario (cedula, clave, estatus, id_rol) VALUES (?, '', 1, 1)";
            $stmt_usuario = $conex->prepare($sql_usuario);
            $stmt_usuario->execute([$datos['cedula']]);
            $id_cliente = $conex->lastInsertId();
            
            // Bitácora
            $bitacora = [
                'id_persona' => $_SESSION['id'] ?? null,
                'accion' => 'Registro de cliente',
                'descripcion' => 'Se registró un nuevo cliente con cédula: ' . $datos['cedula']
            ];
            $this->registrarBitacora(json_encode($bitacora));
            $conex->commit();
            $conex = null;
            return [
                'success' => true,
                'id_cliente' => $id_cliente,
                'message' => 'Cliente registrado exitosamente'
            ];
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Método para registrar en bitácora
    private function registrarBitacora($datos) {
        try {
            $conex2 = $this->getConex2();
            $datos_array = json_decode($datos, true);
            
            // Obtener la cédula desde el id_usuario si se proporciona id_persona
            $cedula = null;
            if (!empty($datos_array['id_persona'])) {
                $sql_cedula = "SELECT cedula FROM usuario WHERE id_usuario = ? AND estatus = 1";
                $stmt_cedula = $conex2->prepare($sql_cedula);
                $stmt_cedula->execute([$datos_array['id_persona']]);
                $usuario = $stmt_cedula->fetch(\PDO::FETCH_ASSOC);
                if ($usuario) {
                    $cedula = $usuario['cedula'];
                }
            }
            
            // Si no se pudo obtener la cédula, no registrar en bitácora
            if (empty($cedula)) {
                return false;
            }
            
            // La tabla bitacora está en BD2 y usa cedula (varchar)
            $sql = "INSERT INTO bitacora (cedula, accion, descripcion, fecha_hora) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $conex2->prepare($sql);
            $stmt->execute([
                $cedula,
                $datos_array['accion'],
                $datos_array['descripcion']
            ]);
            
            $conex2 = null;
            return true;
        } catch (\Exception $e) {
            // Si falla la bitácora, no afectar la operación principal
            return false;
        }
    }
}
?>