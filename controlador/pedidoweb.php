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

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN Y SANITIZACIÓN CONTRA INYECCIÓN SQL |||||||||||||||||||||||||||||*/

/**
 * Detecta intentos de inyección SQL en un string
 */
function detectarInyeccionSQL($valor) {
    if (empty($valor)) {
        return false;
    }
    
    $valor_lower = strtolower($valor);
    
    // Patrones comunes de inyección SQL
    $patrones_peligrosos = [
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bselect\b.*\bfrom\b)/i',
        '/(\binsert\b.*\binto\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(\bcreate\b.*\btable\b)/i',
        '/(\balter\b.*\btable\b)/i',
        '/(\bexec\b|\bexecute\b)/i',
        '/(\bsp_\w+)/i',
        '/(\bxp_\w+)/i',
        '/(--|\#|\/\*|\*\/)/',
        '/(\bor\b.*\b1\s*=\s*1\b)/i',
        '/(\band\b.*\b1\s*=\s*1\b)/i',
        '/(\bor\b.*\b1\s*=\s*0\b)/i',
        '/(\band\b.*\b1\s*=\s*0\b)/i',
        '/(\bwaitfor\b.*\bdelay\b)/i'
    ];
    
    foreach ($patrones_peligrosos as $patron) {
        if (preg_match($patron, $valor_lower)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Sanitiza un número entero
 */
function sanitizarEntero($valor, $min = null, $max = null) {
    if (!is_numeric($valor)) {
        return null;
    }
    $valor = (int)$valor;
    if ($min !== null && $valor < $min) {
        return null;
    }
    if ($max !== null && $valor > $max) {
        return null;
    }
    return $valor;
}

/**
 * Sanitiza un string eliminando caracteres peligrosos
 */
function sanitizarString($valor, $maxLength = 255) {
    if (empty($valor)) {
        return '';
    }
    
    // Detectar inyección SQL
    if (detectarInyeccionSQL($valor)) {
        return '';
    }
    
    $valor = trim($valor);
    
    // Eliminar caracteres peligrosos
    $caracteres_peligrosos = [';', '--', '/*', '*/', '<', '>', '"', "'", '`'];
    foreach ($caracteres_peligrosos as $char) {
        $valor = str_replace($char, '', $valor);
    }
    
    // Limitar longitud
    if (strlen($valor) > $maxLength) {
        $valor = substr($valor, 0, $maxLength);
    }
    
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida y sanitiza dirección
 */
function sanitizarDireccion($direccion) {
    if (empty($direccion)) {
        return '';
    }
    $direccion = trim($direccion);
    // Detectar inyección SQL
    if (detectarInyeccionSQL($direccion)) {
        return '';
    }
    // Eliminar caracteres peligrosos pero permitir caracteres comunes en direcciones
    $direccion = preg_replace('/[<>"\']/', '', $direccion);
    // Longitud máxima
    if (strlen($direccion) > 500) {
        $direccion = substr($direccion, 0, 500);
    }
    return htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida formato de email
 */
function validarEmail($email) {
    if (empty($email)) {
        return false;
    }
    // Detectar inyección SQL
    if (detectarInyeccionSQL($email)) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

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
            // Sanitizar y validar id_pedido
            $id_pedido = sanitizarEntero($_POST['id_pedido'], 1);
            if (!$id_pedido || !validarIdPedido($id_pedido, $objPedidoWeb)) {
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


    /* ===========================
       ELIMINAR PEDIDO
       =========================== */
    } else if (isset($_POST['eliminar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Sanitizar y validar id_pedido
            $id_pedido = sanitizarEntero($_POST['id_pedido'], 1);
            if (!$id_pedido || !validarIdPedido($id_pedido, $objPedidoWeb)) {
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


    /* ===========================
       DELIVERY
       =========================== */
    } else if (!empty($_POST['id_pedido']) && isset($_POST['estado_delivery']) && isset($_POST['direccion'])) {

        // Sanitizar y validar id_pedido
        $id_pedido = sanitizarEntero($_POST['id_pedido'], 1);
        if (!$id_pedido || !validarIdPedido($id_pedido, $objPedidoWeb)) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'El ID del pedido no es válido']);
            exit;
        }

        // Sanitizar y validar estado_delivery
        $estado_delivery = sanitizarString($_POST['estado_delivery'] ?? '', 50);
        if (empty($estado_delivery) || !validarEstadoDelivery($estado_delivery)) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'El estado de delivery no es válido']);
            exit;
        }

        // Sanitizar dirección
        $direccion = sanitizarDireccion($_POST['direccion'] ?? '');

        $datosPeticion = [
            'operacion' => 'delivery',
            'datos' => [
                'id_pedido' => $id_pedido,
                'estado_delivery' => $estado_delivery,
                'direccion' => $direccion
            ]
        ];

        echo json_encode($objPedidoWeb->procesarPedidoweb(json_encode($datosPeticion)));


    /* ===========================
       ENVIAR PEDIDO
       =========================== */
    } else if (isset($_POST['enviar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Sanitizar y validar id_pedido
            $id_pedido = sanitizarEntero($_POST['id_pedido'], 1);
            if (!$id_pedido || !validarIdPedido($id_pedido, $objPedidoWeb)) {
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


    /* ===========================
       ENTREGAR PEDIDO
       =========================== */
    } else if (isset($_POST['entregar'])) {

        if (!empty($_POST['id_pedido'])) {
            // Sanitizar y validar id_pedido
            $id_pedido = sanitizarEntero($_POST['id_pedido'], 1);
            if (!$id_pedido || !validarIdPedido($id_pedido, $objPedidoWeb)) {
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
            // Sanitizar y validar id_pedido
            $id_pedido = sanitizarEntero($_POST['id_pedido'], 1);
            if (!$id_pedido || !validarIdPedido($id_pedido, $objPedidoWeb)) {
                echo json_encode(['success' => false, 'message' => 'El ID del pedido no es válido']);
                exit;
            }

            // Sanitizar tracking
            $tracking = sanitizarString($_POST['tracking'] ?? '', 50);
            if (empty($tracking)) {
                echo json_encode(['success' => false, 'message' => 'El número de tracking no es válido']);
                exit;
            }

            // Sanitizar y validar correo
            $correo_cliente = sanitizarString($_POST['correo_cliente'] ?? '', 100);
            if (empty($correo_cliente) || !validarEmail($correo_cliente)) {
                echo json_encode(['success' => false, 'message' => 'El correo del cliente no es válido']);
                exit;
            }

            // Sanitizar nombre
            $nombre_cliente = sanitizarString($_POST['nombre_cliente'] ?? '', 100);
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
