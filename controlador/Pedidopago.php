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

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el banco sea válido (lista de bancos permitidos)
 */
function validarBanco($banco) {
    if (empty($banco)) {
        return false;
    }
    $bancos_validos = [
        '0102-Banco De Venezuela',
        '0156-100% Banco ',
        '0172-Bancamiga Banco Universal,C.A',
        '0114-Bancaribe',
        '0171-Banco Activo',
        '0166-Banco Agricola De Venezuela',
        '0128-Bancon Caroni',
        '0163-Banco Del Tesoro',
        '0175-Banco Digital De Los Trabajadores, Banco Universal',
        '0115-Banco Exterior',
        '0151-Banco Fondo Comun',
        '0173-Banco Internacional De Desarrollo',
        '0105-Banco Mercantil',
        '0191-Banco Nacional De Credito',
        '0138-Banco Plaza',
        '0137-Banco Sofitasa',
        '0104-Banco Venezolano De Credito',
        '0168-Bancrecer',
        '0134-Banesco',
        '0177-Banfanb',
        '0146-Bangente',
        '0174-Banplus',
        '0108-BBVA Provincial',
        '0157-Delsur Banco Universal',
        '0601-Instituto Municipal De Credito Popular',
        '0178-N58 Banco Digital Banco Microfinanciero S.A',
        '0169-R4 Banco Microfinanciero C.A.'
    ];
    return in_array($banco, $bancos_validos, true);
}

/**
 * Valida que el banco_destino sea válido (solo 2 opciones permitidas)
 */
function validarBancoDestino($banco_destino) {
    if (empty($banco_destino)) {
        return false;
    }
    $bancos_destino_validos = [
        '0102-Banco De Venezuela',
        '0105-Banco Mercantil'
    ];
    return in_array($banco_destino, $bancos_destino_validos, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['continuar_pago'])) {
    header('Content-Type: application/json');

    // Asegurar existencia de entrega y carrito
    if (empty($_SESSION['pedido_entrega']) || empty($_SESSION['carrito'])) {
        echo json_encode(['success'=>false,'message'=>'Falta información de envío o carrito vacío.']);
        exit;
    }

    // Validar banco
    if (!empty($_POST['banco']) && !validarBanco($_POST['banco'])) {
        echo json_encode(['success' => false, 'message' => 'El banco de origen seleccionado no es válido']);
        exit;
    }

    // Validar banco_destino
    if (!empty($_POST['banco_destino']) && !validarBancoDestino($_POST['banco_destino'])) {
        echo json_encode(['success' => false, 'message' => 'El banco de destino seleccionado no es válido']);
        exit;
    }

    // Construir payload para procesarPedido
    $datos = [
        'operacion' => 'registrar_pedido',
        'datos' => [
            // datos básicos
            'id_persona'       => $_SESSION['id'],
            'tipo'              => '2',
            'fecha'             => $_POST['fecha'] ?? date('Y-m-d h:i A'),
            'estado'            => '1',
            // totales
            'precio_total_usd'  => $_POST['precio_total_usd'],
            'precio_total_bs'   => $_POST['precio_total_bs'],
            // entrega
            'id_metodoentrega'  => $_POST['id_metodoentrega'],
            'direccion_envio'   => $_POST['direccion_envio'] ?? '',
            'sucursal_envio'    => $_POST['sucursal_envio'] ?? '',
            'id_delivery' => $_SESSION['pedido_entrega']['id_delivery'] ?? null,
            // pago
            'id_metodopago'       => $_POST['id_metodopago'] ?? '',
            'referencia_bancaria' => $_POST['referencia_bancaria'] ?? '',
            'telefono_emisor'     => $_POST['telefono_emisor'] ?? '',
            'banco_destino'       => $_POST['banco_destino'] ?? '',
            'banco'               => $_POST['banco'] ?? '',
            'monto'               => $_POST['precio_total_bs'],
            'monto_usd'           => $_POST['precio_total_usd'],
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