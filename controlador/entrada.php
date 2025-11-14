<?php

use LoveMakeup\Proyecto\Modelo\Entrada;
use LoveMakeup\Proyecto\Modelo\Bitacora;
use LoveMakeup\Proyecto\Config\Conexion;

session_start();
if (empty($_SESSION["id"])) {
    header("location:?pagina=login");
    exit;
} /* Validacion URL */
if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
} 

if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }/*  Validacion cliente  */

require_once 'permiso.php';
$entrada = new Entrada();

// Detectar si la solicitud es AJAX
function esAjax() {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

// Función para sanitizar datos de entrada
function sanitizar($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Función para validar que un ID de proveedor existe y está activo
function validarIdProveedor($id_proveedor) {
    if (empty($id_proveedor) || !is_numeric($id_proveedor)) {
        return false;
    }
    
    $id_proveedor = intval($id_proveedor);
    if ($id_proveedor <= 0) {
        return false;
    }
    
    try {
        require_once 'modelo/entrada.php';
        $entrada = new Entrada();
        $resultado = $entrada->procesarCompra(json_encode([
            'operacion' => 'consultarProveedores',
            'datos' => null
        ]));
        
        if ($resultado['respuesta'] == 1 && isset($resultado['datos'])) {
            $proveedores_validos = array_column($resultado['datos'], 'id_proveedor');
            return in_array($id_proveedor, $proveedores_validos);
        }
        
        return false;
    } catch (\Exception $e) {
        return false;
    }
}

// Función para validar que un ID de producto existe y está activo
function validarIdProducto($id_producto) {
    if (empty($id_producto) || !is_numeric($id_producto)) {
        return false;
    }
    
    $id_producto = intval($id_producto);
    if ($id_producto <= 0) {
        return false;
    }
    
    try {
        require_once 'modelo/entrada.php';
        $entrada = new Entrada();
        $resultado = $entrada->procesarCompra(json_encode([
            'operacion' => 'consultarProductos',
            'datos' => null
        ]));
        
        if ($resultado['respuesta'] == 1 && isset($resultado['datos'])) {
            $productos_validos = array_column($resultado['datos'], 'id_producto');
            return in_array($id_producto, $productos_validos);
        }
        
        return false;
    } catch (\Exception $e) {
        return false;
    }
}

// Función para validar un array de IDs de productos
function validarIdsProductos($ids_productos) {
    if (!is_array($ids_productos) || empty($ids_productos)) {
        return false;
    }
    
    // Obtener lista de productos válidos una sola vez
    try {
        require_once 'modelo/entrada.php';
        $entrada = new Entrada();
        $resultado = $entrada->procesarCompra(json_encode([
            'operacion' => 'consultarProductos',
            'datos' => null
        ]));
        
        if ($resultado['respuesta'] != 1 || !isset($resultado['datos'])) {
            return false;
        }
        
        $productos_validos = array_column($resultado['datos'], 'id_producto');
        
        // Validar cada ID del array
        foreach ($ids_productos as $id_producto) {
            if (empty($id_producto)) {
                continue; // Permitir valores vacíos (se filtran después)
            }
            
            $id_producto = intval($id_producto);
            if ($id_producto <= 0 || !in_array($id_producto, $productos_validos)) {
                return false;
            }
        }
        
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

// Procesar el registro de una nueva compra
if (isset($_POST['registrar_compra'])) {
    // Validar que los campos requeridos estén presentes
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
    
    // Validar que la fecha esté dentro del rango permitido (hoy y 2 días anteriores)
    $fecha_entrada = trim($_POST['fecha_entrada']);
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
    
    // Validar ID de proveedor - verificar que existe y está activo
    $id_proveedor = intval($_POST['id_proveedor']);
    if ($id_proveedor <= 0 || !validarIdProveedor($id_proveedor)) {
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
    if (!validarIdsProductos($_POST['id_producto'])) {
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
    
    // Procesar productos
    $productos = [];
    for ($i = 0; $i < $count_productos; $i++) {
        if (!empty($_POST['id_producto'][$i]) && isset($_POST['cantidad'][$i]) && $_POST['cantidad'][$i] > 0) {
            // Validar individualmente cada producto antes de agregarlo
            $id_producto = intval($_POST['id_producto'][$i]);
            if (!validarIdProducto($id_producto)) {
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
            
            $productos[] = array(
                'id_producto' => $id_producto,
                'cantidad' => intval($_POST['cantidad'][$i]),
                'precio_unitario' => floatval($_POST['precio_unitario'][$i]),
                'precio_total' => floatval($_POST['precio_total'][$i])
            );
        }
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

// Procesar la modificación de una compra
if (isset($_POST['modificar_compra'])) {
    // Validar que los campos requeridos estén presentes
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
    
    // Validar ID de compra
    $id_compra = intval($_POST['id_compra']);
    if ($id_compra <= 0) {
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
    
    // Validar que la fecha esté dentro del rango permitido (hoy y 2 días anteriores)
    $fecha_entrada = trim($_POST['fecha_entrada']);
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
    
    // Validar ID de proveedor - verificar que existe y está activo
    $id_proveedor = intval($_POST['id_proveedor']);
    if ($id_proveedor <= 0 || !validarIdProveedor($id_proveedor)) {
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
    if (!validarIdsProductos($_POST['id_producto'])) {
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
    
    // Procesar productos
    $productos = [];
    for ($i = 0; $i < $count_productos; $i++) {
        if (!empty($_POST['id_producto'][$i]) && isset($_POST['cantidad'][$i]) && $_POST['cantidad'][$i] > 0) {
            // Validar individualmente cada producto antes de agregarlo
            $id_producto = intval($_POST['id_producto'][$i]);
            if (!validarIdProducto($id_producto)) {
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
            
            $productos[] = array(
                'id_producto' => $id_producto,
                'cantidad' => intval($_POST['cantidad'][$i]),
                'precio_unitario' => floatval($_POST['precio_unitario'][$i]),
                'precio_total' => floatval($_POST['precio_total'][$i])
            );
        }
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

// Procesar la eliminación de una compra
if (isset($_POST['eliminar_compra'])) {
    // Validar ID de compra
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
    
    $id_compra = intval($_POST['id_compra']);
    if ($id_compra <= 0) {
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

// Consultar datos para la vista
$resultadoCompras = $entrada->procesarCompra(json_encode(['operacion' => 'consultar']));
$compras = isset($resultadoCompras['datos']) ? $resultadoCompras['datos'] : [];

// Si hay un ID en la URL, consultamos los detalles de esa compra
$detalles_compra = [];
if (isset($_GET['id'])) {
    $resultadoDetalles = $entrada->procesarCompra(json_encode([
        'operacion' => 'consultarDetalles',
        'datos' => ['id_compra' => intval($_GET['id'])]
    ]));
    $detalles_compra = isset($resultadoDetalles['datos']) ? $resultadoDetalles['datos'] : [];
}

// Obtener la lista de productos y proveedores para los formularios
$resultadoProductos = $entrada->procesarCompra(json_encode(['operacion' => 'consultarProductos']));
$productos_lista = isset($resultadoProductos['datos']) ? $resultadoProductos['datos'] : [];

$resultadoProveedores = $entrada->procesarCompra(json_encode(['operacion' => 'consultarProveedores']));
$proveedores = isset($resultadoProveedores['datos']) ? $resultadoProveedores['datos'] : [];

if(isset($_POST['generar'])){
    // Eliminado: $entrada->generarPDF();
    // Eliminado: exit; // Evitar que se cargue la vista después del PDF
}

// Generar gráfico antes de cargar la vista
function generarGrafico() {
    // Verificar si GD está habilitado y tiene soporte PNG
    if (!extension_loaded('gd') || !function_exists('imagetypes') || !(imagetypes() & IMG_PNG)) {
        error_log("GD library no está habilitado o no tiene soporte PNG. No se puede generar el gráfico.");
        return; // Salir silenciosamente si GD no está disponible
    }
    
    try {
        require_once('assets/js/jpgraph/src/jpgraph.php');
        require_once('assets/js/jpgraph/src/jpgraph_pie.php');
        require_once('assets/js/jpgraph/src/jpgraph_pie3d.php');

        $db = new Conexion();
        $conex1 = $db->getConex1();

        // Primero verificamos si hay datos en las tablas necesarias
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

// Llamar la función para generar la gráfica ANTES de cargar la vista
generarGrafico();

// Cargamos la vista

if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(2, 'ver')) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'entrada';
        require_once 'vista/entrada.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} 
?>