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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar cualquier salida previa
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    
    try {
        // Obtener métodos válidos para validación
        $metodos_pago = $venta->obtenerMetodosPago();
        $metodos_entrega = $venta->obtenerMetodosEntrega();

        // Sanitizar y validar id_metodopago
        $id_metodopago = $venta->sanitizarEntero($_POST['id_metodopago'] ?? null, 1);
        if (!$id_metodopago || !validarIdMetodoPago($id_metodopago, $metodos_pago)) {
            die(json_encode(['success' => false, 'message' => 'El método de pago seleccionado no es válido']));
        }

        // Sanitizar y validar id_metodoentrega
        $id_metodoentrega = $venta->sanitizarEntero($_POST['id_metodoentrega'] ?? null, 1);
        if (!$id_metodoentrega ||$venta->validarIdMetodoEntrega($id_metodoentrega, $metodos_entrega)) {
            die(json_encode(['success' => false, 'message' => 'El método de entrega seleccionado no es válido']));
        }

        // Sanitizar y validar banco
        $banco = $venta-> sanitizarString($_POST['banco'] ?? '', 100);
        if (empty($banco) ||$venta->validarBanco($banco)) {
            die(json_encode(['success' => false, 'message' => 'El banco de origen seleccionado no es válido']));
        }

        // Sanitizar y validar banco_destino
        $banco_destino = $venta-> sanitizarString($_POST['banco_destino'] ?? '', 100);
        if (empty($banco_destino) || $venta->validarBancoDestino($banco_destino)) {
            die(json_encode(['success' => false, 'message' => 'El banco de destino seleccionado no es válido']));
        }

        // Sanitizar referencia bancaria
        $referencia_bancaria = $venta-> sanitizarString($_POST['referencia_bancaria'] ?? '', 50);
        if (!empty($referencia_bancaria) && $venta->validarReferenciaBancaria($referencia_bancaria)) {
            die(json_encode(['success' => false, 'message' => 'La referencia bancaria no es válida']));
        }

        // Sanitizar teléfono emisor
        $telefono_emisor =  $venta->sanitizarString($_POST['telefono_emisor'] ?? '', 20);
        if (!empty($telefono_emisor) && $venta->validarTelefono($telefono_emisor)) {
            die(json_encode(['success' => false, 'message' => 'El teléfono emisor no es válido']));
        }

        // Sanitizar dirección de envío
        $direccion_envio = $venta->sanitizarDireccion($_POST['direccion_envio'] ?? '');
        
        // Sanitizar sucursal
        $sucursal_envio = $venta->sanitizarSucursal($_POST['sucursal_envio'] ?? '');

        // Sanitizar y validar campos de texto
        $referencia_bancaria = !empty($_POST['referencia_bancaria']) ? $venta->sanitizarString($_POST['referencia_bancaria'], 50) : '';
        if (!empty($referencia_bancaria) && $venta->validarReferenciaBancaria($referencia_bancaria)) {
            die(json_encode(['success' => false, 'message' => 'La referencia bancaria no es válida']));
        }

        $telefono_emisor = !empty($_POST['telefono_emisor']) ? $venta->sanitizarString($_POST['telefono_emisor'], 15) : '';
        if (!empty($telefono_emisor) && $venta->validarTelefono($telefono_emisor)) {
            die(json_encode(['success' => false, 'message' => 'El teléfono emisor no es válido']));
        }

        $direccion_envio = !empty($_POST['direccion_envio']) ? $venta->sanitizarDireccion($_POST['direccion_envio']) : '';
        $sucursal_envio = !empty($_POST['sucursal_envio']) ? $venta->sanitizarSucursal($_POST['sucursal_envio']) : '';

        // Sanitizar números
        $id_persona = !empty($_POST['id_persona']) ? $venta->sanitizarEntero($_POST['id_persona'], 1) : null;
        if ($id_persona === null) {
            die(json_encode(['success' => false, 'message' => 'El ID de persona no es válido']));
        }

        $precio_total_usd = !empty($_POST['precio_total_usd']) ? $venta->sanitizarDecimal($_POST['precio_total_usd'], 0) : null;
        $precio_total_bs = !empty($_POST['precio_total_bs']) ? $venta->sanitizarDecimal($_POST['precio_total_bs'], 0) : null;

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

        // Preparar datos del pedido (usando valores sanitizados)
        $datosPedido = [
            'operacion' => 'registrar_pedido',
            'datos' => [
              
                'tipo'                => $venta->sanitizarEntero($_POST['tipo'] ?? '2', 1, 10) ?? 2,
                'fecha'               => date('Y-m-d h:i A'),
                'estado'              => $venta->sanitizarEntero($_POST['estado'] ?? '1', 1, 5) ?? 1,
                'precio_total_usd'    => $precio_total_usd ?? 0,
                'precio_total_bs'     => $precio_total_bs ?? 0,
                'id_persona'          => $id_persona,
            
                // **Pago**
                'id_metodopago'       => $venta->sanitizarEntero($_POST['id_metodopago'] ?? '', 1) ?? '',
                'referencia_bancaria' => $referencia_bancaria,
                'telefono_emisor'     => $telefono_emisor,
                'banco_destino'       => $venta->sanitizarString($_POST['banco_destino'] ?? '', 100),
                'banco'               => $venta->sanitizarString($_POST['banco'] ?? '', 100),
                'monto'               => $precio_total_bs ?? 0,
                'monto_usd'           => $precio_total_usd ?? 0,
                'imagen'              => $rutaImagen,
            
                // **Dirección**
                'id_delivery'         => !empty($_SESSION['pedido_entrega']['id_delivery']) ? $venta->sanitizarEntero($_SESSION['pedido_entrega']['id_delivery'], 1) : null,
                'direccion_envio'     => $direccion_envio,
                'sucursal_envio'      => $sucursal_envio,
                'id_metodoentrega'    => $venta->sanitizarEntero($_POST['id_metodoentrega'] ?? '', 1) ?? '',
            
                // Carrito (validar estructura del carrito)
                'carrito'             => $venta->validarCarrito($_SESSION['carrito'] ?? [])
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
