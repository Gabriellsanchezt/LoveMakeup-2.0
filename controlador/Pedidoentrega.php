<?php



use LoveMakeup\Proyecto\Modelo\VentaWeb;
use LoveMakeup\Proyecto\Modelo\Delivery;
use LoveMakeup\Proyecto\Modelo\MetodoEntrega;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nombre = isset($_SESSION["nombre"]) && !empty($_SESSION["nombre"]) ? $_SESSION["nombre"] : "Estimado Cliente";
$apellido = isset($_SESSION["apellido"]) && !empty($_SESSION["apellido"]) ? $_SESSION["apellido"] : ""; 

$nombreCompleto = trim($nombre . " " . $apellido);

$sesion_activa = isset($_SESSION["id"]) && !empty($_SESSION["id"]);

if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
}

$venta = new VentaWeb();

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el metodo_entrega sea válido (1, 2, 3, 4)
 */
function validarMetodoEntrega($metodo_entrega) {
    if (empty($metodo_entrega) || !is_numeric($metodo_entrega)) {
        return false;
    }
    $metodo_entrega = (int)$metodo_entrega;
    $metodos_validos = [1, 2, 3, 4];
    return in_array($metodo_entrega, $metodos_validos, true);
}

/**
 * Valida que la empresa_envio sea válida (2, 3 para MRW y ZOOM)
 */
function validarEmpresaEnvio($empresa_envio) {
    if (empty($empresa_envio) || !is_numeric($empresa_envio)) {
        return false;
    }
    $empresa_envio = (int)$empresa_envio;
    $empresas_validas = [2, 3];
    return in_array($empresa_envio, $empresas_validas, true);
}

/**
 * Valida que el id_delivery sea válido y exista en la base de datos
 */
function validarIdDelivery($id_delivery, $deliveries_activos) {
    if (empty($id_delivery) || !is_numeric($id_delivery)) {
        return false;
    }
    $id_delivery = (int)$id_delivery;
    foreach ($deliveries_activos as $delivery) {
        if ($delivery['id_delivery'] == $id_delivery) {
            return true;
        }
    }
    return false;
}

/**
 * Valida que la zona sea válida
 */
function validarZona($zona) {
    if (empty($zona)) {
        return false;
    }
    $zonas_validas = ['norte', 'sur', 'este', 'oeste', 'centro'];
    return in_array(strtolower($zona), $zonas_validas, true);
}

/**
 * Valida que la parroquia sea válida (básica, puede expandirse según necesidades)
 */
function validarParroquia($parroquia) {
    if (empty($parroquia)) {
        return false;
    }
    // Validación básica: no vacío y alfanumérico
    return ctype_alnum(str_replace([' ', '-', '_'], '', $parroquia));
}

/**
 * Valida que el sector sea válido (básica, puede expandirse según necesidades)
 */
function validarSector($sector) {
    if (empty($sector)) {
        return false;
    }
    // Validación básica: no vacío y alfanumérico
    return ctype_alnum(str_replace([' ', '-', '_'], '', $sector));
}

// 3) Si es AJAX de continuar_entrega, procesamos y devolvemos JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['continuar_entrega'])) {
    header('Content-Type: application/json');

    // Obtener datos para validación
    $delivery = new Delivery();
    $delivery_activos = $delivery->consultarActivos();
    $metodoEntrega = new MetodoEntrega();
    $metodos_entrega = $metodoEntrega->consultarTodosActivos();

    $me = $_POST['metodo_entrega'] ?? null;
    $empresa_envio = $_POST['empresa_envio'] ?? null;
    
    // Validar metodo_entrega
    if (!validarMetodoEntrega($me)) {
        echo json_encode(['success'=>false,'message'=>'Método de entrega inválido.']);
        exit;
    }

    if ($me == '2' && $empresa_envio == '3') {
        $me = '3';
    }

    // Construye array de entrega
    $entrega = ['id_metodoentrega' => $me];
    switch ($me) {
        case '4': // Tienda física
            $entrega['direccion_envio'] = $_POST['direccion_envio'] ?? '';
            $entrega['sucursal_envio']  = null;
            break;
        case '2': // MRW
            if (empty($_POST['empresa_envio']) || empty($_POST['sucursal_envio'])) {
                echo json_encode(['success'=>false,'message'=>'Complete empresa y sucursal.']);
                exit;
            }
            // Validar empresa_envio
            if (!validarEmpresaEnvio($_POST['empresa_envio'])) {
                echo json_encode(['success'=>false,'message'=>'Empresa de envío inválida.']);
                exit;
            }
            $entrega['empresa_envio']   = $_POST['empresa_envio'];
            $entrega['sucursal_envio']  = $_POST['sucursal_envio'];
            $entrega['direccion_envio'] = $_POST['direccion_envio'];
            break;

        case '3': //ZOOM
                if (empty($_POST['empresa_envio']) || empty($_POST['sucursal_envio'])) {
                    echo json_encode(['success'=>false,'message'=>'Complete empresa y sucursal.']);
                    exit;
                }
                // Validar empresa_envio
                if (!validarEmpresaEnvio($_POST['empresa_envio'])) {
                    echo json_encode(['success'=>false,'message'=>'Empresa de envío inválida.']);
                    exit;
                }
                $entrega['empresa_envio']   = $_POST['empresa_envio'];
                $entrega['sucursal_envio']  = $_POST['sucursal_envio'];
                $entrega['direccion_envio'] = $_POST['direccion_envio'];
                break;
     

        case '1': // Delivery propio

            if (empty($_POST['id_delivery'])) {
                echo json_encode(['success'=>false,'message'=>'Debe seleccionar un delivery.']);
                exit;
            }
            // Validar id_delivery
            if (!validarIdDelivery($_POST['id_delivery'], $delivery_activos)) {
                echo json_encode(['success'=>false,'message'=>'El delivery seleccionado no es válido.']);
                exit;
            }
                foreach (['zona','parroquia','sector','direccion_envio'] as $f) {
                    if (empty($_POST[$f])) {
                        echo json_encode(['success'=>false,'message'=>"Falta el campo $f."]);
                        exit;
                    }
                }
            
                // Los valores individuales
                $zona      = trim($_POST['zona']);
                $parroquia = trim($_POST['parroquia']);
                $sector    = trim($_POST['sector']);
                $dirDetall = trim($_POST['direccion_envio']);

                // Validar zona
                if (!validarZona($zona)) {
                    echo json_encode(['success'=>false,'message'=>'La zona seleccionada no es válida.']);
                    exit;
                }

                // Validar parroquia
                if (!validarParroquia($parroquia)) {
                    echo json_encode(['success'=>false,'message'=>'La parroquia no es válida.']);
                    exit;
                }

                // Validar sector
                if (!validarSector($sector)) {
                    echo json_encode(['success'=>false,'message'=>'El sector no es válido.']);
                    exit;
                }

            
                // Concatenamos en una sola dirección
                $entrega['direccion_envio'] = "Zona: {$zona}, Parroquia: {$parroquia}, Sector: {$sector}, Dirección: {$dirDetall}";

              $entrega['id_delivery'] = $_POST['id_delivery'];
              $entrega['delivery_nombre'] = $_POST['delivery_nombre'];
              $entrega['delivery_tipo'] = $_POST['delivery_tipo'];
              $entrega['delivery_contacto'] = $_POST['delivery_contacto'];
              
                // Para uniformidad, dejamos nulo el campo sucursal_envio
                $entrega['sucursal_envio'] = null;
                break;
    }

    // Guardar en sesión
    $_SESSION['pedido_entrega'] = $entrega;

    // Responder JSON
    echo json_encode([
        'success'  => true,
        'message'  => 'Datos de entrega guardados.',
        'redirect' => '?pagina=Pedidopago'
    ]);
    exit;
}

$delivery = new Delivery();
$delivery_activos = $delivery->consultarActivos();

$metodoEntrega = new MetodoEntrega();
$metodos_entrega = $metodoEntrega->consultarTodosActivos();

// 4) Si llegamos aquí, no es AJAX: preparamos la vista


// Incluimos la vista. Dentro de ella tendrás disponible $metodos_entrega
require_once __DIR__ . '/../vista/tienda/Pedidoentrega.php';
