<?php  

use LoveMakeup\Proyecto\Modelo\PedidoWeb;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION["id"])) {
    header("location:?pagina=login");
    exit;
}
if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
} 

if ($_SESSION["nivel_rol"] == 1) {
    header("Location: ?pagina=catalogo");
    exit();
}

require_once 'permiso.php';
$objPedidoWeb = new PedidoWeb();

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el id_pedido sea válido y exista en la base de datos
 */
function validarIdPedido($id_pedido, $objPedidoWeb) {
    if (empty($id_pedido) || !is_numeric($id_pedido)) {
        return false;
    }
    $id_pedido = (int)$id_pedido;
    $conex = $objPedidoWeb->getConex1();
    try {
        $sql = "SELECT id_pedido FROM pedido WHERE id_pedido = :id_pedido LIMIT 1";
        $stmt = $conex->prepare($sql);
        $stmt->execute(['id_pedido' => $id_pedido]);
        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
        $conex = null;
        return !empty($resultado);
    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        return false;
    }
}

/**
 * Valida que el estado_delivery sea válido
 */
function validarEstadoDelivery($estado_delivery) {
    if (empty($estado_delivery)) {
        return false;
    }
    $estados_validos = ['pendiente', 'en_camino', 'entregado', 'cancelado'];
    return in_array($estado_delivery, $estados_validos, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===========================
       CONFIRMAR PEDIDO
       =========================== */
    if (isset($_POST['confirmar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Validar id_pedido
            if (!validarIdPedido($_POST['id_pedido'], $objPedidoWeb)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
                exit;
            }
            $datosPeticion = [
                'operacion' => 'confirmar',
                'datos' => $_POST['id_pedido']
            ];
            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta el ID del pedido para confirmar']);
        }


    /* ===========================
       ELIMINAR PEDIDO
       =========================== */
    } else if (isset($_POST['eliminar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Validar id_pedido
            if (!validarIdPedido($_POST['id_pedido'], $objPedidoWeb)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
                exit;
            }
            $datosPeticion = [
                'operacion' => 'eliminar',
                'datos' => $_POST['id_pedido']
            ];
            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta el ID del pedido para eliminar']);
        }


    /* ===========================
       DELIVERY
       =========================== */
    } else if (!empty($_POST['id_pedido']) && isset($_POST['estado_delivery']) && isset($_POST['direccion'])) {

        // Validar id_pedido
        if (!validarIdPedido($_POST['id_pedido'], $objPedidoWeb)) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
            exit;
        }

        // Validar estado_delivery
        if (!validarEstadoDelivery($_POST['estado_delivery'])) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'El estado de delivery no es válido']);
            exit;
        }

        $datosPeticion = [
            'operacion' => 'delivery',
            'datos' => [
                'id_pedido' => $_POST['id_pedido'],
                'estado_delivery' => $_POST['estado_delivery'],
                'direccion' => $_POST['direccion']
            ]
        ];

        echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));


    /* ===========================
       ENVIAR PEDIDO
       =========================== */
    } else if (isset($_POST['enviar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Validar id_pedido
            if (!validarIdPedido($_POST['id_pedido'], $objPedidoWeb)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
                exit;
            }
            $datosPeticion = [
                'operacion' => 'enviar',
                'datos' => $_POST['id_pedido']
            ];
            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta el ID del pedido para enviar']);
        }


    /* ===========================
       ENTREGAR PEDIDO
       =========================== */
    } else if (isset($_POST['entregar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Validar id_pedido
            if (!validarIdPedido($_POST['id_pedido'], $objPedidoWeb)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
                exit;
            }
            $datosPeticion = [
                'operacion' => 'entregar',
                'datos' => $_POST['id_pedido']
            ];
            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta el ID del pedido para entregar']);
        }


    /* ===========================
       TRACKING (UNIFICADO) quitar en dado caso 
       =========================== */
    } else if (isset($_POST['tracking'])) {

        if (
            !empty($_POST['id_pedido']) &&
            !empty($_POST['tracking']) &&
            !empty($_POST['correo_cliente']) &&
            !empty($_POST['nombre_cliente'])
        ) {
            // Validar id_pedido
            if (!validarIdPedido($_POST['id_pedido'], $objPedidoWeb)) {
                echo json_encode(['success' => false, 'message' => 'El ID del pedido no es válido']);
                exit;
            }

            $datosPeticion = [
                'operacion' => 'tracking',
                'datos' => [
                    'id_pedido'      => $_POST['id_pedido'],
                    'tracking'       => $_POST['tracking'],
                    'correo_cliente' => $_POST['correo_cliente'],
                    'nombre_cliente' => $_POST['nombre_cliente']
                ]
            ];

            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));

        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Datos incompletos para tracking'
            ]);
        }

    }

    exit;
}


/* ===========================
   GET: CARGAR VISTA
   =========================== */
$pedidos = $objPedidoWeb->consultarPedidosCompletos();
foreach ($pedidos as &$p) {
    $p['detalles'] = $objPedidoWeb->consultarDetallesPedido($p['id_pedido']);
}

if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(5, 'ver')) {
    $pagina_actual = 'pedidoweb';
    require_once __DIR__ . '/../vista/pedidoweb.php';
} else {
    require_once 'vista/seguridad/privilegio.php';
}
