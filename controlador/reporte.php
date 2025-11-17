<?php

use LoveMakeup\Proyecto\Modelo\Reporte;
use LoveMakeup\Proyecto\Modelo\Producto;
use LoveMakeup\Proyecto\Modelo\Proveedor;
use LoveMakeup\Proyecto\Modelo\Categoria;

session_start();
if (empty($_SESSION['id'])) {
    header('Location:?pagina=login');
    exit;
}
if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
}

if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }/*  Validacion cliente  */
    
require_once 'permiso.php';

$objProd = new Producto();

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el ID del producto sea válido y exista en la base de datos
 */
function validarIdProducto($id_producto, $productos) {
    if ($id_producto === '' || $id_producto === null) {
        return true; // Valor vacío es válido (significa "todos")
    }
    if (!is_numeric($id_producto)) {
        return false;
    }
    $id_producto = (int)$id_producto;
    foreach ($productos as $producto) {
        if ($producto['id_producto'] == $id_producto && $producto['estatus'] == 1) {
            return true;
        }
    }
    return false;
}

/**
 * Valida que el ID del proveedor sea válido y exista en la base de datos
 */
function validarIdProveedor($id_proveedor, $proveedores) {
    if ($id_proveedor === '' || $id_proveedor === null) {
        return true; // Valor vacío es válido (significa "todos")
    }
    if (!is_numeric($id_proveedor)) {
        return false;
    }
    $id_proveedor = (int)$id_proveedor;
    foreach ($proveedores as $proveedor) {
        if ($proveedor['id_proveedor'] == $id_proveedor && $proveedor['estatus'] == 1) {
            return true;
        }
    }
    return false;
}

/**
 * Valida que el ID de la categoría sea válido y exista en la base de datos
 */
function validarIdCategoria($id_categoria, $categorias) {
    if ($id_categoria === '' || $id_categoria === null) {
        return true; // Valor vacío es válido (significa "todas")
    }
    if (!is_numeric($id_categoria)) {
        return false;
    }
    $id_categoria = (int)$id_categoria;
    foreach ($categorias as $categoria) {
        // Algunas consultas de categoría retornan sólo `id_categoria` y `nombre` (sin `estatus`).
        // Consideramos válida la categoría si el id coincide y, si existe `estatus`, debe ser 1.
        if (isset($categoria['id_categoria']) && $categoria['id_categoria'] == $id_categoria) {
            if (!isset($categoria['estatus']) || (int)$categoria['estatus'] === 1) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Valida que el método de pago sea válido
 */
function validarMetodoPago($metodo_pago) {
    if ($metodo_pago === '' || $metodo_pago === null) {
        return true; // Valor vacío es válido (significa "todos")
    }
    if (!is_numeric($metodo_pago)) {
        return false;
    }
    $metodo_pago = (int)$metodo_pago;
    $metodos_validos = [1, 2, 3]; // 1=Efectivo, 2=Transferencia, 3=Pago Móvil
    return in_array($metodo_pago, $metodos_validos, true);
}

/**
 * Valida que el método de pago web sea válido
 */
function validarMetodoPagoWeb($metodo_pago_web) {
    if ($metodo_pago_web === '' || $metodo_pago_web === null) {
        return true; // Valor vacío es válido (significa "todos")
    }
    if (!is_numeric($metodo_pago_web)) {
        return false;
    }
    $metodo_pago_web = (int)$metodo_pago_web;
    $metodos_validos = [2, 3]; // 2=Transferencia, 3=Pago Móvil
    return in_array($metodo_pago_web, $metodos_validos, true);
}

/**
 * Valida que el estado del producto sea válido
 */
function validarEstadoProducto($estado) {
    if ($estado === '' || $estado === null) {
        return true; // Valor vacío es válido (significa "todos")
    }
    if (!is_numeric($estado)) {
        return false;
    }
    $estado = (int)$estado;
    $estados_validos = [0, 1]; // 0=No disponible, 1=Disponible
    return in_array($estado, $estados_validos, true);
}

/**
 * Valida que el estado del pedido web sea válido
 */
function validarEstadoPedidoWeb($estado) {
    if ($estado === '' || $estado === null) {
        return true; // Valor vacío es válido (significa "todos")
    }
    if (!is_numeric($estado)) {
        return false;
    }
    $estado = (int)$estado;
    $estados_validos = [2, 3, 4, 5]; // 2=Pago verificado, 3=Pendiente envío, 4=En camino, 5=Entregado
    return in_array($estado, $estados_validos, true);
}

// 1) Recoger valores "raw" (pueden venir como string vacíos)
$startRaw = $_REQUEST['f_start'] ?? '';
$endRaw   = $_REQUEST['f_end']   ?? '';
$prodRaw  = $_REQUEST['f_id']    ?? '';
$provRaw  = $_REQUEST['f_prov']  ?? '';
$catRaw   = $_REQUEST['f_cat']   ?? '';

// 2) Nuevos filtros avanzados
$montoMinRaw = $_REQUEST['monto_min'] ?? '';
$montoMaxRaw = $_REQUEST['monto_max'] ?? '';
$precioMinRaw = $_REQUEST['precio_min'] ?? '';
$precioMaxRaw = $_REQUEST['precio_max'] ?? '';
$stockMinRaw = $_REQUEST['stock_min'] ?? '';
$stockMaxRaw = $_REQUEST['stock_max'] ?? '';
$metodoPagoRaw = $_REQUEST['f_mp'] ?? '';
$metodoPagoWebRaw = $_REQUEST['metodo_pago'] ?? '';
$estadoRaw = $_REQUEST['estado'] ?? '';

// 3) Normalizar para que sean null o int/float
$start  = $startRaw ?: null;
$end    = $endRaw   ?: null;
$prodId = is_numeric($prodRaw) ? (int)$prodRaw : null;
$provId = is_numeric($provRaw) ? (int)$provRaw : null;
$catId  = is_numeric($catRaw)  ? (int)$catRaw  : null;

// Normalizar nuevos filtros
$montoMin = is_numeric($montoMinRaw) ? (float)$montoMinRaw : null;
$montoMax = is_numeric($montoMaxRaw) ? (float)$montoMaxRaw : null;
$precioMin = is_numeric($precioMinRaw) ? (float)$precioMinRaw : null;
$precioMax = is_numeric($precioMaxRaw) ? (float)$precioMaxRaw : null;
$stockMin = is_numeric($stockMinRaw) ? (int)$stockMinRaw : null;
$stockMax = is_numeric($stockMaxRaw) ? (int)$stockMaxRaw : null;
$metodoPago = is_numeric($metodoPagoRaw) ? (int)$metodoPagoRaw : null;
$metodoPagoWeb = is_numeric($metodoPagoWebRaw) ? (int)$metodoPagoWebRaw : null;
$estado = is_numeric($estadoRaw) ? (int)$estadoRaw : null;

// Limitar fechas a hoy y corregir orden
$today = date('Y-m-d');
if ($start && $start > $today) $start = $today;
if ($end   && $end   > $today) $end   = $today;
if ($start && $end && $start > $end) {
    list($start, $end) = [$end, $start];
}

// Acción solicitada
$accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : '';

// 2) AJAX GET → conteos JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && in_array($accion, ['countCompra','countProducto','countVenta','countPedidoWeb'], true)
) {
    header('Content-Type: application/json');
    
    // Obtener listas para validación
    $productos_lista = (new Producto())->consultar();
    $proveedores_lista = (new Proveedor())->consultar();
    $categorias_lista = (new Categoria())->consultar();
    
    try {
    // Validar parámetros comunes
    if (!validarIdProducto($prodRaw, $productos_lista)) {
        echo json_encode(['count' => 0]);
        exit;
    }

    if (!validarIdProveedor($provRaw, $proveedores_lista)) {
        echo json_encode(['count' => 0]);
        exit;
    }

    if (!validarIdCategoria($catRaw, $categorias_lista)) {
        echo json_encode(['count' => 0]);
        exit;
    }

    // Validaciones específicas por acción
    switch ($accion) {
        case 'countProducto':
            if (!validarEstadoProducto($estadoRaw)) {
                echo json_encode(['count' => 0]);
                exit;
            }
            break;
        case 'countVenta':
            if (!validarMetodoPago($metodoPagoRaw)) {
                echo json_encode(['count' => 0]);
                exit;
            }
            break;
        case 'countPedidoWeb':
            if (!validarMetodoPagoWeb($metodoPagoWebRaw)) {
                echo json_encode(['count' => 0]);
                exit;
            }
            if (!validarEstadoPedidoWeb($estadoRaw)) {
                echo json_encode(['count' => 0]);
                exit;
            }
            break;
        case 'countCompra':
        default:
            // No validations extra
            break;
    }

    switch ($accion) {
        case 'countCompra':
            $cnt = Reporte::countCompra($start, $end, $prodId, $catId, $provId, $montoMin, $montoMax);
            break;
        case 'countProducto':
            $cnt = Reporte::countProducto($prodId, $provId, $catId, $precioMin, $precioMax, $stockMin, $stockMax, $estado);
            break;
        case 'countVenta':
            $cnt = Reporte::countVenta($start, $end, $prodId, $metodoPago, $catId, $montoMin, $montoMax);
            break;
        case 'countPedidoWeb':
            $cnt = Reporte::countPedidoWeb($start, $end, $prodId, $estado, $metodoPagoWeb, $montoMin, $montoMax);
            break;
        default:
            $cnt = 0;
    }

    echo json_encode(['count' => (int)$cnt]);
    exit;
    } catch (\Throwable $e) {
        // Log y responder JSON de error para que el frontend no entre en catch genérico
        error_log('reporte.php GET EXCEPTION: ' . $e->getMessage());
        error_log($e->getTraceAsString());
        http_response_code(500);
        header('Content-Type: application/json');
        // Incluir mensaje de error en la respuesta para depuración local
        echo json_encode([
            'error' => 'Error interno al verificar los datos',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// 3) POST → generar PDF y registrar bitácora
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($accion, ['compra','producto','venta','pedidoWeb'], true)
) {
    // Obtener listas para validación
    $productos_lista = (new Producto())->consultar();
    $proveedores_lista = (new Proveedor())->consultar();
    $categorias_lista = (new Categoria())->consultar();
    
    // Validar parámetros
    if (!validarIdProducto($prodRaw, $productos_lista)) {
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error al generar reporte</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #721c24; }
        p { color: #856404; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Error al generar el reporte</h1>
        <p>El producto seleccionado no es válido.</p>
        <p><a href="?pagina=reporte">Volver a Reportes</a></p>
    </div>
</body>
</html>';
        exit;
    }
    
    if (!validarIdProveedor($provRaw, $proveedores_lista)) {
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error al generar reporte</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #721c24; }
        p { color: #856404; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Error al generar el reporte</h1>
        <p>El proveedor seleccionado no es válido.</p>
        <p><a href="?pagina=reporte">Volver a Reportes</a></p>
    </div>
</body>
</html>';
        exit;
    }
    
    if (!validarIdCategoria($catRaw, $categorias_lista)) {
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error al generar reporte</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #721c24; }
        p { color: #856404; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Error al generar el reporte</h1>
        <p>La categoría seleccionada no es válida.</p>
        <p><a href="?pagina=reporte">Volver a Reportes</a></p>
    </div>
</body>
</html>';
        exit;
    }
    
    // Validaciones específicas por acción (POST)
    if ($accion === 'venta') {
        if (!validarMetodoPago($metodoPagoRaw)) {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error al generar reporte</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #721c24; }
        p { color: #856404; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Error al generar el reporte</h1>
        <p>El método de pago seleccionado no es válido.</p>
        <p><a href="?pagina=reporte">Volver a Reportes</a></p>
    </div>
</body>
</html>';
            exit;
        }
    }

    if ($accion === 'pedidoWeb') {
        if (!validarMetodoPagoWeb($metodoPagoWebRaw)) {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error al generar reporte</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #721c24; }
        p { color: #856404; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Error al generar el reporte</h1>
        <p>El método de pago web seleccionado no es válido.</p>
        <p><a href="?pagina=reporte">Volver a Reportes</a></p>
    </div>
</body>
</html>';
            exit;
        }
        if (!validarEstadoPedidoWeb($estadoRaw)) {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error al generar reporte</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #721c24; }
        p { color: #856404; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Error al generar el reporte</h1>
        <p>El estado del pedido web seleccionado no es válido.</p>
        <p><a href="?pagina=reporte">Volver a Reportes</a></p>
    </div>
</body>
</html>';
            exit;
        }
    }

    if ($accion === 'producto') {
        if (!validarEstadoProducto($estadoRaw)) {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error al generar reporte</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #721c24; }
        p { color: #856404; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Error al generar el reporte</h1>
        <p>El estado del producto seleccionado no es válido.</p>
        <p><a href="?pagina=reporte">Volver a Reportes</a></p>
    </div>
</body>
</html>';
            exit;
        }
    }

    $userId = $_SESSION['id'];
    $rol    = $_SESSION['nivel_rol'] == 2
            ? 'Asesora de Ventas'
            : 'Administrador';

    try {
        // Log de diagnóstico: volcar parámetros recibidos antes de generar el reporte
        error_log(sprintf(
            "reporte.php: accion=%s start=%s end=%s prodRaw=%s prodId=%s catRaw=%s catId=%s provRaw=%s provId=%s metodoPagoRaw=%s metodoPago=%s metodoPagoWebRaw=%s metodoPagoWeb=%s montoMin=%s montoMax=%s estadoRaw=%s estado=%s",
            $accion,
            var_export($startRaw, true),
            var_export($endRaw, true),
            var_export($prodRaw, true),
            var_export($prodId, true),
            var_export($catRaw, true),
            var_export($catId, true),
            var_export($provRaw, true),
            var_export($provId, true),
            var_export($metodoPagoRaw, true),
            var_export($metodoPago, true),
            var_export($metodoPagoWebRaw, true),
            var_export($metodoPagoWeb, true),
            var_export($montoMinRaw, true),
            var_export($montoMaxRaw, true),
            var_export($estadoRaw, true),
            var_export($estado, true)
        ));

        switch ($accion) {
            case 'compra':
                Reporte::compra($start, $end, $prodId, $catId, $provId, $montoMin, $montoMax);
                $desc = 'Generó Reporte de Compras';
                break;
            case 'producto':
                Reporte::producto($prodId, $provId, $catId, $precioMin, $precioMax, $stockMin, $stockMax, $estado);
                $desc = 'Generó Reporte de Productos';
                break;
            case 'venta':
                Reporte::venta($start, $end, $prodId, $catId, $metodoPago, $montoMin, $montoMax);
                $desc = 'Generó Reporte de Ventas';
                break;
            case 'pedidoWeb':
                Reporte::pedidoWeb($start, $end, $prodId, $estado, $metodoPagoWeb, $montoMin, $montoMax);
                $desc = 'Generó Reporte de Pedidos Web';
                break;
            default:
                $desc = '';
        }
    } catch (\Exception $e) {
        // Log del error para diagnóstico
        error_log('reporte.php EXCEPTION: ' . $e->getMessage());
        // Si hay un error (por ejemplo, GD no está habilitado), mostrar mensaje al usuario
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Error al generar reporte</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #721c24; }
        p { color: #856404; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>Error al generar el reporte</h1>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p><strong>Solución:</strong> Por favor, habilite la extensión GD de PHP en el archivo php.ini de su servidor.</p>
        <p><a href="?pagina=reporte">Volver a Reportes</a></p>
    </div>
</body>
</html>';
        exit;
    }

    if ($desc) {
        $objProd->registrarBitacora(json_encode([
            'id_persona'  => $userId,
            'accion'      => $desc,
            'descripcion' => "Usuario ($rol) ejecutó $desc"
        ]));
    }

    exit; // PDF ya enviado
}

// 4) GET normal → cargar listas y mostrar pantalla
$productos_lista   = (new Producto())->consultar();
$proveedores_lista = (new Proveedor())->consultar();
$categorias_lista  = (new Categoria())->consultar();



if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(1, 'ver')) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'reporte';
        require_once 'vista/reporte.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} if ($_SESSION["nivel_rol"] == 1) {
    header("Location: ?pagina=catalogo");
    exit();
}