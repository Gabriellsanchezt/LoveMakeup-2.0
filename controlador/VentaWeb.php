<?php

use LoveMakeup\Proyecto\Modelo\VentaWeb;
use LoveMakeup\Proyecto\Modelo\MetodoPago;
use LoveMakeup\Proyecto\Modelo\MetodoEntrega;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
}
// Verificar sesión y definir variable para la vista
$sesion_activa = isset($_SESSION['id']) && !empty($_SESSION['id']);

if (!$sesion_activa) {
    header("Location: ?pagina=login");
    exit;
}

// Verificar carrito
if (empty($_SESSION['carrito'])) {
    require_once 'vista/complementos/carritovacio.php';
    exit;
}

$venta = new VentaWeb();

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el id_metodopago sea válido y exista en la base de datos
 */
function validarIdMetodoPago($id_metodopago, $metodos_pago) {
    if (empty($id_metodopago) || !is_numeric($id_metodopago)) {
        return false;
    }
    $id_metodopago = (int)$id_metodopago;
    foreach ($metodos_pago as $metodo) {
        if ($metodo['id_metodopago'] == $id_metodopago && $metodo['estatus'] == 1) {
            return true;
        }
    }
    return false;
}

/**
 * Valida que el id_metodoentrega sea válido y exista en la base de datos
 */
function validarIdMetodoEntrega($id_metodoentrega, $metodos_entrega) {
    if (empty($id_metodoentrega) || !is_numeric($id_metodoentrega)) {
        return false;
    }
    $id_metodoentrega = (int)$id_metodoentrega;
    foreach ($metodos_entrega as $metodo) {
        if ($metodo['id_entrega'] == $id_metodoentrega && $metodo['estatus'] == 1) {
            return true;
        }
    }
    return false;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar cualquier salida previa
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    
    try {
        // Obtener métodos válidos para validación
        $metodos_pago = $venta->obtenerMetodosPago();
        $metodos_entrega = $venta->obtenerMetodosEntrega();

        // Validar id_metodopago
        if (!empty($_POST['id_metodopago']) && !validarIdMetodoPago($_POST['id_metodopago'], $metodos_pago)) {
            die(json_encode(['success' => false, 'message' => 'El método de pago seleccionado no es válido']));
        }

        // Validar id_metodoentrega
        if (!empty($_POST['id_metodoentrega']) && !validarIdMetodoEntrega($_POST['id_metodoentrega'], $metodos_entrega)) {
            die(json_encode(['success' => false, 'message' => 'El método de entrega seleccionado no es válido']));
        }

        // Validar banco
        if (!empty($_POST['banco']) && !validarBanco($_POST['banco'])) {
            die(json_encode(['success' => false, 'message' => 'El banco de origen seleccionado no es válido']));
        }

        // Validar banco_destino
        if (!empty($_POST['banco_destino']) && !validarBancoDestino($_POST['banco_destino'])) {
            die(json_encode(['success' => false, 'message' => 'El banco de destino seleccionado no es válido']));
        }

        $rutaImagen = null;
if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nuevoNombre = uniqid('img_') . ".$ext";
    $destino = __DIR__ . '/../assets/img/captures/' . $nuevoNombre;
    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
        die(json_encode(['success'=>false,'message'=>'Error al guardar la imagen.']));
    }
    $rutaImagen = 'assets/img/captures/' . $nuevoNombre;
}

        // Preparar datos del pedido
        $datosPedido = [
            'operacion' => 'registrar_pedido',
            'datos' => [
              
                'tipo'                => $_POST['tipo'] ?? '',
                'fecha'               => $_POST['fecha'] ?? date('Y-m-d h:i A'),
                'estado'              => $_POST['estado'] ?? 'pendiente',
                'precio_total_usd'    => $_POST['precio_total_usd'] ?? '',
                'precio_total_bs'     => $_POST['precio_total_bs'] ?? '',
                'id_persona'          => $_POST['id_persona'] ?? '',
            
                // **Pago**
                'id_metodopago'       => $_POST['id_metodopago'] ?? '',
                'referencia_bancaria' => $_POST['referencia_bancaria'] ?? '',
                'telefono_emisor'     => $_POST['telefono_emisor'] ?? '',
                'banco_destino'       => $_POST['banco_destino'] ?? '',
                'banco'               => $_POST['banco'] ?? '',
                'monto'               => $_POST['monto'] ?? '',
                'monto_usd'           => $_POST['monto_usd'] ?? '',
                'imagen'            =>$rutaImagen,
            
                // **Dirección**
                  'id_delivery'      => $_SESSION['pedido_entrega']['id_delivery'] ?? null,
                'direccion_envio'     => $_POST['direccion_envio'] ?? '',
                'sucursal_envio'      => $_POST['sucursal_envio'] ?? '',
                'id_metodoentrega'    => $_POST['id_metodoentrega'] ?? '',
            
                // Carrito
                'carrito'             => $_SESSION['carrito'] ?? []
            ]
        ];

        // Procesar el pedido
        $resultado = $venta->procesarPedido(json_encode($datosPedido));
        
        // Si el pedido se registró correctamente, vaciar el carrito.
        if ($resultado['success'] && $resultado['id_pedido']) {
            unset($_SESSION['carrito']);
        }
        
        // Asegurarse de que no haya nada antes del JSON
        die(json_encode($resultado));
    } catch (\Exception $e) {
        // Asegurarse de que no haya nada antes del JSON
        die(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
}

// Datos para la vista
$nombre = $_SESSION['nombre'] ?? 'Estimado Cliente';
$apellido = $_SESSION['apellido'] ?? '';
$nombreCompleto = trim("$nombre $apellido");
$metodos_pago = $venta->obtenerMetodosPago();
$metodos_entrega = $venta->obtenerMetodosEntrega();
$carrito = $_SESSION['carrito'] ?? [];
$total = 0;

// Calcular total
foreach ($carrito as $item) {
    $cantidad = $item['cantidad'];
    $precioUnitario = $cantidad >= $item['cantidad_mayor'] ? $item['precio_mayor'] : $item['precio_detal'];
    $total += $cantidad * $precioUnitario;
}

require_once 'vista/tienda/verpedidoweb.php';
