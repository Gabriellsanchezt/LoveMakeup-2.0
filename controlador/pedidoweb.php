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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 
       CONFIRMAR PEDIDO
       */
    if (isset($_POST['confirmar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Sanitizar y validar id_pedido
            $id_pedido = $objPedidoWeb->sanitizarEnteropw($_POST['id_pedido'], 1);
            if (!$id_pedido || !$objPedidoWeb->validarIdPedidopw($id_pedido, $objPedidoWeb)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
                exit;
            }
            $datosPeticion = [
                'operacion' => 'confirmar',
                'datos' => $id_pedido
            ];
            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta el ID del pedido para confirmar']);
        }


    /*
       ELIMINAR PEDIDO
       */
    } else if (isset($_POST['eliminar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Sanitizar y validar id_pedido
            $id_pedido = $objPedidoWeb->sanitizarEnteropw($_POST['id_pedido'], 1);
            if (!$id_pedido || !$objPedidoWeb->validarIdPedidopw($id_pedido, $objPedidoWeb)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
                exit;
            }
            $datosPeticion = [
                'operacion' => 'eliminar',
                'datos' => $id_pedido
            ];
            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta el ID del pedido para eliminar']);
        }


    /* 
       DELIVERY
        */
    } else if (!empty($_POST['id_pedido']) && isset($_POST['estado_delivery']) && isset($_POST['direccion'])) {

        // Sanitizar y validar id_pedido
        $id_pedido = $objPedidoWeb->sanitizarEnteropw($_POST['id_pedido'], 1);
        if (!$id_pedido || !$objPedidoWeb->validarIdPedidopw($id_pedido, $objPedidoWeb)) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
            exit;
        }

        // Sanitizar y validar estado_delivery
        $estado_delivery = $objPedidoWeb->sanitizarStringpw($_POST['estado_delivery'] ?? '', 50);
        if (empty($estado_delivery) || !$objPedidoWeb->validarEstadoDeliverypw($estado_delivery)) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'El estado de delivery no es válido']);
            exit;
        }

        // Sanitizar dirección
        $direccion = $objPedidoWeb->sanitizarDireccionpw($_POST['direccion'] ?? '');

        $datosPeticion = [
            'operacion' => 'delivery',
            'datos' => [
                'id_pedido' => $id_pedido,
                'estado_delivery' => $estado_delivery,
                'direccion' => $direccion
            ]
        ];

        echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));


    /*
       ENVIAR PEDIDO
       */
    } else if (isset($_POST['enviar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Sanitizar y validar id_pedido
            $id_pedido = $objPedidoWeb->sanitizarEnteropw($_POST['id_pedido'], 1);
            if (!$id_pedido || !$objPedidoWeb->validarIdPedidopw($id_pedido, $objPedidoWeb)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
                exit;
            }
            $datosPeticion = [
                'operacion' => 'enviar',
                'datos' => $id_pedido
            ];
            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta el ID del pedido para enviar']);
        }


    /* 
       ENTREGAR PEDIDO
      */
    } else if (isset($_POST['entregar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Sanitizar y validar id_pedido
            $id_pedido = $objPedidoWeb->sanitizarEnteropw($_POST['id_pedido'], 1);
            if (!$id_pedido || !$objPedidoWeb->validarIdPedidopw($id_pedido, $objPedidoWeb)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
                exit;
            }
            $datosPeticion = [
                'operacion' => 'entregar',
                'datos' => $id_pedido
            ];
            echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta el ID del pedido para entregar']);
        }


    /*
       TRACKING  
      */
    } else if (isset($_POST['tracking'])) {

        if (
            !empty($_POST['id_pedido']) &&
            !empty($_POST['tracking']) &&
            !empty($_POST['correo_cliente']) &&
            !empty($_POST['nombre_cliente'])
        ) {
            // Sanitizar y validar id_pedido
            $id_pedido = $objPedidoWeb->sanitizarEnteropw($_POST['id_pedido'], 1);
            if (!$id_pedido || !$objPedidoWeb->validarIdPedidopw($id_pedido, $objPedidoWeb)) {
                echo json_encode(['success' => false, 'message' => 'El ID del pedido no es válido']);
                exit;
            }

            // Sanitizar tracking
            $tracking = $objPedidoWeb->sanitizarStringpw($_POST['tracking'] ?? '', 50);
            if (empty($tracking)) {
                echo json_encode(['success' => false, 'message' => 'El número de tracking no es válido']);
                exit;
            }

            // Sanitizar y validar correo
            $correo_cliente = $objPedidoWeb->sanitizarStringpw($_POST['correo_cliente'] ?? '', 100);
            if (empty($correo_cliente) || !$objPedidoWeb->validarEmailpw($correo_cliente)) {
                echo json_encode(['success' => false, 'message' => 'El correo del cliente no es válido']);
                exit;
            }

            // Sanitizar nombre
            $nombre_cliente = $objPedidoWeb->sanitizarStringpw($_POST['nombre_cliente'] ?? '', 100);
            if (empty($nombre_cliente)) {
                echo json_encode(['success' => false, 'message' => 'El nombre del cliente no es válido']);
                exit;
            }

            $datosPeticion = [
                'operacion' => 'tracking',
                'datos' => [
                    'id_pedido'      => $id_pedido,
                    'tracking'       => $tracking,
                    'correo_cliente' => $correo_cliente,
                    'nombre_cliente' => $nombre_cliente
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


/* 
   VISTA
 */
$pedidos = $objPedidoWeb->consultarPedidosCompletos();
foreach ($pedidos as &$p) {
    $p['detalles'] = $objPedidoWeb->consultarDetallesPedido($p['id_pedido']);
}

if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(5, 1)) {
    $pagina_actual = 'pedidoweb';
    require_once __DIR__ . '/../vista/pedidoweb.php';
} else {
    require_once 'vista/seguridad/privilegio.php';
}
