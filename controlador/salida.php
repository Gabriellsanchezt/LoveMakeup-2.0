<?php

use LoveMakeup\Proyecto\Modelo\Salida;
use LoveMakeup\Proyecto\Modelo\Bitacora;

session_start();
if (empty($_SESSION["id"])) {
    header("location:?pagina=login");
    exit;
}

// Detectar si es una petición AJAX ANTES de cargar archivos que pueden generar output
$esAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                 (isset($_POST['registrar']) || isset($_POST['buscar_cliente']) || 
                  isset($_POST['registrar_cliente']) || isset($_POST['actualizar']) || 
                  isset($_POST['eliminar']));

// Solo cargar estos archivos si NO es una petición AJAX/POST que requiere respuesta JSON
if (!$esAjaxRequest) {
if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
} 

if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }
    
    require_once 'permiso.php';
}
    
require_once 'modelo/salida.php';
$salida = new Salida();

// Detectar si la solicitud es AJAX
function esAjax() {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

// Función para sanitizar datos de entrada
function sanitizar($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Procesar el registro de una nueva venta
if (isset($_POST['registrar'])) {
    // Limpiar cualquier output previo y asegurar respuesta JSON limpia
    if (ob_get_level() > 0) {
        ob_clean();
            }
    
    try {
        // Validar sesión y permisos básicos para peticiones POST
        if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] == 1) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'respuesta' => 0,
                'error' => 'No tiene permisos para realizar esta acción'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Validar token CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new \Exception('Error de validación del formulario');
        }

            // Validar datos requeridos
        if (empty($_POST['precio_total']) || !is_numeric($_POST['precio_total']) || $_POST['precio_total'] <= 0) {
            throw new \Exception('Precio total inválido');
            }

            if (!isset($_POST['id_producto']) || !is_array($_POST['id_producto']) || empty($_POST['id_producto'])) {
            throw new \Exception('Debe seleccionar al menos un producto');
            }

        // Procesar cliente (nuevo o existente)
        $id_persona = null;
            if (isset($_POST['registrar_cliente_con_venta'])) {
            // Registrar cliente nuevo
                $datosCliente = [
                    'cedula' => sanitizar($_POST['cedula_cliente']),
                    'nombre' => sanitizar($_POST['nombre_cliente']),
                    'apellido' => sanitizar($_POST['apellido_cliente']),
                    'telefono' => sanitizar($_POST['telefono_cliente']),
                    'correo' => sanitizar($_POST['correo_cliente'])
                ];

            // Validar campos del cliente
                foreach ($datosCliente as $campo => $valor) {
                    if (empty($valor)) {
                    throw new \Exception("Campo {$campo} del cliente es obligatorio");
                    }
                }

                if (!preg_match('/^[0-9]{7,8}$/', $datosCliente['cedula'])) {
                throw new \Exception('Formato de cédula inválido');
                }

                if (!preg_match('/^0[0-9]{10}$/', $datosCliente['telefono'])) {
                throw new \Exception('Formato de teléfono inválido');
                }

                if (!filter_var($datosCliente['correo'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Formato de correo inválido');
                }

                $respuestaCliente = $salida->registrarClientePublico($datosCliente);
                if (!$respuestaCliente['success']) {
                throw new \Exception('Error al registrar cliente: ' . $respuestaCliente['message']);
                }

                $id_persona = $respuestaCliente['id_cliente'];
            } else {
            // Usar cliente existente
                if (empty($_POST['id_persona'])) {
                throw new \Exception('ID de cliente no proporcionado');
                }
                $id_persona = intval($_POST['id_persona']);
            }

        // Preparar datos de la venta
            $datosVenta = [
                'id_persona' => $id_persona,
                'precio_total' => floatval($_POST['precio_total']),
                'precio_total_bs' => floatval($_POST['precio_total_bs'] ?? 0),
            'detalles' => [],
            'metodos_pago' => []
            ];

        // Procesar detalles de productos
            for ($i = 0; $i < count($_POST['id_producto']); $i++) {
                if (!empty($_POST['id_producto'][$i]) && isset($_POST['cantidad'][$i]) && $_POST['cantidad'][$i] > 0) {
                    $id_producto = intval($_POST['id_producto'][$i]);
                    $cantidad = intval($_POST['cantidad'][$i]);
                    $precio_unitario = floatval($_POST['precio_unitario'][$i]);

                    if ($id_producto <= 0 || $cantidad <= 0 || $precio_unitario <= 0) {
                    throw new \Exception('Datos de producto inválidos en la fila ' . ($i + 1));
                    }

                        $datosVenta['detalles'][] = [
                        'id_producto' => $id_producto,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio_unitario
                    ];
                }
            }

        if (empty($datosVenta['detalles'])) {
            throw new \Exception('Debe seleccionar al menos un producto válido');
            }

        // Procesar métodos de pago
            if (isset($_POST['id_metodopago']) && is_array($_POST['id_metodopago'])) {
                $totalMetodosPago = 0;
                $metodosPagoUnicos = [];

                for ($i = 0; $i < count($_POST['id_metodopago']); $i++) {
                    $idMetodo = intval($_POST['id_metodopago'][$i]);
                    $montoMetodo = floatval($_POST['monto_metodopago'][$i]);

                    if ($idMetodo > 0 && $montoMetodo > 0) {
                        $key = $idMetodo . '-' . $montoMetodo;
                        if (!isset($metodosPagoUnicos[$key])) {
                            $metodo = [
                                'id_metodopago' => $idMetodo,
                                'monto_usd' => $montoMetodo,
                                'monto_bs' => 0.00,
                                'referencia' => null,
                                'banco_emisor' => null,
                                'banco_receptor' => null,
                                'telefono_emisor' => null
                            ];

                        // Obtener nombre del método de pago
                        $sql = "SELECT nombre FROM metodo_pago WHERE id_metodopago = ? AND estatus = 1";
                        $stmt = $salida->getConex1()->prepare($sql);
                        $stmt->execute([$idMetodo]);
                        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $nombreMetodo = $resultado ? $resultado['nombre'] : '';

                        // Procesar detalles según el método
                        switch($nombreMetodo) {
                            case 'Efectivo Bs':
                                if (isset($_POST['monto_efectivo_bs']) && $_POST['monto_efectivo_bs'] > 0) {
                                    $metodo['monto_bs'] = floatval($_POST['monto_efectivo_bs']);
                                }
                                break;
                            case 'Pago Movil':
                                if (isset($_POST['monto_pm_bs']) && $_POST['monto_pm_bs'] > 0) {
                                    $metodo['monto_bs'] = floatval($_POST['monto_pm_bs']);
                                }
                                if (isset($_POST['banco_emisor_pm'])) {
                                    $metodo['banco_emisor'] = sanitizar($_POST['banco_emisor_pm']);
                                }
                                if (isset($_POST['banco_receptor_pm'])) {
                                    $metodo['banco_receptor'] = sanitizar($_POST['banco_receptor_pm']);
                                }
                                if (isset($_POST['referencia_pm'])) {
                                    $metodo['referencia'] = sanitizar($_POST['referencia_pm']);
                                }
                                if (isset($_POST['telefono_emisor_pm'])) {
                                    $metodo['telefono_emisor'] = sanitizar($_POST['telefono_emisor_pm']);
                                }
                                break;
                            case 'Punto de Venta':
                                if (isset($_POST['monto_pv_bs']) && $_POST['monto_pv_bs'] > 0) {
                                    $metodo['monto_bs'] = floatval($_POST['monto_pv_bs']);
                                }
                                if (isset($_POST['referencia_pv'])) {
                                    $metodo['referencia'] = sanitizar($_POST['referencia_pv']);
                                }
                                break;
                            case 'Transferencia Bancaria':
                                if (isset($_POST['monto_tb_bs']) && $_POST['monto_tb_bs'] > 0) {
                                    $metodo['monto_bs'] = floatval($_POST['monto_tb_bs']);
                                }
                                if (isset($_POST['referencia_tb'])) {
                                    $metodo['referencia'] = sanitizar($_POST['referencia_tb']);
                                }
                                break;
                        }

                        $datosVenta['metodos_pago'][] = $metodo;
                            $totalMetodosPago += $metodo['monto_usd'];
                            $metodosPagoUnicos[$key] = true;
                        }
                    }
                }

                // Validar que la suma de métodos de pago coincida con el total
                if (abs($totalMetodosPago - $datosVenta['precio_total']) > 0.01) {
                throw new \Exception('La suma de los métodos de pago ($' . number_format($totalMetodosPago, 2) . ') no coincide con el total de la venta ($' . number_format($datosVenta['precio_total'], 2) . ')');
                }

            if (empty($datosVenta['metodos_pago'])) {
                throw new \Exception('Debe seleccionar al menos un método de pago válido');
                }
            } else {
            throw new \Exception('Debe seleccionar al menos un método de pago');
            }

        // Registrar la venta
            $respuesta = $salida->registrarVentaPublico($datosVenta);
            
        if ($respuesta['respuesta'] == 1) {
            // Registrar en bitácora (solo si no es AJAX para evitar problemas)
            try {
                require_once 'modelo/bitacora.php';
                $bitacora = [
                    'id_persona' => $_SESSION["id"],
                    'accion' => 'Registro de venta',
                    'descripcion' => 'Se registró la venta ID: ' . $respuesta['id_pedido']
                ];
                $bitacoraObj = new Bitacora();
                $bitacoraObj->registrarOperacion($bitacora['accion'], 'salida', $bitacora);
            } catch (\Exception $e) {
                // Si falla la bitácora, no afecta la respuesta
                error_log("Error al registrar en bitácora: " . $e->getMessage());
            }

            // Siempre responder con JSON para peticiones POST
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                    'respuesta' => 1,
                    'mensaje' => 'Venta registrada exitosamente',
                    'id_pedido' => $respuesta['id_pedido']
            ], JSON_UNESCAPED_UNICODE);
            exit;
            } else {
            throw new \Exception($respuesta['mensaje'] ?? 'Error al registrar la venta');
        }
    } catch (\Exception $e) {
        // Siempre responder con JSON para peticiones POST
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
                    'respuesta' => 0,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Procesar búsqueda de cliente (AJAX)
if (isset($_POST['buscar_cliente'])) {
    try {
        $datos = ['cedula' => sanitizar($_POST['cedula'])];
        $respuesta = $salida->consultarClientePublico($datos);
        header('Content-Type: application/json');
        echo json_encode($respuesta);
        exit;
    } catch (\Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
                'respuesta' => 0,
                'error' => $e->getMessage()
            ]);
        exit;
    }
}

// Procesar registro de cliente (AJAX)
if (isset($_POST['registrar_cliente'])) {
    try {
        $datos = [
            'cedula' => sanitizar($_POST['cedula']),
            'nombre' => sanitizar($_POST['nombre']),
            'apellido' => sanitizar($_POST['apellido']),
            'telefono' => sanitizar($_POST['telefono']),
            'correo' => sanitizar($_POST['correo'])
        ];
        $respuesta = $salida->registrarClientePublico($datos);
        header('Content-Type: application/json');
        echo json_encode($respuesta);
        exit;
    } catch (\Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
        exit;
        }
    }

    // Procesar actualización de venta
    if (isset($_POST['actualizar'])) {
        try {
            $datosVenta = [
            'id_pedido' => intval($_POST['id_pedido']),
            'estado' => sanitizar($_POST['estado_pedido'])
            ];
            $respuesta = $salida->actualizarVentaPublico($datosVenta);

        if ($respuesta['respuesta'] == 1) {
            $bitacora = [
                'id_persona' => $_SESSION["id"],
                'accion' => 'Modificación de venta',
                'descripcion' => 'Se modificó la venta ID: ' . $datosVenta['id_pedido']
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bitacora['accion'], 'salida', $bitacora);
        }

        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode($respuesta);
            exit;
        } else {
            $_SESSION['message'] = [
                'title' => ($respuesta['respuesta'] == 1) ? '¡Éxito!' : 'Error',
                'text' => $respuesta['mensaje'],
                'icon' => ($respuesta['respuesta'] == 1) ? 'success' : 'error'
            ];
            header("Location: ?pagina=salida");
            exit;
        }
    } catch (\Exception $e) {
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                    'respuesta' => 0,
                    'error' => $e->getMessage()
                ]);
            exit;
        } else {
            $_SESSION['message'] = [
                'title' => 'Error',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ];
            header("Location: ?pagina=salida");
            exit;
        }
    }
}

    // Procesar eliminación de venta
    if (isset($_POST['eliminar'])) {
        try {
        $datosVenta = ['id_pedido' => intval($_POST['eliminar'])];
            $respuesta = $salida->eliminarVentaPublico($datosVenta);

        if ($respuesta['respuesta'] == 1) {
            $bitacora = [
                'id_persona' => $_SESSION["id"],
                'accion' => 'Eliminación de venta',
                'descripcion' => 'Se eliminó la venta ID: ' . $datosVenta['id_pedido']
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bitacora['accion'], 'salida', $bitacora);
        }

        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode($respuesta);
            exit;
        } else {
            $_SESSION['message'] = [
                'title' => ($respuesta['respuesta'] == 1) ? '¡Éxito!' : 'Error',
                'text' => $respuesta['mensaje'],
                'icon' => ($respuesta['respuesta'] == 1) ? 'success' : 'error'
            ];
            header("Location: ?pagina=salida");
            exit;
        }
    } catch (\Exception $e) {
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                    'respuesta' => 0,
                    'error' => $e->getMessage()
                ]);
            exit;
        } else {
            $_SESSION['message'] = [
                'title' => 'Error',
                'text' => $e->getMessage(),
                'icon' => 'error'
            ];
            header("Location: ?pagina=salida");
            exit;
        }
    }
}

// Generar o verificar el token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Solo consultar datos para la vista si NO es una petición POST/AJAX
if (!$esAjaxRequest && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Consultar datos para la vista
    try {
        // Consulta de prueba: verificar todos los pedidos tipo 1 sin filtrar por estado
        $conex = $salida->getConex1();
        $sql_test = "SELECT COUNT(*) as total FROM pedido WHERE (tipo = '1' OR tipo = 1)";
        $stmt_test = $conex->prepare($sql_test);
        $stmt_test->execute();
        $test_result = $stmt_test->fetch(\PDO::FETCH_ASSOC);
        error_log("Total de pedidos tipo 1: " . $test_result['total']);
        
        $sql_test2 = "SELECT COUNT(*) as total FROM pedido WHERE (tipo = '1' OR tipo = 1) AND (estado = '2' OR estado = 2)";
        $stmt_test2 = $conex->prepare($sql_test2);
        $stmt_test2->execute();
        $test_result2 = $stmt_test2->fetch(\PDO::FETCH_ASSOC);
        error_log("Total de pedidos tipo 1 y estado 2: " . $test_result2['total']);
        
        $ventas = $salida->consultarVentas();
        $productos_lista = $salida->consultarProductos();
        $metodos_pago = $salida->consultarMetodosPago();
        
        // Asegurar que las variables estén definidas
        if (!isset($ventas)) {
            $ventas = [];
        }
        if (!isset($productos_lista)) {
            $productos_lista = [];
        }
        if (!isset($metodos_pago)) {
            $metodos_pago = [];
        }
        
        // Depuración temporal - remover en producción
        error_log("Ventas consultadas: " . count($ventas));
        if (count($ventas) > 0) {
            error_log("Primera venta: " . json_encode($ventas[0]));
        } else {
            error_log("ADVERTENCIA: consultarVentas() devolvió un array vacío");
        }
    } catch (\Exception $e) {
        // Si hay error, inicializar arrays vacíos
        $ventas = [];
        $productos_lista = [];
        $metodos_pago = [];
        error_log("Error al consultar datos en salida: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }

    // Registrar acceso en bitácora
    try {
        require_once 'modelo/bitacora.php';
    $bitacora = [
        'id_persona' => $_SESSION["id"],
        'accion' => 'Acceso a Módulo',
        'descripcion' => 'módulo de Ventas'
    ];
    $bitacoraObj = new Bitacora();
    $bitacoraObj->registrarOperacion($bitacora['accion'], 'salida', $bitacora);
    } catch (\Exception $e) {
        error_log("Error al registrar en bitácora: " . $e->getMessage());
    }

    // Cargar la vista
    if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(4, 'ver')) {
             $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'salida';
            require_once 'vista/salida.php';
    } else {
            require_once 'vista/seguridad/privilegio.php';
    }
}
