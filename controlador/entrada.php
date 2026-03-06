<?php

use LoveMakeup\Proyecto\Modelo\Entrada;
use LoveMakeup\Proyecto\Modelo\Bitacora;
use LoveMakeup\Proyecto\Config\Conexion;

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

// Instanciar modelo de entrada
$entrada = new Entrada();

/* ============================================
   FUNCIONES AUXILIARES
   ============================================ */

/* Detecta si la solicitud es AJAx*/
function esAjax() {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

/* Sanitiza datos de entrada para prevenir XSS*/
function sanitizar($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}


/* ============================================
   OPERACIÓN: REGISTRAR NUEVA COMPRA
   ============================================ */

/* Procesa el registro de una nueva compra. Valida: sesión, campos vacíos, formato de datos, claves foráneas*/
if (isset($_POST['registrar_compra'])) {
    // ===== VALIDACIÓN DE SESIÓN =====
    if (empty($_SESSION["id"])) {
        $mensaje_error = 'Debe iniciar sesión para realizar esta acción.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            header("location:?pagina=login");
            exit;
        }
    }
    
    // ===== VALIDACIÓN DE VACÍOS Y PRESENCIA DE CAMPOS =====
    if (empty($_POST['fecha_entrada']) || empty($_POST['id_proveedor']) || 
        !isset($_POST['id_producto']) || !is_array($_POST['id_producto'])) {
        $mensaje_error = 'Datos incompletos. Por favor, complete todos los campos requeridos.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== SANITIZACIÓN DE DATOS =====
    $fecha_entrada = sanitizar($_POST['fecha_entrada']);
    $id_proveedor_raw = sanitizar($_POST['id_proveedor']);
    
    // ===== VALIDACIÓN DE LONGITUD Y FORMATO - FECHA =====
    if (empty($fecha_entrada)) {
        $mensaje_error = 'La fecha de entrada es obligatoria.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Normalizar fecha: extraer solo la parte de fecha (YYYY-MM-DD) si viene con formato datetime
    $fecha_entrada = trim($fecha_entrada);
    if (strlen($fecha_entrada) > 10 && strpos($fecha_entrada, ' ') !== false) {
        // Si tiene hora, extraer solo la fecha (primeros 10 caracteres)
        $fecha_entrada = substr($fecha_entrada, 0, 10);
    }
    
    // Validar formato de fecha (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_entrada)) {
        $mensaje_error = 'Formato de fecha inválido. Use el formato YYYY-MM-DD.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Validar que la fecha esté dentro del rango permitido (hoy y 2 días anteriores)
    $fecha_hoy = date('Y-m-d');
    $fecha_dos_dias_atras = date('Y-m-d', strtotime('-2 days'));
    
    if ($fecha_entrada < $fecha_dos_dias_atras || $fecha_entrada > $fecha_hoy) {
        $mensaje_error = 'La fecha de entrada solo puede ser el día de hoy o los dos días anteriores.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== VALIDACIÓN DE LONGITUD Y FORMATO - ID PROVEEDOR =====
    if (empty($id_proveedor_raw) || !is_numeric($id_proveedor_raw)) {
        $mensaje_error = 'El ID de proveedor es obligatorio y debe ser un número válido.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    $id_proveedor = intval($id_proveedor_raw);
    if ($id_proveedor <= 0 || $id_proveedor > 2147483647) { // Validar rango válido de entero
        $mensaje_error = 'ID de proveedor inválido.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== VALIDACIÓN DE CLAVE FORÁNEA - PROVEEDOR =====
    if (!$entrada->validarIdProveedor($id_proveedor)) {
        $mensaje_error = 'Proveedor inválido o no autorizado.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== VALIDACIÓN Y SANITIZACIÓN DE PRODUCTOS =====
    // Validar que los arrays tengan la misma longitud
    $count_productos = count($_POST['id_producto']);
    if (!isset($_POST['cantidad']) || !is_array($_POST['cantidad']) || count($_POST['cantidad']) != $count_productos ||
        !isset($_POST['precio_unitario']) || !is_array($_POST['precio_unitario']) || count($_POST['precio_unitario']) != $count_productos ||
        !isset($_POST['precio_total']) || !is_array($_POST['precio_total']) || count($_POST['precio_total']) != $count_productos) {
        $mensaje_error = 'Los datos de productos están incompletos o inconsistentes.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Validar que los IDs de productos sean válidos (no manipulados)
    if (!$entrada->validarIdsProductos($_POST['id_producto'])) {
        $mensaje_error = 'Uno o más productos seleccionados no son válidos o no están disponibles.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Procesar productos con validaciones completas
    $productos = [];
    for ($i = 0; $i < $count_productos; $i++) {
        // ===== VALIDACIÓN DE VACÍOS =====
        if (empty($_POST['id_producto'][$i])) {
            continue; // Saltar productos vacíos
        }
        
        if (empty($_POST['cantidad'][$i]) || $_POST['cantidad'][$i] <= 0) {
            $mensaje_error = 'La cantidad en la fila ' . ($i + 1) . ' es obligatoria y debe ser mayor a cero.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        if (empty($_POST['precio_unitario'][$i])) {
            $mensaje_error = 'El precio unitario en la fila ' . ($i + 1) . ' es obligatorio.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        if (empty($_POST['precio_total'][$i])) {
            $mensaje_error = 'El precio total en la fila ' . ($i + 1) . ' es obligatorio.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== SANITIZACIÓN =====
        $id_producto_raw = sanitizar($_POST['id_producto'][$i]);
        $cantidad_raw = sanitizar($_POST['cantidad'][$i]);
        $precio_unitario_raw = sanitizar($_POST['precio_unitario'][$i]);
        $precio_total_raw = sanitizar($_POST['precio_total'][$i]);
        
        // ===== VALIDACIÓN DE FORMATO Y LONGITUD - ID PRODUCTO =====
        if (!is_numeric($id_producto_raw)) {
            $mensaje_error = 'El ID de producto en la fila ' . ($i + 1) . ' debe ser un número válido.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $id_producto = intval($id_producto_raw);
        if ($id_producto <= 0 || $id_producto > 2147483647) {
            $mensaje_error = 'ID de producto inválido en la fila ' . ($i + 1) . '.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== VALIDACIÓN DE CLAVE FORÁNEA - PRODUCTO =====
        if (!$entrada->validarIdProducto($id_producto)) {
            $mensaje_error = 'El producto en la posición ' . ($i + 1) . ' no es válido o no está disponible.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== VALIDACIÓN DE FORMATO - CANTIDAD =====
        if (!is_numeric($cantidad_raw)) {
            $mensaje_error = 'La cantidad en la fila ' . ($i + 1) . ' debe ser un número válido.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $cantidad = intval($cantidad_raw);
        if ($cantidad <= 0 || $cantidad > 999999) { // Límite razonable
            $mensaje_error = 'La cantidad en la fila ' . ($i + 1) . ' debe estar entre 1 y 999999.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== VALIDACIÓN DE FORMATO - PRECIO UNITARIO =====
        if (!is_numeric($precio_unitario_raw)) {
            $mensaje_error = 'El precio unitario en la fila ' . ($i + 1) . ' debe ser un número válido.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $precio_unitario = floatval($precio_unitario_raw);
        if ($precio_unitario <= 0 || $precio_unitario > 999999.99) {
            $mensaje_error = 'El precio unitario en la fila ' . ($i + 1) . ' debe ser mayor a 0 y menor a 999999.99.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== VALIDACIÓN DE FORMATO - PRECIO TOTAL =====
        if (!is_numeric($precio_total_raw)) {
            $mensaje_error = 'El precio total en la fila ' . ($i + 1) . ' debe ser un número válido.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $precio_total = floatval($precio_total_raw);
        if ($precio_total <= 0 || $precio_total > 999999999.99) {
            $mensaje_error = 'El precio total en la fila ' . ($i + 1) . ' debe ser mayor a 0 y menor a 999999999.99.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // Verificar coherencia: precio_total ≈ cantidad * precio_unitario (con tolerancia de redondeo)
        $precio_calculado = round($cantidad * $precio_unitario, 2);
        $precio_total_redondeado = round($precio_total, 2);
        if (abs($precio_calculado - $precio_total_redondeado) > 0.01) {
            $mensaje_error = 'El precio total en la fila ' . ($i + 1) . ' no coincide con la cantidad por el precio unitario.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $productos[] = array(
            'id_producto' => $id_producto,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'precio_total' => $precio_total
        );
    }
    
    // Validar que haya al menos un producto válido
    if (count($productos) == 0) {
        $mensaje_error = 'Debe agregar al menos un producto con cantidad mayor a cero.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }

    $datosCompra = [
        'operacion' => 'registrar',
        'datos' => [
            'fecha_entrada' => $fecha_entrada,
            'id_proveedor' => $id_proveedor,
            'productos' => $productos
        ]
    ];

    $resultadoRegistro = $entrada->procesarCompra(json_encode($datosCompra));

    if ($resultadoRegistro['respuesta'] == 1) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Registro de compra',
            'descripcion' => 'Se registró la compra ID: ' . $resultadoRegistro['id_compra']
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'entrada', $bitacora);
    }

    if (esAjax()) {
        header('Content-Type: application/json');
        echo json_encode($resultadoRegistro);
        exit;
    } else {
        $_SESSION['message'] = [
            'title' => ($resultadoRegistro['respuesta'] == 1) ? '¡Éxito!' : 'Error',
            'text' => $resultadoRegistro['mensaje'],
            'icon' => ($resultadoRegistro['respuesta'] == 1) ? 'success' : 'error'
        ];
        
        header("Location: ?pagina=entrada");
        exit;
    }
}

/* ============================================
   OPERACIÓN: MODIFICAR COMPRA EXISTENTE
   ============================================ */

/* Procesa la modificación de una compra existente. Valida: sesión, ID de compra, campos vacíos, formato de datos, claves foráneas*/
if (isset($_POST['modificar_compra'])) {
    // ===== VALIDACIÓN DE SESIÓN =====
    if (empty($_SESSION["id"])) {
        $mensaje_error = 'Debe iniciar sesión para realizar esta acción.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            header("location:?pagina=login");
            exit;
        }
    }
    
    // ===== VALIDACIÓN DE VACÍOS Y PRESENCIA DE CAMPOS =====
    if (empty($_POST['id_compra']) || empty($_POST['fecha_entrada']) || empty($_POST['id_proveedor']) || 
        !isset($_POST['id_producto']) || !is_array($_POST['id_producto'])) {
        $mensaje_error = 'Datos incompletos. Por favor, complete todos los campos requeridos.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== SANITIZACIÓN DE DATOS =====
    $id_compra_raw = sanitizar($_POST['id_compra']);
    $fecha_entrada = sanitizar($_POST['fecha_entrada']);
    $id_proveedor_raw = sanitizar($_POST['id_proveedor']);
    
    // ===== VALIDACIÓN DE LONGITUD Y FORMATO - ID COMPRA =====
    if (empty($id_compra_raw) || !is_numeric($id_compra_raw)) {
        $mensaje_error = 'El ID de compra es obligatorio y debe ser un número válido.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    $id_compra = intval($id_compra_raw);
    if ($id_compra <= 0 || $id_compra > 2147483647) {
        $mensaje_error = 'ID de compra inválido.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== VALIDACIÓN DE LONGITUD Y FORMATO - FECHA =====
    if (empty($fecha_entrada)) {
        $mensaje_error = 'La fecha de entrada es obligatoria.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Normalizar fecha: extraer solo la parte de fecha (YYYY-MM-DD) si viene con formato datetime
    $fecha_entrada = trim($fecha_entrada);
    if (strlen($fecha_entrada) > 10 && strpos($fecha_entrada, ' ') !== false) {
        // Si tiene hora, extraer solo la fecha (primeros 10 caracteres)
        $fecha_entrada = substr($fecha_entrada, 0, 10);
    }
    
    // Validar formato de fecha (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_entrada)) {
        $mensaje_error = 'Formato de fecha inválido. Use el formato YYYY-MM-DD.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Validar que la fecha esté dentro del rango permitido (hoy y 2 días anteriores)
    $fecha_hoy = date('Y-m-d');
    $fecha_dos_dias_atras = date('Y-m-d', strtotime('-2 days'));
    
    if ($fecha_entrada < $fecha_dos_dias_atras || $fecha_entrada > $fecha_hoy) {
        $mensaje_error = 'La fecha de entrada solo puede ser el día de hoy o los dos días anteriores.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== VALIDACIÓN DE LONGITUD Y FORMATO - ID PROVEEDOR =====
    if (empty($id_proveedor_raw) || !is_numeric($id_proveedor_raw)) {
        $mensaje_error = 'El ID de proveedor es obligatorio y debe ser un número válido.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    $id_proveedor = intval($id_proveedor_raw);
    if ($id_proveedor <= 0 || $id_proveedor > 2147483647) {
        $mensaje_error = 'ID de proveedor inválido.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== VALIDACIÓN DE CLAVE FORÁNEA - PROVEEDOR =====
    if (!$entrada->validarIdProveedor($id_proveedor)) {
        $mensaje_error = 'Proveedor inválido o no autorizado.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Validar que los IDs de productos sean válidos (no manipulados)
    if (!$entrada->validarIdsProductos($_POST['id_producto'])) {
        $mensaje_error = 'Uno o más productos seleccionados no son válidos o no están disponibles.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Validar que los arrays tengan la misma longitud
    $count_productos = count($_POST['id_producto']);
    if (!isset($_POST['cantidad']) || !is_array($_POST['cantidad']) || count($_POST['cantidad']) != $count_productos ||
        !isset($_POST['precio_unitario']) || !is_array($_POST['precio_unitario']) || count($_POST['precio_unitario']) != $count_productos ||
        !isset($_POST['precio_total']) || !is_array($_POST['precio_total']) || count($_POST['precio_total']) != $count_productos) {
        $mensaje_error = 'Los datos de productos están incompletos o inconsistentes.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // Procesar productos con validaciones completas (mismo proceso que en registrar_compra)
    $productos = [];
    for ($i = 0; $i < $count_productos; $i++) {
        // ===== VALIDACIÓN DE VACÍOS =====
        if (empty($_POST['id_producto'][$i])) {
            continue; // Saltar productos vacíos
        }
        
        if (empty($_POST['cantidad'][$i]) || $_POST['cantidad'][$i] <= 0) {
            $mensaje_error = 'La cantidad en la fila ' . ($i + 1) . ' es obligatoria y debe ser mayor a cero.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        if (empty($_POST['precio_unitario'][$i])) {
            $mensaje_error = 'El precio unitario en la fila ' . ($i + 1) . ' es obligatorio.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        if (empty($_POST['precio_total'][$i])) {
            $mensaje_error = 'El precio total en la fila ' . ($i + 1) . ' es obligatorio.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== SANITIZACIÓN =====
        $id_producto_raw = sanitizar($_POST['id_producto'][$i]);
        $cantidad_raw = sanitizar($_POST['cantidad'][$i]);
        $precio_unitario_raw = sanitizar($_POST['precio_unitario'][$i]);
        $precio_total_raw = sanitizar($_POST['precio_total'][$i]);
        
        // ===== VALIDACIÓN DE FORMATO Y LONGITUD - ID PRODUCTO =====
        if (!is_numeric($id_producto_raw)) {
            $mensaje_error = 'El ID de producto en la fila ' . ($i + 1) . ' debe ser un número válido.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $id_producto = intval($id_producto_raw);
        if ($id_producto <= 0 || $id_producto > 2147483647) {
            $mensaje_error = 'ID de producto inválido en la fila ' . ($i + 1) . '.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== VALIDACIÓN DE CLAVE FORÁNEA - PRODUCTO =====
        if (!$entrada->validarIdProducto($id_producto)) {
            $mensaje_error = 'El producto en la posición ' . ($i + 1) . ' no es válido o no está disponible.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== VALIDACIÓN DE FORMATO - CANTIDAD =====
        if (!is_numeric($cantidad_raw)) {
            $mensaje_error = 'La cantidad en la fila ' . ($i + 1) . ' debe ser un número válido.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $cantidad = intval($cantidad_raw);
        if ($cantidad <= 0 || $cantidad > 999999) {
            $mensaje_error = 'La cantidad en la fila ' . ($i + 1) . ' debe estar entre 1 y 999999.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== VALIDACIÓN DE FORMATO - PRECIO UNITARIO =====
        if (!is_numeric($precio_unitario_raw)) {
            $mensaje_error = 'El precio unitario en la fila ' . ($i + 1) . ' debe ser un número válido.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $precio_unitario = floatval($precio_unitario_raw);
        if ($precio_unitario <= 0 || $precio_unitario > 999999.99) {
            $mensaje_error = 'El precio unitario en la fila ' . ($i + 1) . ' debe ser mayor a 0 y menor a 999999.99.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // ===== VALIDACIÓN DE FORMATO - PRECIO TOTAL =====
        if (!is_numeric($precio_total_raw)) {
            $mensaje_error = 'El precio total en la fila ' . ($i + 1) . ' debe ser un número válido.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $precio_total = floatval($precio_total_raw);
        if ($precio_total <= 0 || $precio_total > 999999999.99) {
            $mensaje_error = 'El precio total en la fila ' . ($i + 1) . ' debe ser mayor a 0 y menor a 999999999.99.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        // Verificar coherencia: precio_total ≈ cantidad * precio_unitario (con tolerancia de redondeo)
        $precio_calculado = round($cantidad * $precio_unitario, 2);
        $precio_total_redondeado = round($precio_total, 2);
        if (abs($precio_calculado - $precio_total_redondeado) > 0.01) {
            $mensaje_error = 'El precio total en la fila ' . ($i + 1) . ' no coincide con la cantidad por el precio unitario.';
            if (esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
                exit;
            } else {
                $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
                header("Location: ?pagina=entrada");
                exit;
            }
        }
        
        $productos[] = array(
            'id_producto' => $id_producto,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'precio_total' => $precio_total
        );
    }
    
    // Validar que haya al menos un producto válido
    if (count($productos) == 0) {
        $mensaje_error = 'Debe agregar al menos un producto con cantidad mayor a cero.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }

    $datosCompra = [
        'operacion' => 'actualizar',
        'datos' => [
            'id_compra' => $id_compra,
            'fecha_entrada' => $fecha_entrada,
            'id_proveedor' => $id_proveedor,
            'productos' => $productos
        ]
    ];

    $resultado = $entrada->procesarCompra(json_encode($datosCompra));

    if ($resultado['respuesta'] == 1) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Modificación de compra',
            'descripcion' => 'Se modificó la compra ID: ' . $datosCompra['datos']['id_compra']
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'entrada', $bitacora);
    }

    if (esAjax()) {
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    } else {
        $_SESSION['message'] = [
            'title' => ($resultado['respuesta'] == 1) ? '¡Éxito!' : 'Error',
            'text' => $resultado['mensaje'],
            'icon' => ($resultado['respuesta'] == 1) ? 'success' : 'error'
        ];
        header("Location: ?pagina=entrada");
        exit;
    }
}

/* ============================================
   OPERACIÓN: ELIMINAR COMPRA
   ============================================ */

/* Procesa la eliminación de una compra. Valida: sesión, ID de compra válido*/
if (isset($_POST['eliminar_compra'])) {
    // ===== VALIDACIÓN DE SESIÓN =====
    if (empty($_SESSION["id"])) {
        $mensaje_error = 'Debe iniciar sesión para realizar esta acción.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            header("location:?pagina=login");
            exit;
        }
    }
    
    // ===== VALIDACIÓN DE VACÍOS =====
    if (empty($_POST['id_compra'])) {
        $mensaje_error = 'ID de compra no proporcionado.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    // ===== SANITIZACIÓN =====
    $id_compra_raw = sanitizar($_POST['id_compra']);
    
    // ===== VALIDACIÓN DE FORMATO Y LONGITUD =====
    if (!is_numeric($id_compra_raw)) {
        $mensaje_error = 'El ID de compra debe ser un número válido.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    $id_compra = intval($id_compra_raw);
    if ($id_compra <= 0 || $id_compra > 2147483647) {
        $mensaje_error = 'ID de compra inválido.';
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['respuesta' => 0, 'mensaje' => $mensaje_error]);
            exit;
        } else {
            $_SESSION['message'] = ['title' => 'Error', 'text' => $mensaje_error, 'icon' => 'error'];
            header("Location: ?pagina=entrada");
            exit;
        }
    }
    
    $datosCompra = [
        'operacion' => 'eliminar',
        'datos' => [
            'id_compra' => $id_compra
        ]
    ];

    $resultado = $entrada->procesarCompra(json_encode($datosCompra));

    if ($resultado['respuesta'] == 1) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Eliminación de compra',
            'descripcion' => 'Se eliminó la compra ID: ' . $datosCompra['datos']['id_compra']
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'entrada', $bitacora);
    }

    if (esAjax()) {
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    } else {
        $_SESSION['message'] = [
            'title' => ($resultado['respuesta'] == 1) ? '¡Éxito!' : 'Error',
            'text' => $resultado['mensaje'],
            'icon' => ($resultado['respuesta'] == 1) ? 'success' : 'error'
        ];
        header("Location: ?pagina=entrada");
        exit;
    }
}

/* ============================================
   CONSULTA DE DATOS PARA LA VISTA
   ============================================ */

// Consultar todas las compras para mostrar en la lista
$resultadoCompras = $entrada->procesarCompra(json_encode(['operacion' => 'consultar']));
$compras = isset($resultadoCompras['datos']) ? $resultadoCompras['datos'] : [];

// Si hay un ID en la URL, consultar los detalles de esa compra específica
$detalles_compra = [];
if (isset($_GET['id'])) {
    $resultadoDetalles = $entrada->procesarCompra(json_encode([
        'operacion' => 'consultarDetalles',
        'datos' => ['id_compra' => intval($_GET['id'])]
    ]));
    $detalles_compra = isset($resultadoDetalles['datos']) ? $resultadoDetalles['datos'] : [];
}

// Obtener la lista de productos activos para los formularios
$resultadoProductos = $entrada->procesarCompra(json_encode(['operacion' => 'consultarProductos']));
$productos_lista = isset($resultadoProductos['datos']) ? $resultadoProductos['datos'] : [];

// Obtener la lista de proveedores activos para los formularios
$resultadoProveedores = $entrada->procesarCompra(json_encode(['operacion' => 'consultarProveedores']));
$proveedores = isset($resultadoProveedores['datos']) ? $resultadoProveedores['datos'] : [];

if(isset($_POST['generar'])){
    // Eliminado: $entrada->generarPDF();
    // Eliminado: exit; // Evitar que se cargue la vista después del PDF
}

/* ============================================
   FUNCIÓN: GENERAR GRÁFICO DE COMPRAS
   ============================================ */

function generarGrafico() {
    /* Genera un gráfico de los productos más comprados */
    if (!extension_loaded('gd') || !function_exists('imagetypes') || !(imagetypes() & IMG_PNG)) {
        error_log("GD library no está habilitado o no tiene soporte PNG. No se puede generar el gráfico.");
        return;
    
    try {
        require_once('assets/js/jpgraph/src/jpgraph.php');
        require_once('assets/js/jpgraph/src/jpgraph_pie.php');
        require_once('assets/js/jpgraph/src/jpgraph_pie3d.php');

        $db = new Conexion();
        $conex1 = $db->getConex1();

        /* Primero verificamos si hay datos en las tablas necesarias*/
        $SQL_verificacion = "SELECT COUNT(*) as total FROM compra c 
                           INNER JOIN compra_detalles cd ON c.id_compra = cd.id_compra";
        $stmt_verificacion = $conex1->prepare($SQL_verificacion);
        $stmt_verificacion->execute();
        $total = $stmt_verificacion->fetch(PDO::FETCH_ASSOC)['total'];

        if ($total == 0) {
            // Si no hay datos, creamos un gráfico con mensaje
            $graph = new PieGraph(900, 500);
            $graph->SetShadow();
            
            // Configurar título
            $graph->title->Set("No hay datos de compras disponibles");
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            
            // Crear un gráfico vacío con mensaje
            $p1 = new PiePlot3D([100]);
            $p1->SetLegends(['No hay datos']);
            $p1->SetCenter(0.5, 0.45);
            $p1->SetSize(0.3);
            $p1->SetSliceColors(['#CCCCCC']);
            
            $graph->Add($p1);
            
            // Guardar el gráfico
            $imgDir = __DIR__ . "/../assets/img/grafica_reportes/";
            if (!file_exists($imgDir)) {
                mkdir($imgDir, 0777, true);
            }

            $imagePath = $imgDir . "grafico_entradas.png";
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            $graph->Stroke($imagePath);
            error_log("Se generó un gráfico vacío porque no hay datos de compras");
            return;
        }

        // Si hay datos, procedemos con la consulta normal
        $SQL = "SELECT 
                    p.nombre as nombre_producto,
                    COALESCE(SUM(cd.cantidad), 0) as total_comprado 
                FROM producto p 
                INNER JOIN compra_detalles cd ON p.id_producto = cd.id_producto 
                INNER JOIN compra c ON cd.id_compra = c.id_compra 
                WHERE p.estatus = 1 
                GROUP BY p.id_producto, p.nombre 
                HAVING total_comprado > 0
                ORDER BY total_comprado DESC 
                LIMIT 5";

        $stmt = $conex1->prepare($SQL);
        $stmt->execute();

        $data = [];
        $labels = [];

        // Verificar si hay resultados
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($resultados)) {
            error_log("No se encontraron productos en la consulta");
            // Si no hay datos, crear datos de ejemplo
            $data = [100];
            $labels = ['No hay datos de compras'];
        } else {
            foreach ($resultados as $resultado) {
                error_log("Producto encontrado: " . print_r($resultado, true));
                $labels[] = $resultado['nombre_producto'];
                $data[] = (int)$resultado['total_comprado'];
            }
        }

        // Crear el gráfico con configuración mejorada
        $graph = new PieGraph(900, 500);
        $graph->SetShadow();
        
        $p1 = new PiePlot3D($data);
        $p1->SetLegends($labels);
        $p1->SetCenter(0.5, 0.45);
        $p1->SetSize(0.3);
        
        $p1->ShowBorder();
        $p1->SetSliceColors(['#FF9999','#66B2FF','#99FF99','#FFCC99','#FF99CC']);
        
        $p1->SetLabelType(PIE_VALUE_ABS);
        $p1->value->SetFont(FF_ARIAL, FS_BOLD, 11);
        $p1->value->SetColor("black");
        
        $graph->Add($p1);

        // Guardar el gráfico
        $imgDir = __DIR__ . "/../assets/img/grafica_reportes/";
        if (!file_exists($imgDir)) {
            mkdir($imgDir, 0777, true);
        }

        $imagePath = $imgDir . "grafico_entradas.png";
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $graph->Stroke($imagePath);
        error_log("Gráfico generado exitosamente con datos reales");
        
    } catch (\Exception $e) {
        error_log("Error al generar el gráfico de compras: " . $e->getMessage());
    }
}
}

/* ============================================
   GENERACIÓN DE GRÁFICO Y CARGA DE VISTA
   ============================================ */

/* Generar gráfico de productos más comprados (debe ejecutarse antes de cargar la vista)*/
generarGrafico();

// Verificar permisos y cargar la vista correspondiente
if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(2, 1)) {
    $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'entrada';
    require_once 'vista/entrada.php';
} else {
    // Usuario sin permisos - mostrar página de privilegios insuficientes
    require_once 'vista/seguridad/privilegio.php';
} 
?>