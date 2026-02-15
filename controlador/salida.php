<?php

use LoveMakeup\Proyecto\Modelo\Salida;
use LoveMakeup\Proyecto\Modelo\Bitacora;
use LoveMakeup\Proyecto\Modelo\MetodoPago;

/* ============================================
   INICIALIZACIÓN Y VALIDACIÓN DE SESIÓN
   ============================================ */

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar que el usuario esté logueado
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
    // Verificar validez de la sesión
    if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
    } 
    
    // Validar rol de usuario - Clientes no pueden acceder
    if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }
    
    // Cargar sistema de permisos
    require_once 'permiso.php';
}
    
// Cargar modelos necesarios
require_once 'modelo/salida.php';
require_once 'modelo/metodopago.php';

// Instanciar modelos
$salida = new Salida();
$metodoPago = new MetodoPago();

/* ============================================
   FUNCIONES AUXILIARES
   ============================================ */

/**
 * Detecta si la solicitud es AJAX
 * @return bool True si es una solicitud AJAX
 */
function esAjax() {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

/**
 * Sanitiza datos de entrada para prevenir XSS
 * @param mixed $dato Dato a sanitizar
 * @return string|null Dato sanitizado o null si el dato es null
 */
function sanitizar($dato) {
    if (is_null($dato)) {
        return null;
    }
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

/* Valida y sanitiza nombres (solo letras, espacios y caracteres especiales permitidos)*/
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

/* Valida y sanitiza texto general (para referencias, etc.)*/
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

/* Valida que un ID sea un entero positivo válido*/

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

/* Valida que un número sea decimal positivo válido*/
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

/* Valida que un ID de producto existe y está activo en la base de datos*/
function validarIdProductoSalida($id_producto) {
    if (empty($id_producto) || !is_numeric($id_producto)) {
        return false;
    }
    
    $id_producto = intval($id_producto);
    if ($id_producto <= 0) {
        return false;
    }
    
    try {
        require_once 'modelo/producto.php';
        $producto = new \LoveMakeup\Proyecto\Modelo\Producto();
        $productos_validos = $producto->ProductosActivos();
        
        if (is_array($productos_validos)) {
            $ids_validos = array_column($productos_validos, 'id_producto');
            return in_array($id_producto, $ids_validos);
        }
        
        return false;
    } catch (\Exception $e) {
        return false;
    }
}

/* Valida que un ID de método de pago existe y está activo en la base de datos*/
function validarIdMetodoPago($id_metodopago) {
    if (empty($id_metodopago) || !is_numeric($id_metodopago)) {
        return false;
    }
    
    $id_metodopago = intval($id_metodopago);
    if ($id_metodopago <= 0) {
        return false;
    }
    
    try {
        require_once 'modelo/metodopago.php';
        $metodoPago = new \LoveMakeup\Proyecto\Modelo\MetodoPago();
        $metodos_validos = $metodoPago->consultar();
        
        if (is_array($metodos_validos)) {
            $ids_validos = array_column($metodos_validos, 'id_metodopago');
            return in_array($id_metodopago, $ids_validos);
        }
        
        return false;
    } catch (\Exception $e) {
        return false;
    }
}

/* Valida y sanitiza nombre de banco (lista blanca de caracteres)*/
function validarNombreBanco($banco, $campo = 'banco') {
    if (empty($banco)) {
        throw new \Exception("El campo {$campo} es obligatorio");
    }
    
    $banco = trim($banco);
    
    // Validar longitud
    if (strlen($banco) > 100) {
        throw new \Exception("El campo {$campo} no puede exceder 100 caracteres");
    }
    
    // Solo números, letras, espacios y algunos caracteres especiales (guiones, puntos, comas)
    if (!preg_match('/^[0-9A-Za-zÁáÉéÍíÓóÚúÑñÜü\s\-\.\,]+$/u', $banco)) {
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

/* ============================================
   OPERACIÓN: REGISTRAR NUEVA VENTA
   ============================================ */

    /* Procesa el registro de una nueva venta*/
if (isset($_POST['registrar'])) {
    // Limpiar cualquier output previo y asegurar respuesta JSON limpia
    if (ob_get_level() > 0) {
        ob_clean();
            }
    
    try {
        // ===== VALIDACIÓN DE SESIÓN =====
        if (empty($_SESSION["id"])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'respuesta' => 0,
                'error' => 'Debe iniciar sesión para realizar esta acción'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
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

        // ===== VALIDACIÓN DE VACÍOS Y SANITIZACIÓN =====
        $precio_total_raw = isset($_POST['precio_total']) ? sanitizar($_POST['precio_total']) : '0';
        $precio_total_bs_raw = isset($_POST['precio_total_bs']) ? sanitizar($_POST['precio_total_bs']) : '0';
        
        if (empty($precio_total_raw)) {
            throw new \Exception('El precio total es obligatorio');
        }
        
        $precio_total = validarDecimal($precio_total_raw, 'precio total', 0.01);
        $precio_total_bs = validarDecimal($precio_total_bs_raw, 'precio total en bolívares', 0);

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

                // ===== SANITIZACIÓN =====
                $cedula_raw = sanitizar($_POST['cedula_cliente']);
                $nombre_cliente_raw = sanitizar($_POST['nombre_cliente']);
                $apellido_cliente_raw = sanitizar($_POST['apellido_cliente']);
                $telefono_raw = sanitizar($_POST['telefono_cliente']);
                $correo_raw = sanitizar($_POST['correo_cliente']);
                
                // Validar cédula (solo números, 7-8 dígitos)
                $cedula = trim($cedula_raw);
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
                $datosCliente['nombre'] = validarYLimpiarNombre($nombre_cliente_raw, 'nombre', 100);

                // Validar apellido
                $datosCliente['apellido'] = validarYLimpiarNombre($apellido_cliente_raw, 'apellido', 100);

                // Validar teléfono (solo números, formato específico)
                $telefono = trim($telefono_raw);
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
                $correo = trim($correo_raw);
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
                // ===== SANITIZACIÓN =====
                $id_persona_raw = sanitizar($_POST['id_persona']);
                $id_persona = validarId($id_persona_raw, 'ID de cliente');
            }

        // Preparar datos de la venta
            $datosVenta = [
                'id_persona' => $id_persona,
                'precio_total' => $precio_total,
                'precio_total_bs' => $precio_total_bs,
            'detalles' => [],
            'metodos_pago' => []
            ];

        /* Procesar detalles de productos con validaciones robustas*/
            $totalCantidadProductos = 0;
            // Validar que los arrays tengan la misma longitud
            if (count($_POST['id_producto']) !== count($_POST['cantidad'] ?? []) || 
                count($_POST['id_producto']) !== count($_POST['precio_unitario'] ?? [])) {
                throw new \Exception('Los datos de productos están incompletos');
            }
            
            for ($i = 0; $i < count($_POST['id_producto']); $i++) {
                // ===== VALIDACIÓN DE VACÍOS =====
                if (empty($_POST['id_producto'][$i])) {
                    continue; // Saltar productos vacíos
                }
                
                if (empty($_POST['cantidad'][$i]) || $_POST['cantidad'][$i] <= 0) {
                    throw new \Exception('La cantidad en la fila ' . ($i + 1) . ' es obligatoria y debe ser mayor a cero');
                }
                
                if (empty($_POST['precio_unitario'][$i])) {
                    throw new \Exception('El precio unitario en la fila ' . ($i + 1) . ' es obligatorio');
                }
                
                // ===== SANITIZACIÓN =====
                $id_producto_raw = sanitizar($_POST['id_producto'][$i]);
                $cantidad_raw = sanitizar($_POST['cantidad'][$i]);
                $precio_unitario_raw = sanitizar($_POST['precio_unitario'][$i] ?? '0');
                
                // Validar ID de producto - verificar que existe y está activo
                $id_producto = validarId($id_producto_raw, 'ID de producto en fila ' . ($i + 1));
                
                // Validar que el ID no haya sido manipulado
                if (!validarIdProductoSalida($id_producto)) {
                    throw new \Exception('El producto en la fila ' . ($i + 1) . ' no es válido o no está disponible.');
                }
                
                // Validar cantidad (debe ser entero positivo)
                $cantidad = filter_var($cantidad_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]);
                if ($cantidad === false) {
                    throw new \Exception('Cantidad inválida en la fila ' . ($i + 1) . '. Debe ser un número entero entre 1 y 9999');
                }
                
                // Validar precio unitario
                $precio_unitario = validarDecimal($precio_unitario_raw, 'precio unitario en fila ' . ($i + 1), 0.01);

                        $datosVenta['detalles'][] = [
                        'id_producto' => $id_producto,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio_unitario
                    ];
                    $totalCantidadProductos += $cantidad;
                }

        if (empty($datosVenta['detalles'])) {
            throw new \Exception('Debe seleccionar al menos un producto válido');
            }

        // Validar cantidad total de productos
        if ($totalCantidadProductos <= 0) {
            throw new \Exception('La cantidad total de productos debe ser mayor a 0');
        }

        /* Procesar métodos de pago con validaciones robustas*/
            if (isset($_POST['id_metodopago']) && is_array($_POST['id_metodopago'])) {
                // Validar longitud de arrays para prevenir DoS
                if (count($_POST['id_metodopago']) > 10) {
                    throw new \Exception('No se pueden procesar más de 10 métodos de pago a la vez');
                }
                
                $totalMetodosPago = 0;
                $metodosPagoUnicos = [];

                for ($i = 0; $i < count($_POST['id_metodopago']); $i++) {
                    // Validar ID de método de pago - verificar que existe y está activo
                    $idMetodo = validarId($_POST['id_metodopago'][$i], 'ID de método de pago en fila ' . ($i + 1));
                    
                    // Validar que el ID no haya sido manipulado
                    if (!validarIdMetodoPago($idMetodo)) {
                        throw new \Exception('El método de pago en la fila ' . ($i + 1) . ' no es válido o no está disponible.');
                    }
                    
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

                        // Obtener nombre del método de pago usando el método del modelo
                        $nombreMetodo = $salida->obtenerNombreMetodoPago($idMetodo);

                        // Procesar detalles según el método
                        switch($nombreMetodo) {
                            case 'Efectivo Bs':
                                if (isset($_POST['monto_efectivo_bs']) && $_POST['monto_efectivo_bs'] > 0) {
                                    $metodo['monto_bs'] = floatval($_POST['monto_efectivo_bs']);
                                }
                                break;
                            case 'Pago Movil':
                                // Leer desde array indexado si existe, sino desde campo único (compatibilidad hacia atrás)
                                $montoPmBs = 0;
                                if (isset($_POST['monto_pm_bs']) && is_array($_POST['monto_pm_bs']) && isset($_POST['monto_pm_bs'][$i])) {
                                    $montoPmBs = floatval($_POST['monto_pm_bs'][$i]);
                                } elseif (isset($_POST['monto_pm_bs']) && !is_array($_POST['monto_pm_bs'])) {
                                    $montoPmBs = floatval($_POST['monto_pm_bs']);
                                }
                                if ($montoPmBs > 0) {
                                    $metodo['monto_bs'] = $montoPmBs;
                                }
                                
                                // Validaciones específicas de Pago Móvil - leer desde array indexado
                                $bancoEmisor = '';
                                if (isset($_POST['banco_emisor_pm']) && is_array($_POST['banco_emisor_pm']) && isset($_POST['banco_emisor_pm'][$i])) {
                                    $bancoEmisor = $_POST['banco_emisor_pm'][$i];
                                } elseif (isset($_POST['banco_emisor_pm']) && !is_array($_POST['banco_emisor_pm'])) {
                                    $bancoEmisor = $_POST['banco_emisor_pm'];
                                }
                                
                                if (empty($bancoEmisor)) {
                                    throw new \Exception('Seleccione un banco emisor para Pago Móvil en el método de pago #' . ($i + 1));
                                }
                                $metodo['banco_emisor'] = validarNombreBanco($bancoEmisor, 'banco emisor');
                                
                                $bancoReceptor = '';
                                if (isset($_POST['banco_receptor_pm']) && is_array($_POST['banco_receptor_pm']) && isset($_POST['banco_receptor_pm'][$i])) {
                                    $bancoReceptor = $_POST['banco_receptor_pm'][$i];
                                } elseif (isset($_POST['banco_receptor_pm']) && !is_array($_POST['banco_receptor_pm'])) {
                                    $bancoReceptor = $_POST['banco_receptor_pm'];
                                }
                                
                                if (empty($bancoReceptor)) {
                                    throw new \Exception('Seleccione un banco receptor para Pago Móvil en el método de pago #' . ($i + 1));
                                }
                                $metodo['banco_receptor'] = validarNombreBanco($bancoReceptor, 'banco receptor');
                                
                                $referenciaPM = '';
                                if (isset($_POST['referencia_pm']) && is_array($_POST['referencia_pm']) && isset($_POST['referencia_pm'][$i])) {
                                    $referenciaPM = trim($_POST['referencia_pm'][$i]);
                                } elseif (isset($_POST['referencia_pm']) && !is_array($_POST['referencia_pm'])) {
                                    $referenciaPM = trim($_POST['referencia_pm']);
                                }
                                
                                if (empty($referenciaPM)) {
                                    throw new \Exception('La referencia de Pago Móvil es obligatoria en el método de pago #' . ($i + 1));
                                }
                                // Validar formato (solo números, 4-6 dígitos)
                                if (!preg_match('/^\d{4,6}$/', $referenciaPM)) {
                                    throw new \Exception('La referencia de Pago Móvil debe tener entre 4 y 6 dígitos numéricos en el método de pago #' . ($i + 1));
                                }
                                // Validar contra SQL injection
                                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $referenciaPM)) {
                                    throw new \Exception('La referencia contiene caracteres no permitidos en el método de pago #' . ($i + 1));
                                }
                                $metodo['referencia'] = $referenciaPM;
                                
                                $telefonoPM = '';
                                if (isset($_POST['telefono_emisor_pm']) && is_array($_POST['telefono_emisor_pm']) && isset($_POST['telefono_emisor_pm'][$i])) {
                                    $telefonoPM = trim($_POST['telefono_emisor_pm'][$i]);
                                } elseif (isset($_POST['telefono_emisor_pm']) && !is_array($_POST['telefono_emisor_pm'])) {
                                    $telefonoPM = trim($_POST['telefono_emisor_pm']);
                                }
                                
                                if (empty($telefonoPM)) {
                                    throw new \Exception('El teléfono emisor de Pago Móvil es obligatorio en el método de pago #' . ($i + 1));
                                }
                                // Validar formato (solo números, 11 dígitos)
                                if (!preg_match('/^\d{11}$/', $telefonoPM)) {
                                    throw new \Exception('El teléfono emisor debe tener 11 dígitos numéricos en el método de pago #' . ($i + 1));
                                }
                                // Validar contra SQL injection
                                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $telefonoPM)) {
                                    throw new \Exception('El teléfono contiene caracteres no permitidos en el método de pago #' . ($i + 1));
                                }
                                $metodo['telefono_emisor'] = $telefonoPM;
                                break;
                            case 'Punto de Venta':
                                // Leer desde array indexado si existe
                                $montoPvBs = 0;
                                if (isset($_POST['monto_pv_bs']) && is_array($_POST['monto_pv_bs']) && isset($_POST['monto_pv_bs'][$i])) {
                                    $montoPvBs = floatval($_POST['monto_pv_bs'][$i]);
                                } elseif (isset($_POST['monto_pv_bs']) && !is_array($_POST['monto_pv_bs'])) {
                                    $montoPvBs = floatval($_POST['monto_pv_bs']);
                                }
                                if ($montoPvBs > 0) {
                                    $metodo['monto_bs'] = $montoPvBs;
                                }
                                
                                // Validación de referencia para Punto de Venta - leer desde array indexado
                                $referenciaPV = '';
                                if (isset($_POST['referencia_pv']) && is_array($_POST['referencia_pv']) && isset($_POST['referencia_pv'][$i])) {
                                    $referenciaPV = trim($_POST['referencia_pv'][$i]);
                                } elseif (isset($_POST['referencia_pv']) && !is_array($_POST['referencia_pv'])) {
                                    $referenciaPV = trim($_POST['referencia_pv']);
                                }
                                
                                if (empty($referenciaPV)) {
                                    throw new \Exception('La referencia de Punto de Venta es obligatoria en el método de pago #' . ($i + 1));
                                }
                                // Validar formato (solo números, 4-6 dígitos)
                                if (!preg_match('/^\d{4,6}$/', $referenciaPV)) {
                                    throw new \Exception('La referencia de Punto de Venta debe tener entre 4 y 6 dígitos numéricos en el método de pago #' . ($i + 1));
                                }
                                // Validar contra SQL injection
                                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $referenciaPV)) {
                                    throw new \Exception('La referencia contiene caracteres no permitidos en el método de pago #' . ($i + 1));
                                }
                                $metodo['referencia'] = $referenciaPV;
                                break;
                            case 'Transferencia Bancaria':
                                // Leer desde array indexado si existe
                                $montoTbBs = 0;
                                if (isset($_POST['monto_tb_bs']) && is_array($_POST['monto_tb_bs']) && isset($_POST['monto_tb_bs'][$i])) {
                                    $montoTbBs = floatval($_POST['monto_tb_bs'][$i]);
                                } elseif (isset($_POST['monto_tb_bs']) && !is_array($_POST['monto_tb_bs'])) {
                                    $montoTbBs = floatval($_POST['monto_tb_bs']);
                                }
                                if ($montoTbBs > 0) {
                                    $metodo['monto_bs'] = $montoTbBs;
                                }
                                
                                // Validación de referencia para Transferencia Bancaria - leer desde array indexado
                                $referenciaTB = '';
                                if (isset($_POST['referencia_tb']) && is_array($_POST['referencia_tb']) && isset($_POST['referencia_tb'][$i])) {
                                    $referenciaTB = trim($_POST['referencia_tb'][$i]);
                                } elseif (isset($_POST['referencia_tb']) && !is_array($_POST['referencia_tb'])) {
                                    $referenciaTB = trim($_POST['referencia_tb']);
                                }
                                
                                if (empty($referenciaTB)) {
                                    throw new \Exception('La referencia de Transferencia Bancaria es obligatoria en el método de pago #' . ($i + 1));
                                }
                                // Validar formato (solo números, 4-6 dígitos)
                                if (!preg_match('/^\d{4,6}$/', $referenciaTB)) {
                                    throw new \Exception('La referencia de Transferencia Bancaria debe tener entre 4 y 6 dígitos numéricos en el método de pago #' . ($i + 1));
                                }
                                // Validar contra SQL injection
                                if (preg_match('/[;\'\"\-\-]|(\/\*)|(\*\/)|(xp_)|(sp_)|(exec)|(union)|(select)|(insert)|(update)|(delete)|(drop)|(create)|(alter)/i', $referenciaTB)) {
                                    throw new \Exception('La referencia contiene caracteres no permitidos en el método de pago #' . ($i + 1));
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

        /* Registrar la venta*/
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

/* ============================================
   OPERACIÓN: BUSCAR CLIENTE (AJAX)
   ============================================ */

/* Procesa la búsqueda de un cliente por cédula. Retorna información del cliente si existe*/
 
if (isset($_POST['buscar_cliente'])) {
    try {
        // ===== VALIDACIÓN DE SESIÓN =====
        if (empty($_SESSION["id"])) {
            header('Content-Type: application/json');
            echo json_encode([
                'respuesta' => 0,
                'error' => 'Debe iniciar sesión para realizar esta acción'
            ]);
            exit;
        }
        
        // Validar que la cédula esté presente
        if (!isset($_POST['cedula']) || empty($_POST['cedula'])) {
            throw new \Exception('La cédula es obligatoria');
        }
        
        // ===== SANITIZACIÓN =====
        $cedula_raw = sanitizar($_POST['cedula']);
        
        // Validar formato de cédula (solo números, 7-8 dígitos)
        $cedula = trim($cedula_raw);
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

/* ============================================
   OPERACIÓN: REGISTRAR NUEVO CLIENTE (AJAX)
   ============================================ */

/* Procesa el registro de un nuevo cliente*/
if (isset($_POST['registrar_cliente'])) {
    try {
        // ===== VALIDACIÓN DE SESIÓN =====
        if (empty($_SESSION["id"])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Debe iniciar sesión para realizar esta acción'
            ]);
            exit;
        }
        
        // Validar que todos los campos estén presentes
        if (!isset($_POST['cedula']) || !isset($_POST['nombre']) || 
            !isset($_POST['apellido']) || !isset($_POST['telefono']) || 
            !isset($_POST['correo'])) {
            throw new \Exception('Todos los campos del cliente son obligatorios');
        }
        
        // ===== SANITIZACIÓN =====
        $cedula_raw = sanitizar($_POST['cedula']);
        $nombre_raw = sanitizar($_POST['nombre']);
        $apellido_raw = sanitizar($_POST['apellido']);
        $telefono_raw = sanitizar($_POST['telefono']);
        $correo_raw = sanitizar($_POST['correo']);
        
        // Validar cédula (solo números, 7-8 dígitos)
        $cedula = trim($cedula_raw);
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
        $nombre = validarYLimpiarNombre($nombre_raw, 'nombre', 100);
        
        // Validar apellido
        $apellido = validarYLimpiarNombre($apellido_raw, 'apellido', 100);
        
        // Validar teléfono (solo números, formato específico)
        $telefono = trim($telefono_raw);
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
        $correo = trim($correo_raw);
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

    /* ============================================
       OPERACIÓN: ACTUALIZAR ESTADO DE VENTA
       ============================================ */

        /* Procesa la actualización del estado de una venta*/
    if (isset($_POST['actualizar'])) {
        try {
            // ===== VALIDACIÓN DE SESIÓN =====
            if (empty($_SESSION["id"])) {
                if (esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'respuesta' => 0,
                        'error' => 'Debe iniciar sesión para realizar esta acción'
                    ]);
                    exit;
                } else {
                    header("location:?pagina=login");
                    exit;
                }
            }
            
            // ===== VALIDACIÓN DE VACÍOS =====
            if (!isset($_POST['id_pedido']) || empty($_POST['id_pedido'])) {
                throw new \Exception('ID de pedido no proporcionado');
            }
            
            if (!isset($_POST['estado_pedido']) || empty($_POST['estado_pedido'])) {
                throw new \Exception('El estado del pedido es obligatorio');
            }
            
            // ===== SANITIZACIÓN =====
            $id_pedido_raw = sanitizar($_POST['id_pedido']);
            $estado_raw = sanitizar($_POST['estado_pedido']);
            
            $id_pedido = validarId($id_pedido_raw, 'ID de pedido');
            $estado = trim($estado_raw);
            
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

    /* ============================================
       OPERACIÓN: ELIMINAR VENTA
       ============================================ */

    /**
     * Procesa la eliminación de una venta
     * Valida: sesión, ID de pedido válido
     */
    if (isset($_POST['eliminar'])) {
        try {
            // ===== VALIDACIÓN DE SESIÓN =====
            if (empty($_SESSION["id"])) {
                if (esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'respuesta' => 0,
                        'error' => 'Debe iniciar sesión para realizar esta acción'
                    ]);
                    exit;
                } else {
                    header("location:?pagina=login");
                    exit;
                }
            }
            
            // ===== VALIDACIÓN DE VACÍOS =====
            if (!isset($_POST['eliminar']) || empty($_POST['eliminar'])) {
                throw new \Exception('ID de pedido no proporcionado');
            }
            
            // ===== SANITIZACIÓN =====
            $id_pedido_raw = sanitizar($_POST['eliminar']);
            $id_pedido = validarId($id_pedido_raw, 'ID de pedido');
            
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

/* ============================================
   GENERACIÓN DE TOKEN CSRF
   ============================================ */

// Generar o verificar el token CSRF para protección contra ataques CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ============================================
   CONSULTA DE DATOS PARA LA VISTA
   ============================================ */

// Solo consultar datos para la vista si NO es una petición POST/AJAX
if (!$esAjaxRequest && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Consultar todas las ventas para mostrar en la lista
        $ventas = $salida->consultarVentas();
        
        // Consultar productos activos disponibles para venta
        $productos_lista = $salida->consultarProductos();
        
        // Consultar métodos de pago disponibles (usando el modelo MetodoPago)
        $metodos_pago = $metodoPago->consultar();
        
        // Ordenar métodos de pago alfabéticamente por nombre
        if (is_array($metodos_pago) && !empty($metodos_pago)) {
            usort($metodos_pago, function($a, $b) {
                return strcmp($a['nombre'], $b['nombre']);
            });
        }
        
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

        /* ============================================
           REGISTRO EN BITÁCORA
           ============================================ */
        
        // Registrar acceso al módulo en la bitácora
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

        /* ============================================
           CARGA DE VISTA
           ============================================ */
        
        // Verificar permisos y cargar la vista correspondiente
        if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(4, 1)) {
            $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'salida';
            require_once 'vista/salida.php';
        } else {
            // Usuario sin permisos - mostrar página de privilegios insuficientes
            require_once 'vista/seguridad/privilegio.php';
        }
    }
