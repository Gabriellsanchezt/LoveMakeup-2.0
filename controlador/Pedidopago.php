<?php  

use LoveMakeup\Proyecto\Modelo\VentaWeb;

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

if (empty($_SESSION['id'])) {
    header('Location:?pagina=login');
    exit;
}

$venta = new VentaWeb();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['continuar_pago'])) {
    header('Content-Type: application/json');

    // Asegurar existencia de entrega y carrito
    if (empty($_SESSION['pedido_entrega']) || empty($_SESSION['carrito'])) {
        echo json_encode(['success'=>false,'message'=>'Falta información de envío o carrito vacío.']);
        exit;
    }

    // Sanitizar y validar banco
    $banco = $venta->sanitizarString($_POST['banco'] ?? '', 100);
    if (empty($banco) || !$venta->validarBanco($banco)) {
        echo json_encode(['success' => false, 'message' => 'El banco de origen seleccionado no es válido']);
        exit;
    }

    // Sanitizar y validar banco_destino
    $banco_destino = $venta->sanitizarString($_POST['banco_destino'] ?? '', 100);
    if (empty($banco_destino) || !$venta->validarBancoDestino($banco_destino)) {
        echo json_encode(['success' => false, 'message' => 'El banco de destino seleccionado no es válido']);
        exit;
    }

    // Sanitizar referencia bancaria
    $referencia_bancaria = $venta->sanitizarString($_POST['referencia_bancaria'] ?? '', 50);
    if (!empty($referencia_bancaria) && !$venta->validarReferenciaBancaria($referencia_bancaria)) {
        echo json_encode(['success' => false, 'message' => 'La referencia bancaria no es válida']);
        exit;
    }

    // Sanitizar teléfono emisor
    $telefono_emisor = $venta->sanitizarString($_POST['telefono_emisor'] ?? '', 20);
    if (!empty($telefono_emisor) && !$venta->validarTelefono($telefono_emisor)) {
        echo json_encode(['success' => false, 'message' => 'El teléfono emisor no es válido']);
        exit;
    }

    // Sanitizar dirección de envío
    $direccion_envio = $venta->sanitizarDireccion($_POST['direccion_envio'] ?? '');
    
    // Sanitizar sucursal
    $sucursal_envio = $venta->sanitizarSucursal($_POST['sucursal_envio'] ?? '');

    // Sanitizar números
    $id_persona = $venta->sanitizarEntero($_SESSION['id'] ?? null, 1);
    if ($id_persona === null) {
        echo json_encode(['success' => false, 'message' => 'El ID de persona no es válido']);
        exit;
    }

    $precio_total_usd = $venta->sanitizarDecimal($_POST['precio_total_usd'] ?? null, 0);
    $precio_total_bs = $venta->sanitizarDecimal($_POST['precio_total_bs'] ?? null, 0);

    // Sanitizar id_metodoentrega
    $id_metodoentrega = $venta->sanitizarEntero($_POST['id_metodoentrega'] ?? null, 1);
    if ($id_metodoentrega === null) {
        echo json_encode(['success' => false, 'message' => 'El método de entrega no es válido']);
        exit;
    }

    // Sanitizar id_metodopago
    $id_metodopago = $venta->sanitizarEntero($_POST['id_metodopago'] ?? null, 1);
    if ($id_metodopago === null) {
        echo json_encode(['success' => false, 'message' => 'El método de pago no es válido']);
        exit;
    }

    // Sanitizar id_delivery de sesión
    $id_delivery = null;
    if (!empty($_SESSION['pedido_entrega']['id_delivery'])) {
        $id_delivery = $venta->sanitizarEntero($_SESSION['pedido_entrega']['id_delivery'], 1);
    }

    // Construir payload para procesarPedido (usando valores sanitizados)
    $datos = [
        'operacion' => 'registrar_pedido',
        'datos' => [
            // datos básicos
            'id_persona'       => $id_persona,
            'tipo'              => '2',
            'fecha'             => date('Y-m-d h:i A'),
            'estado'            => '1',
            // totales
            'precio_total_usd'  => $precio_total_usd ?? 0,
            'precio_total_bs'   => $precio_total_bs ?? 0,
            // entrega
            'id_metodoentrega'  => $id_metodoentrega,
            'direccion_envio'   => $direccion_envio,
            'sucursal_envio'    => $sucursal_envio,
            'id_delivery'       => $id_delivery,
            // pago
            'id_metodopago'       => $id_metodopago,
            'referencia_bancaria' => $referencia_bancaria,
            'telefono_emisor'     => $telefono_emisor,
            'banco_destino'       => $banco_destino,
            'banco'               => $banco,
            'monto'               => $precio_total_bs ?? 0,
            'monto_usd'           => $precio_total_usd ?? 0,
            'imagen'              => '' // se setea abajo
        ]
    ];

    // Manejo de imagen
    if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $name = uniqid('img_').".$ext";
        $dest = __DIR__ . '/../assets/img/captures/' . $name;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
            $datos['datos']['imagen'] = 'assets/img/captures/'.$name;
        }
    }

    // Carrito
    $datos['datos']['carrito'] = $_SESSION['carrito'];

    // Procesar
    $res = $venta->procesarPedido(json_encode($datos));

    if ($res['success']) {
        // Limpiar sesión
        unset($_SESSION['carrito'], $_SESSION['pedido_entrega']);
        echo json_encode([
            'success'  => true,
            'message'  => 'Pago realizado en espera de Verificacion.',
            'redirect' => '?pagina=confirmacion&id='.$res['id_pedido']
        ]);
    } else {
        echo json_encode(['success'=>false,'message'=>$res['message']]);
    }
    exit;
}

// Si no es POST AJAX, redirigir al carrito
require_once __DIR__ . '/../vista/tienda/Pedidopago.php';

?>