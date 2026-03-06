<?php
use LoveMakeup\Proyecto\Modelo\Producto;
use LoveMakeup\Proyecto\Modelo\Proveedor;
use LoveMakeup\Proyecto\Modelo\Categoria;
use LoveMakeup\Proyecto\Modelo\Reporte;
use LoveMakeup\Proyecto\Modelo\Bitacora;
use LoveMakeup\Proyecto\Modelo\Marca;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// No necesitamos instancia de Producto sólo para la bitácora; usaremos Bitacora cuando haga falta

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el ID de la marca sea válido y exista en la base de datos
 */
function validarIdMarca($id_marca, $marcas) {
    if ($id_marca === '' || $id_marca === null) {
        return true; // Valor vacío es válido (significa "todas")
    }
    if (!is_numeric($id_marca)) {
        return false;
    }
    $id_marca = (int)$id_marca;
    foreach ($marcas as $marca) {
        // El método consultar() de Marca solo devuelve id_marca y nombre (sin estatus).
        // Consideramos válida la marca si el id coincide.
        if (isset($marca['id_marca']) && $marca['id_marca'] == $id_marca) {
            return true;
        }
    }
    return false;
}

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
    $metodos_validos = [1, 2, 3, 4, 5]; // 1=Pago Móvil, 2=Transferencia Bancaria, 3=Punto de Venta, 4=Efectivo Bs, 5=Divisas (Dolares $)
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
    $metodos_validos = [1, 2]; // 1=Pago Móvil, 2=Transferencia Bancaria
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

// ============================================
// CAPA 4: SANITIZACIÓN DE DATOS
// ============================================
// 1) Recoger valores "raw" y sanitizar
$startRaw = trim($_REQUEST['f_start'] ?? '');
$endRaw   = trim($_REQUEST['f_end']   ?? '');
$prodRaw  = trim($_REQUEST['f_id']    ?? '');
$provRaw  = trim($_REQUEST['f_prov']  ?? '');
$catRaw   = trim($_REQUEST['f_cat']   ?? '');
$marcaRaw = trim($_REQUEST['f_marca'] ?? '');

// Nuevos filtros avanzados - sanitizar
$montoMinRaw = trim($_REQUEST['monto_min'] ?? '');
$montoMaxRaw = trim($_REQUEST['monto_max'] ?? '');
$precioMinRaw = trim($_REQUEST['precio_min'] ?? '');
$precioMaxRaw = trim($_REQUEST['precio_max'] ?? '');
$stockMinRaw = trim($_REQUEST['stock_min'] ?? '');
$stockMaxRaw = trim($_REQUEST['stock_max'] ?? '');
$metodoPagoRaw = trim($_REQUEST['f_mp'] ?? '');
$metodoPagoWebRaw = trim($_REQUEST['metodo_pago'] ?? '');
$estadoRaw = trim($_REQUEST['estado'] ?? '');

// ============================================
// CAPA 5: VALIDACIÓN CON EXPRESIONES REGULARES
// ============================================
// Validar formato de fechas (YYYY-MM-DD) si vienen proporcionadas
if (!empty($startRaw) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startRaw)) {
    throw new \Exception('Formato de fecha de inicio inválido. Debe ser YYYY-MM-DD');
}

if (!empty($endRaw) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endRaw)) {
    throw new \Exception('Formato de fecha fin inválido. Debe ser YYYY-MM-DD');
}

// Validar IDs si vienen proporcionados (solo números enteros)
if (!empty($prodRaw) && !preg_match('/^\d+$/', $prodRaw)) {
    throw new \Exception('ID de producto inválido. Debe ser un número entero');
}

if (!empty($provRaw) && !preg_match('/^\d+$/', $provRaw)) {
    throw new \Exception('ID de proveedor inválido. Debe ser un número entero');
}

if (!empty($catRaw) && !preg_match('/^\d+$/', $catRaw)) {
    throw new \Exception('ID de categoría inválido. Debe ser un número entero');
}

if (!empty($marcaRaw) && !preg_match('/^\d+$/', $marcaRaw)) {
    throw new \Exception('ID de marca inválido. Debe ser un número entero');
}

// Validar montos si vienen proporcionados (números decimales positivos)
if (!empty($montoMinRaw) && !preg_match('/^\d+(\.\d{1,2})?$/', $montoMinRaw)) {
    throw new \Exception('Monto mínimo inválido. Debe ser un número decimal positivo');
}

if (!empty($montoMaxRaw) && !preg_match('/^\d+(\.\d{1,2})?$/', $montoMaxRaw)) {
    throw new \Exception('Monto máximo inválido. Debe ser un número decimal positivo');
}

if (!empty($precioMinRaw) && !preg_match('/^\d+(\.\d{1,2})?$/', $precioMinRaw)) {
    throw new \Exception('Precio mínimo inválido. Debe ser un número decimal positivo');
}

if (!empty($precioMaxRaw) && !preg_match('/^\d+(\.\d{1,2})?/$', $precioMaxRaw)) {
    throw new \Exception('Precio máximo inválido. Debe ser un número decimal positivo');
}

// Validar stock si viene proporcionado (números enteros no negativos)
if (!empty($stockMinRaw) && !preg_match('/^\d+$/', $stockMinRaw)) {
    throw new \Exception('Stock mínimo inválido. Debe ser un número entero no negativo');
}

if (!empty($stockMaxRaw) && !preg_match('/^\d+$/', $stockMaxRaw)) {
    throw new \Exception('Stock máximo inválido. Debe ser un número entero no negativo');
}

// Validar método de pago si viene proporcionado (solo números)
if (!empty($metodoPagoRaw) && !preg_match('/^\d+$/', $metodoPagoRaw)) {
    throw new \Exception('Método de pago inválido. Debe ser un número entero');
}

if (!empty($metodoPagoWebRaw) && !preg_match('/^\d+$/', $metodoPagoWebRaw)) {
    throw new \Exception('Método de pago web inválido. Debe ser un número entero');
}

// Validar estado si viene proporcionado (solo números)
if (!empty($estadoRaw) && !preg_match('/^\d+$/', $estadoRaw)) {
    throw new \Exception('Estado inválido. Debe ser un número entero');
}

// ============================================
// NORMALIZACIÓN DE DATOS (YA EXISTÍA)
// ============================================
// 3) Normalizar para que sean null o int/float
$start  = $startRaw ?: null;
$end    = $endRaw   ?: null;
$prodId = is_numeric($prodRaw) ? (int)$prodRaw : null;
$provId = is_numeric($provRaw) ? (int)$provRaw : null;
$catId  = is_numeric($catRaw)  ? (int)$catRaw  : null;
$marcaId = is_numeric($marcaRaw) ? (int)$marcaRaw : null;

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

// ============================================
// CAPA 3: VALIDACIÓN DE CAMPOS VACÍOS Y LÓGICA DE NEGOCIO
// ============================================
// Validar consistencia de rangos (solo si ambos valores fueron proporcionados)
if ($start && $end && $start > $end) {
    // Intercambiar para mantener comportamiento existente
    list($start, $end) = [$end, $start];
}

// Validar que las fechas no sean futuras
if ($start && $start > date('Y-m-d')) {
    throw new \Exception('La fecha de inicio no puede ser futura');
}

if ($end && $end > date('Y-m-d')) {
    throw new \Exception('La fecha fin no puede ser futura');
}

// Acción solicitada
$accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : '';

// 2) AJAX GET → conteos JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && in_array($accion, ['countCompra','countProducto','countVenta','countPedidoWeb'], true)
) {
    header('Content-Type: application/json');
    
    try {
        // ============================================
        // CAPA 3: VALIDACIÓN DE CAMPOS VACÍOS (EXISTENTE MEJORADA)
        // ============================================
        // Obtener listas para validación
        $productos_lista = (new Producto())->consultar();
        $proveedores_lista = (new Proveedor())->consultar();
        $categorias_lista = (new Categoria())->consultar();
        $marcas_lista = (new Marca())->consultar();
        
        // Validar parámetros comunes usando funciones existentes
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

        if (!validarIdMarca($marcaRaw, $marcas_lista)) {
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
            $cnt = Reporte::countCompra($start, $end, $prodId, $catId, $provId, $marcaId, $montoMin, $montoMax);
            break;
        case 'countProducto':
            $cnt = Reporte::countProducto($prodId, $provId, $catId, $marcaId, $precioMin, $precioMax, $stockMin, $stockMax, $estado);
            break;
        case 'countVenta':
            $cnt = Reporte::countVenta($start, $end, $prodId, $metodoPago, $catId, $marcaId, $montoMin, $montoMax);
            break;
        case 'countPedidoWeb':
            $cnt = Reporte::countPedidoWeb($start, $end, $prodId, $estado, $metodoPagoWeb, $marcaId, $montoMin, $montoMax);
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
    try {
        // ============================================
        // CAPA 3: VALIDACIÓN DE CAMPOS VACÍOS (EXISTENTE MEJORADA)
        // ============================================
        // Obtener listas para validación
        $productos_lista = (new Producto())->consultar();
        $proveedores_lista = (new Proveedor())->consultar();
        $categorias_lista = (new Categoria())->consultar();
        $marcas_lista = (new Marca())->consultar();
        
        // Validar parámetros comunes usando funciones existentes
        if (!validarIdProducto($prodRaw, $productos_lista)) {
            throw new \Exception('El producto seleccionado no es válido');
        }
        
        if (!validarIdProveedor($provRaw, $proveedores_lista)) {
            throw new \Exception('El proveedor seleccionado no es válido');
        }
        
        if (!validarIdCategoria($catRaw, $categorias_lista)) {
            throw new \Exception('La categoría seleccionada no es válida');
        }
        
        if (!validarIdMarca($marcaRaw, $marcas_lista)) {
            throw new \Exception('La marca seleccionada no es válida');
        }
        
        // Validaciones específicas por acción (POST)
        if ($accion === 'venta') {
            if (!validarMetodoPago($metodoPagoRaw)) {
                throw new \Exception('El método de pago seleccionado no es válido');
            }
        }

        if ($accion === 'pedidoWeb') {
            if (!validarMetodoPagoWeb($metodoPagoWebRaw)) {
                throw new \Exception('El método de pago web seleccionado no es válido');
            }
            if (!validarEstadoPedidoWeb($estadoRaw)) {
                throw new \Exception('El estado del pedido web seleccionado no es válido');
            }
        }

        if ($accion === 'producto') {
            if (!validarEstadoProducto($estadoRaw)) {
                throw new \Exception('El estado del producto seleccionado no es válido');
            }
        }

        $userId = $_SESSION['id'];
        $rol    = $_SESSION['nivel_rol'] == 2
                ? 'Asesora de Ventas'
                : 'Administrador';

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
                Reporte::compra($start, $end, $prodId, $catId, $provId, $marcaId, $montoMin, $montoMax);
                $desc = 'Generó Reporte de Compras';
                break;
            case 'producto':
                Reporte::producto($prodId, $provId, $catId, $marcaId, $precioMin, $precioMax, $stockMin, $stockMax, $estado);
                $desc = 'Generó Reporte de Productos';
                break;
            case 'venta':
                Reporte::venta($start, $end, $prodId, $catId, $metodoPago, $marcaId, $montoMin, $montoMax);
                $desc = 'Generó Reporte de Ventas';
                break;
            case 'pedidoWeb':
                Reporte::pedidoWeb($start, $end, $prodId, $estado, $metodoPagoWeb, $marcaId, $montoMin, $montoMax);
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
        try {
            $bit = new Bitacora();
            // registrarOperacion maneja la sesión y el formato del registro
            $bit->registrarOperacion($desc, 'reporte', ['descripcion' => "Usuario ($rol) ejecutó $desc"]);
        } catch (\Throwable $e) {
            error_log('reporte.php bitacora fallo: ' . $e->getMessage());
        }
    }

    exit; // PDF ya enviado
}

// 4) GET normal → cargar listas y mostrar pantalla
$productos_lista   = (new Producto())->consultar();
$proveedores_lista = (new Proveedor())->consultar();
$categorias_lista  = (new Categoria())->consultar();
$marcas_lista      = (new Marca())->consultar();



if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(1, 1)) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'reporte';
        require_once 'vista/reporte.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} if ($_SESSION["nivel_rol"] == 1) {
    header("Location: ?pagina=catalogo");
    exit();
}