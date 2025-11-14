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

// Función para sanitizar datos de entrada (protección XSS)
function sanitizar($dato) {
    if (is_null($dato)) {
        return null;
    }
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Función para validar y limpiar nombres (solo letras, espacios y caracteres especiales permitidos)
function validarYLimpiarNombre($nombre, $campo = 'nombre', $maxLength = 100) {
    if (empty($nombre)) {
        throw new \Exception("El campo {$campo} es obligatorio");
    }
    
    $nombre = trim($nombre);
    
    // Validar longitud máxima
    if (strlen($nombre) > $maxLength) {
        throw new \Exception("El campo {$campo} no puede exceder {$maxLength} caracteres");
    }
    
    // Validar que solo contenga letras, espacios, acentos y caracteres especiales comunes
    if (!preg_match('/^[A-Za-zÁáÉéÍíÓóÚúÑñÜü\s\'-]+$/u', $nombre)) {
        throw new \Exception("El campo {$campo} contiene caracteres no permitidos");
    }
    
    // Detectar caracteres peligrosos SQL (aunque los prepared statements protegen, es una capa adicional)
    $caracteresPeligrosos = ["'", '"', ';', '--', '/*', '*/', 'xp_', 'sp_', 'exec', 'union', 'select', 'insert', 'update', 'delete', 'drop', 'create', 'alter'];
    foreach ($caracteresPeligrosos as $peligroso) {
        if (stripos($nombre, $peligroso) !== false) {
            throw new \Exception("El campo {$campo} contiene caracteres no permitidos");
        }
    }
    
    return sanitizar($nombre);
}

// Función para validar y limpiar texto general (para referencias, etc.)
function validarYLimpiarTexto($texto, $campo = 'texto', $maxLength = 255, $soloNumeros = false) {
    if (empty($texto)) {
        throw new \Exception("El campo {$campo} es obligatorio");
    }
    
    $texto = trim($texto);
    
    // Validar longitud máxima
    if (strlen($texto) > $maxLength) {
        throw new \Exception("El campo {$campo} no puede exceder {$maxLength} caracteres");
    }
    
    if ($soloNumeros) {
        // Solo números
        if (!preg_match('/^\d+$/', $texto)) {
            throw new \Exception("El campo {$campo} solo puede contener números");
        }
    } else {
        // Validar caracteres alfanuméricos y algunos especiales
        if (!preg_match('/^[A-Za-z0-9\s\-_\.]+$/', $texto)) {
            throw new \Exception("El campo {$campo} contiene caracteres no permitidos");
        }
    }
    
    // Detectar caracteres peligrosos SQL
    $caracteresPeligrosos = ["'", '"', ';', '--', '/*', '*/', 'xp_', 'sp_', 'exec', 'union', 'select', 'insert', 'update', 'delete', 'drop', 'create', 'alter'];
    foreach ($caracteresPeligrosos as $peligroso) {
        if (stripos($texto, $peligroso) !== false) {
            throw new \Exception("El campo {$campo} contiene caracteres no permitidos");
        }
    }
    
    return sanitizar($texto);
}

// Función para validar ID (debe ser entero positivo)
function validarId($id, $campo = 'ID') {
    if (empty($id)) {
        throw new \Exception("El campo {$campo} es obligatorio");
    }
    
    // Convertir a entero y validar
    $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    
    if ($id === false) {
        throw new \Exception("El campo {$campo} debe ser un número entero positivo válido");
    }
    
    return $id;
}

// Función para validar número decimal positivo
function validarDecimal($numero, $campo = 'número', $min = 0) {
    if (!isset($numero) || $numero === '') {
        throw new \Exception("El campo {$campo} es obligatorio");
    }
    
    $numero = filter_var($numero, FILTER_VALIDATE_FLOAT);
    
    if ($numero === false) {
        throw new \Exception("El campo {$campo} debe ser un número válido");
    }
    
    if ($numero < $min) {
        throw new \Exception("El campo {$campo} debe ser mayor o igual a {$min}");
    }
    
    return floatval($numero);
}

// Función para validar nombre de banco (lista blanca de caracteres)
function validarNombreBanco($banco, $campo = 'banco') {
    if (empty($banco)) {
        throw new \Exception("El campo {$campo} es obligatorio");
    }
    
    $banco = trim($banco);
    
    // Validar longitud
    if (strlen($banco) > 100) {
        throw new \Exception("El campo {$campo} no puede exceder 100 caracteres");
    }
    
    // Solo letras, espacios y algunos caracteres especiales
    if (!preg_match('/^[A-Za-zÁáÉéÍíÓóÚúÑñÜü\s\-\.]+$/u', $banco)) {
        throw new \Exception("El campo {$campo} contiene caracteres no permitidos");
    }
    
    // Detectar caracteres peligrosos SQL
    $caracteresPeligrosos = ["'", '"', ';', '--', '/*', '*/', 'xp_', 'sp_', 'exec', 'union', 'select', 'insert', 'update', 'delete', 'drop', 'create', 'alter'];
    foreach ($caracteresPeligrosos as $peligroso) {
        if (stripos($banco, $peligroso) !== false) {
            throw new \Exception("El campo {$campo} contiene caracteres no permitidos");
        }
    }
    
    return sanitizar($banco);
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

            // Validar datos requeridos con validaciones robustas
        $precio_total = validarDecimal($_POST['precio_total'] ?? 0, 'precio total', 0.01);
        $precio_total_bs = validarDecimal($_POST['precio_total_bs'] ?? 0, 'precio total en bolívares', 0);

            if (!isset($_POST['id_producto']) || !is_array($_POST['id_producto'])) {
            throw new \Exception('Debe seleccionar al menos un producto');
            }

        // Validar longitud de arrays para prevenir DoS
        if (count($_POST['id_producto']) > 100) {
            throw new \Exception('No se pueden procesar más de 100 productos a la vez');
        }
        
        if (empty($_POST['id_producto'])) {
            throw new \Exception('Debe seleccionar al menos un producto');
        }

        // Procesar cliente (nuevo o existente)
        $id_persona = null;
            if (isset($_POST['registrar_cliente_con_venta'])) {
            // Registrar cliente nuevo - Validaciones robustas contra SQL injection
                if (!isset($_POST['cedula_cliente']) || !isset($_POST['nombre_cliente']) || 
                    !isset($_POST['apellido_cliente']) || !isset($_POST['telefono_cliente']) || 
                    !isset($_POST['correo_cliente'])) {
                    throw new \Exception('Todos los campos del cliente son obligatorios');
                }

                // Validar cédula (solo números, 7-8 dígitos)
                $cedula = trim($_POST['cedula_cliente']);
                if (empty($cedula)) {
                    throw new \Exception('La cédula es obligatoria');
                }
                if (!preg_match('/^\d{7,8}$/', $cedula)) {
                    throw new \Exception('Formato de cédula inválido. Debe tener entre 7 y 8 dígitos numéricos');
                }
                // Validar que no contenga caracteres SQL peligrosos
                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $cedula)) {
                    throw new \Exception('La cédula contiene caracteres no permitidos');
                }
                $datosCliente['cedula'] = $cedula;

                // Validar nombre
                $datosCliente['nombre'] = validarYLimpiarNombre($_POST['nombre_cliente'], 'nombre', 100);

                // Validar apellido
                $datosCliente['apellido'] = validarYLimpiarNombre($_POST['apellido_cliente'], 'apellido', 100);

                // Validar teléfono (solo números, formato específico)
                $telefono = trim($_POST['telefono_cliente']);
                if (empty($telefono)) {
                    throw new \Exception('El teléfono es obligatorio');
                }
                if (!preg_match('/^0\d{10}$/', $telefono)) {
                    throw new \Exception('Formato de teléfono inválido. Debe comenzar con 0 y tener 11 dígitos');
                }
                // Validar que no contenga caracteres SQL peligrosos
                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $telefono)) {
                    throw new \Exception('El teléfono contiene caracteres no permitidos');
                }
                $datosCliente['telefono'] = $telefono;

                // Validar correo electrónico
                $correo = trim($_POST['correo_cliente']);
                if (empty($correo)) {
                    throw new \Exception('El correo es obligatorio');
                }
                // Validar formato de correo
                if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception('Formato de correo inválido');
                }
                // Validar longitud máxima
                if (strlen($correo) > 255) {
                    throw new \Exception('El correo no puede exceder 255 caracteres');
                }
                // Validar que no contenga caracteres SQL peligrosos
                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $correo)) {
                    throw new \Exception('El correo contiene caracteres no permitidos');
                }
                $datosCliente['correo'] = filter_var($correo, FILTER_SANITIZE_EMAIL);

                $respuestaCliente = $salida->registrarClientePublico($datosCliente);
                if (!$respuestaCliente['success']) {
                throw new \Exception('Error al registrar cliente: ' . $respuestaCliente['message']);
                }

                $id_persona = $respuestaCliente['id_cliente'];
            } else {
            // Usar cliente existente - Validar ID contra SQL injection
                if (empty($_POST['id_persona'])) {
                throw new \Exception('ID de cliente no proporcionado');
                }
                $id_persona = validarId($_POST['id_persona'], 'ID de cliente');
            }

        // Preparar datos de la venta
            $datosVenta = [
                'id_persona' => $id_persona,
                'precio_total' => $precio_total,
                'precio_total_bs' => $precio_total_bs,
            'detalles' => [],
            'metodos_pago' => []
            ];

        // Procesar detalles de productos con validaciones robustas
            $totalCantidadProductos = 0;
            // Validar que los arrays tengan la misma longitud
            if (count($_POST['id_producto']) !== count($_POST['cantidad'] ?? []) || 
                count($_POST['id_producto']) !== count($_POST['precio_unitario'] ?? [])) {
                throw new \Exception('Los datos de productos están incompletos');
            }
            
            for ($i = 0; $i < count($_POST['id_producto']); $i++) {
                if (!empty($_POST['id_producto'][$i]) && isset($_POST['cantidad'][$i]) && $_POST['cantidad'][$i] > 0) {
                    // Validar ID de producto
                    $id_producto = validarId($_POST['id_producto'][$i], 'ID de producto en fila ' . ($i + 1));
                    
                    // Validar cantidad (debe ser entero positivo)
                    $cantidad = filter_var($_POST['cantidad'][$i], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]);
                    if ($cantidad === false) {
                        throw new \Exception('Cantidad inválida en la fila ' . ($i + 1) . '. Debe ser un número entero entre 1 y 9999');
                    }
                    
                    // Validar precio unitario
                    $precio_unitario = validarDecimal($_POST['precio_unitario'][$i] ?? 0, 'precio unitario en fila ' . ($i + 1), 0.01);

                        $datosVenta['detalles'][] = [
                        'id_producto' => $id_producto,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio_unitario
                    ];
                    $totalCantidadProductos += $cantidad;
                }
            }

        if (empty($datosVenta['detalles'])) {
            throw new \Exception('Debe seleccionar al menos un producto válido');
            }

        // Validar cantidad total de productos
        if ($totalCantidadProductos <= 0) {
            throw new \Exception('La cantidad total de productos debe ser mayor a 0');
        }

        // Procesar métodos de pago con validaciones robustas
            if (isset($_POST['id_metodopago']) && is_array($_POST['id_metodopago'])) {
                // Validar longitud de arrays para prevenir DoS
                if (count($_POST['id_metodopago']) > 10) {
                    throw new \Exception('No se pueden procesar más de 10 métodos de pago a la vez');
                }
                
                $totalMetodosPago = 0;
                $metodosPagoUnicos = [];

                for ($i = 0; $i < count($_POST['id_metodopago']); $i++) {
                    // Validar ID de método de pago
                    $idMetodo = validarId($_POST['id_metodopago'][$i], 'ID de método de pago en fila ' . ($i + 1));
                    
                    // Validar monto
                    $montoMetodo = validarDecimal($_POST['monto_metodopago'][$i] ?? 0, 'monto de método de pago en fila ' . ($i + 1), 0.01);

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
                                
                                // Validaciones específicas de Pago Móvil
                                if (!isset($_POST['banco_emisor_pm']) || empty($_POST['banco_emisor_pm'])) {
                                    throw new \Exception('Seleccione un banco emisor para Pago Móvil');
                                }
                                $metodo['banco_emisor'] = validarNombreBanco($_POST['banco_emisor_pm'], 'banco emisor');
                                
                                if (!isset($_POST['banco_receptor_pm']) || empty($_POST['banco_receptor_pm'])) {
                                    throw new \Exception('Seleccione un banco receptor para Pago Móvil');
                                }
                                $metodo['banco_receptor'] = validarNombreBanco($_POST['banco_receptor_pm'], 'banco receptor');
                                
                                if (!isset($_POST['referencia_pm']) || empty($_POST['referencia_pm'])) {
                                    throw new \Exception('La referencia de Pago Móvil es obligatoria');
                                }
                                $referenciaPM = trim($_POST['referencia_pm']);
                                // Validar formato (solo números, 4-6 dígitos)
                                if (!preg_match('/^\d{4,6}$/', $referenciaPM)) {
                                    throw new \Exception('La referencia de Pago Móvil debe tener entre 4 y 6 dígitos numéricos');
                                }
                                // Validar contra SQL injection
                                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $referenciaPM)) {
                                    throw new \Exception('La referencia contiene caracteres no permitidos');
                                }
                                $metodo['referencia'] = $referenciaPM;
                                
                                if (!isset($_POST['telefono_emisor_pm']) || empty($_POST['telefono_emisor_pm'])) {
                                    throw new \Exception('El teléfono emisor de Pago Móvil es obligatorio');
                                }
                                $telefonoPM = trim($_POST['telefono_emisor_pm']);
                                // Validar formato (solo números, 11 dígitos)
                                if (!preg_match('/^\d{11}$/', $telefonoPM)) {
                                    throw new \Exception('El teléfono emisor debe tener 11 dígitos numéricos');
                                }
                                // Validar contra SQL injection
                                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $telefonoPM)) {
                                    throw new \Exception('El teléfono contiene caracteres no permitidos');
                                }
                                $metodo['telefono_emisor'] = $telefonoPM;
                                break;
                            case 'Punto de Venta':
                                if (isset($_POST['monto_pv_bs']) && $_POST['monto_pv_bs'] > 0) {
                                    $metodo['monto_bs'] = floatval($_POST['monto_pv_bs']);
                                }
                                
                                // Validación de referencia para Punto de Venta
                                if (!isset($_POST['referencia_pv']) || empty($_POST['referencia_pv'])) {
                                    throw new \Exception('La referencia de Punto de Venta es obligatoria');
                                }
                                $referenciaPV = trim($_POST['referencia_pv']);
                                // Validar formato (solo números, 4-6 dígitos)
                                if (!preg_match('/^\d{4,6}$/', $referenciaPV)) {
                                    throw new \Exception('La referencia de Punto de Venta debe tener entre 4 y 6 dígitos numéricos');
                                }
                                // Validar contra SQL injection
                                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $referenciaPV)) {
                                    throw new \Exception('La referencia contiene caracteres no permitidos');
                                }
                                $metodo['referencia'] = $referenciaPV;
                                break;
                            case 'Transferencia Bancaria':
                                if (isset($_POST['monto_tb_bs']) && $_POST['monto_tb_bs'] > 0) {
                                    $metodo['monto_bs'] = floatval($_POST['monto_tb_bs']);
                                }
                                
                                // Validación de referencia para Transferencia Bancaria
                                if (!isset($_POST['referencia_tb']) || empty($_POST['referencia_tb'])) {
                                    throw new \Exception('La referencia de Transferencia Bancaria es obligatoria');
                                }
                                $referenciaTB = trim($_POST['referencia_tb']);
                                // Validar formato (solo números, 4-6 dígitos)
                                if (!preg_match('/^\d{4,6}$/', $referenciaTB)) {
                                    throw new \Exception('La referencia de Transferencia Bancaria debe tener entre 4 y 6 dígitos numéricos');
                                }
                                // Validar contra SQL injection
                                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $referenciaTB)) {
                                    throw new \Exception('La referencia contiene caracteres no permitidos');
                                }
                                $metodo['referencia'] = $referenciaTB;
                                break;
                        }

                        $datosVenta['metodos_pago'][] = $metodo;
                            $totalMetodosPago += $metodo['monto_usd'];
                            $metodosPagoUnicos[$key] = true;
                        }
                    }
                }

                // Validar que la suma de métodos de pago no exceda el total (con tolerancia de 0.01 para errores de redondeo)
                $diferencia = $totalMetodosPago - $datosVenta['precio_total'];
                if ($diferencia > 0.01) {
                    throw new \Exception('La suma de los métodos de pago ($' . number_format($totalMetodosPago, 2) . ') excede el total de la venta ($' . number_format($datosVenta['precio_total'], 2) . ') por $' . number_format($diferencia, 2));
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
        // Validar que la cédula esté presente
        if (!isset($_POST['cedula']) || empty($_POST['cedula'])) {
            throw new \Exception('La cédula es obligatoria');
        }
        
        // Validar formato de cédula (solo números, 7-8 dígitos)
        $cedula = trim($_POST['cedula']);
        if (!preg_match('/^\d{7,8}$/', $cedula)) {
            throw new \Exception('Formato de cédula inválido. Debe tener entre 7 y 8 dígitos numéricos');
        }
        
        // Validar contra SQL injection (aunque prepared statements protegen, es capa adicional)
        if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $cedula)) {
            throw new \Exception('La cédula contiene caracteres no permitidos');
        }
        
        $datos = ['cedula' => $cedula];
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
        // Validar que todos los campos estén presentes
        if (!isset($_POST['cedula']) || !isset($_POST['nombre']) || 
            !isset($_POST['apellido']) || !isset($_POST['telefono']) || 
            !isset($_POST['correo'])) {
            throw new \Exception('Todos los campos del cliente son obligatorios');
        }
        
        // Validar cédula (solo números, 7-8 dígitos)
        $cedula = trim($_POST['cedula']);
        if (empty($cedula)) {
            throw new \Exception('La cédula es obligatoria');
        }
        if (!preg_match('/^\d{7,8}$/', $cedula)) {
            throw new \Exception('Formato de cédula inválido. Debe tener entre 7 y 8 dígitos numéricos');
        }
        if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $cedula)) {
            throw new \Exception('La cédula contiene caracteres no permitidos');
        }
        
        // Validar nombre
        $nombre = validarYLimpiarNombre($_POST['nombre'], 'nombre', 100);
        
        // Validar apellido
        $apellido = validarYLimpiarNombre($_POST['apellido'], 'apellido', 100);
        
        // Validar teléfono (solo números, formato específico)
        $telefono = trim($_POST['telefono']);
        if (empty($telefono)) {
            throw new \Exception('El teléfono es obligatorio');
        }
        if (!preg_match('/^0\d{10}$/', $telefono)) {
            throw new \Exception('Formato de teléfono inválido. Debe comenzar con 0 y tener 11 dígitos');
        }
        if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $telefono)) {
            throw new \Exception('El teléfono contiene caracteres no permitidos');
        }
        
        // Validar correo electrónico
        $correo = trim($_POST['correo']);
        if (empty($correo)) {
            throw new \Exception('El correo es obligatorio');
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Formato de correo inválido');
        }
        if (strlen($correo) > 255) {
            throw new \Exception('El correo no puede exceder 255 caracteres');
        }
        if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $correo)) {
            throw new \Exception('El correo contiene caracteres no permitidos');
        }
        $correo = filter_var($correo, FILTER_SANITIZE_EMAIL);
        
        $datos = [
            'cedula' => $cedula,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'correo' => $correo
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
            // Validar ID de pedido
            if (!isset($_POST['id_pedido']) || empty($_POST['id_pedido'])) {
                throw new \Exception('ID de pedido no proporcionado');
            }
            $id_pedido = validarId($_POST['id_pedido'], 'ID de pedido');
            
            // Validar estado del pedido (lista blanca de valores permitidos)
            if (!isset($_POST['estado_pedido']) || empty($_POST['estado_pedido'])) {
                throw new \Exception('El estado del pedido es obligatorio');
            }
            $estado = trim($_POST['estado_pedido']);
            
            // Lista blanca de estados permitidos (ajustar según los estados reales de tu sistema)
            $estadosPermitidos = ['1', '2', '3', '4', '5']; // Pendiente, Completado, Cancelado, etc.
            if (!in_array($estado, $estadosPermitidos)) {
                throw new \Exception('Estado de pedido inválido');
            }
            
            // Validar contra SQL injection
            if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $estado)) {
                throw new \Exception('El estado contiene caracteres no permitidos');
            }
            
            $datosVenta = [
                'id_pedido' => $id_pedido,
                'estado' => $estado
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
            // Validar ID de pedido
            if (!isset($_POST['eliminar']) || empty($_POST['eliminar'])) {
                throw new \Exception('ID de pedido no proporcionado');
            }
            $id_pedido = validarId($_POST['eliminar'], 'ID de pedido');
            
            $datosVenta = ['id_pedido' => $id_pedido];
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
        // Consultar datos
        $ventas = $salida->consultarVentas();
        $productos_lista = $salida->consultarProductos();
        $metodos_pago = $salida->consultarMetodosPago();
        
        // Asegurar que las variables estén definidas y sean arrays
        if (!isset($ventas) || !is_array($ventas)) {
            $ventas = [];
        }
        if (!isset($productos_lista) || !is_array($productos_lista)) {
            $productos_lista = [];
        }
        if (!isset($metodos_pago) || !is_array($metodos_pago)) {
            $metodos_pago = [];
        }
        
        // Validar estructura de productos_lista
        if (!empty($productos_lista)) {
            $productos_validos = [];
            foreach ($productos_lista as $producto) {
                if (is_array($producto) && isset($producto['id_producto']) && !empty($producto['id_producto'])) {
                    $productos_validos[] = $producto;
                }
            }
            $productos_lista = $productos_validos;
        }
    } catch (\Exception $e) {
        // Si hay error, inicializar arrays vacíos
        $ventas = [];
        $productos_lista = [];
        $metodos_pago = [];
    }
    
    // Asegurar que las variables estén definidas incluso si no entraron al try
    if (!isset($ventas)) {
        $ventas = [];
    }
    if (!isset($productos_lista)) {
        $productos_lista = [];
    }
    if (!isset($metodos_pago)) {
        $metodos_pago = [];
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
