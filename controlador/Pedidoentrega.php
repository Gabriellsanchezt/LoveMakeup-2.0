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


// 3) Si es AJAX de continuar_entrega, procesamos y devolvemos JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['continuar_entrega'])) {
    error_log("Datos recibidos: " . json_encode($_POST));
    header('Content-Type: application/json');

    // Obtener datos para validación
    $delivery = new Delivery();
    $delivery_activos = $delivery->consultarActivos();
    $metodoEntrega = new MetodoEntrega();
    $metodos_entrega = $metodoEntrega->consultarTodosActivos();

    // Sanitizar metodo_entrega
    $me = $venta->sanitizarEntero($_POST['metodo_entrega'] ?? null, 1, 4);
    if (!$me || !$venta->validarMetodoEntrega($me)) {
        echo json_encode(['success'=>false,'message'=>'Método de entrega inválido.']);
        exit;
    }

    // Sanitizar empresa_envio si existe
    $empresa_envio = !empty($_POST['empresa_envio']) ? $venta->sanitizarEntero($_POST['empresa_envio'], 1, 10) : null;

    if ($me == 2 && $empresa_envio == 3) {
        $me = 3;
    }

    // Construye array de entrega
    $entrega = ['id_metodoentrega' => $me];
    switch ($me) {
        case 4: // Tienda física
            $entrega['direccion_envio'] = $venta->sanitizarDireccion($_POST['direccion_envio'] ?? '');
            $entrega['sucursal_envio']  = null;
            break;
        case 2: // MRW
            if (empty($_POST['empresa_envio']) || empty($_POST['sucursal_envio'])) {
                echo json_encode(['success'=>false,'message'=>'Complete empresa y sucursal.']);
                exit;
            }
            // Sanitizar y validar empresa_envio
            $empresa_envio = $venta->sanitizarEntero($_POST['empresa_envio'], 1, 10);
            if (!$empresa_envio || !$venta->validarEmpresaEnvio($empresa_envio)) {
                echo json_encode(['success'=>false,'message'=>'Empresa de envío inválida.']);
                exit;
            }
            $entrega['empresa_envio']   = $empresa_envio;
            $entrega['sucursal_envio']  = $venta->sanitizarSucursal($_POST['sucursal_envio']);
          $direccion = $venta->sanitizarDireccion($_POST['direccion_envio'] ?? '');

if(!$direccion){
    echo json_encode(['success'=>false,'message'=>'Nombre de Sucursal inválida']);
    exit;
}

$entrega['direccion_envio'] = $direccion;
            break;

        case 3: //ZOOM
                if (empty($_POST['empresa_envio']) || empty($_POST['sucursal_envio'])) {
                    echo json_encode(['success'=>false,'message'=>'Complete empresa y sucursal.']);
                    exit;
                }
                // Sanitizar y validar empresa_envio
                $empresa_envio = $venta->sanitizarEntero($_POST['empresa_envio'], 1, 10);
                if (!$empresa_envio || !$venta->validarEmpresaEnvio($empresa_envio)) {
                    echo json_encode(['success'=>false,'message'=>'Empresa de envío inválida.']);
                    exit;
                }
                $entrega['empresa_envio']   = $empresa_envio;
                $entrega['sucursal_envio']  = $venta->sanitizarSucursal($_POST['sucursal_envio']);
                $direccion = $venta->sanitizarDireccion($_POST['direccion_envio'] ?? '');

                if(!$direccion){
                    echo json_encode(['success'=>false,'message'=>'Nombre de Sucursal  inválida']);
                    exit;
                }
                
                $entrega['direccion_envio'] = $direccion;
                break;
     

                case 1: // Delivery propio

                    if (empty($_POST['id_delivery'])) {
                        echo json_encode(['success'=>false,'message'=>'Debe seleccionar un delivery.']);
                        exit;
                    }
                
                    // Sanitizar y validar id_delivery
                    $id_delivery = $venta->sanitizarEntero($_POST['id_delivery'], 1);
                
                    if (!$id_delivery || !$venta->validarIdDelivery($id_delivery, $delivery_activos)) {
                        echo json_encode(['success'=>false,'message'=>'El delivery seleccionado no es válido.']);
                        exit;
                    }
                
                    // Verificar campos obligatorios
                    foreach (['zona','parroquia','sector','direccion_envio'] as $f) {
                        if (empty($_POST[$f])) {
                            echo json_encode(['success'=>false,'message'=>"Falta el campo $f."]);
                            exit;
                        }
                    }
                
                    // Sanitizar campos
                    $zona = $venta->sanitizarString($_POST['zona'], 50);
                    $parroquia = $venta->sanitizarString($_POST['parroquia'], 100);
                    $sector = $venta->sanitizarString($_POST['sector'], 100);
                    $dirDetall = $venta->sanitizarDireccion($_POST['direccion_envio']);
                
                    // Validar sanitización
                    if (!$zona) {
                        echo json_encode(['success'=>false,'message'=>'Zona inválida']);
                        exit;
                    }
                
                    if (!$parroquia) {
                        echo json_encode(['success'=>false,'message'=>'Parroquia inválida']);
                        exit;
                    }
                
                    if (!$sector) {
                        echo json_encode(['success'=>false,'message'=>'Sector inválido']);
                        exit;
                    }
                
                    if (!$dirDetall) {
                        echo json_encode(['success'=>false,'message'=>'Dirección inválida']);
                        exit;
                    }
                
                    // Validaciones de catálogo
                    if (!$venta->validarZona($zona)) {
                        echo json_encode(['success'=>false,'message'=>'La zona seleccionada no es válida.']);
                        exit;
                    }
                
                    if (!$venta->validarParroquia($parroquia)) {
                        echo json_encode(['success'=>false,'message'=>'La parroquia no es válida.']);
                        exit;
                    }
                
                    if (!$venta->validarSector($sector)) {
                        echo json_encode(['success'=>false,'message'=>'El sector no es válido.']);
                        exit;
                    }
                
                    // Concatenar dirección final
                    $entrega['direccion_envio'] = "Zona: {$zona}, Parroquia: {$parroquia}, Sector: {$sector}, Dirección: {$dirDetall}";
                
                    // Datos del delivery
                    $delivery_nombre = $venta->sanitizarString($_POST['delivery_nombre'] ?? '', 100);
                    $delivery_tipo = $venta->sanitizarString($_POST['delivery_tipo'] ?? '', 50);
                    $delivery_contacto = $venta->sanitizarString($_POST['delivery_contacto'] ?? '', 50);
                
                    if (!$delivery_nombre || !$delivery_tipo || !$delivery_contacto) {
                        echo json_encode(['success'=>false,'message'=>'Datos del delivery inválidos.']);
                        exit;
                    }
                
                    $entrega['id_delivery'] = $id_delivery;
                    $entrega['delivery_nombre'] = $delivery_nombre;
                    $entrega['delivery_tipo'] = $delivery_tipo;
                    $entrega['delivery_contacto'] = $delivery_contacto;
                
                    // Uniformidad con los otros métodos
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
