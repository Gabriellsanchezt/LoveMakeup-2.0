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
 * Sanitiza un número decimal
 */
function sanitizarDecimal($valor, $min = null, $max = null) {
    if (!is_numeric($valor)) {
        return null;
    }
    $valor = (float)$valor;
    if ($min !== null && $valor < $min) {
        return null;
    }
    if ($max !== null && $valor > $max) {
        return null;
    }
    return $valor;
}

/**
 * Valida formato de referencia bancaria (solo números y guiones)
 */
function validarReferenciaBancaria($referencia) {
    if (empty($referencia)) {
        return false;
    }
    // Solo números, guiones y espacios
    if (!preg_match('/^[0-9\-\s]+$/', $referencia)) {
        return false;
    }
    // Longitud máxima
    if (strlen($referencia) > 50) {
        return false;
    }
    return true;
}

/**
 * Valida formato de teléfono
 */
function validarTelefono($telefono) {
    if (empty($telefono)) {
        return false;
    }
    // Solo números, guiones, espacios y paréntesis
    if (!preg_match('/^[0-9\-\s\(\)]+$/', $telefono)) {
        return false;
    }
    // Longitud entre 7 y 15 caracteres
    $longitud = strlen(preg_replace('/[^0-9]/', '', $telefono));
    if ($longitud < 7 || $longitud > 15) {
        return false;
    }
    return true;
}

/**
 * Valida y sanitiza dirección de envío
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
 * Valida y sanitiza sucursal (solo alfanuméricos y guiones)
 */
function sanitizarSucursal($sucursal) {
    if (empty($sucursal)) {
        return '';
    }
    
    // Detectar inyección SQL
    if (detectarInyeccionSQL($sucursal)) {
        return '';
    }
    
    $sucursal = trim($sucursal);
    // Solo alfanuméricos, guiones y espacios
    if (!preg_match('/^[a-zA-Z0-9\-\s]+$/', $sucursal)) {
        return '';
    }
    // Longitud máxima
    if (strlen($sucursal) > 100) {
        $sucursal = substr($sucursal, 0, 100);
    }
    return htmlspecialchars($sucursal, ENT_QUOTES, 'UTF-8');
}

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

    // Sanitizar y validar banco
    $banco = sanitizarString($_POST['banco'] ?? '', 100);
    if (empty($banco) || !validarBanco($banco)) {
        echo json_encode(['success' => false, 'message' => 'El banco de origen seleccionado no es válido']);
        exit;
    }

    // Sanitizar y validar banco_destino
    $banco_destino = sanitizarString($_POST['banco_destino'] ?? '', 100);
    if (empty($banco_destino) || !validarBancoDestino($banco_destino)) {
        echo json_encode(['success' => false, 'message' => 'El banco de destino seleccionado no es válido']);
        exit;
    }

    // Sanitizar referencia bancaria
    $referencia_bancaria = sanitizarString($_POST['referencia_bancaria'] ?? '', 50);
    if (!empty($referencia_bancaria) && !validarReferenciaBancaria($referencia_bancaria)) {
        echo json_encode(['success' => false, 'message' => 'La referencia bancaria no es válida']);
        exit;
    }

    // Sanitizar teléfono emisor
    $telefono_emisor = sanitizarString($_POST['telefono_emisor'] ?? '', 20);
    if (!empty($telefono_emisor) && !validarTelefono($telefono_emisor)) {
        echo json_encode(['success' => false, 'message' => 'El teléfono emisor no es válido']);
        exit;
    }

    // Sanitizar dirección de envío
    $direccion_envio = sanitizarDireccion($_POST['direccion_envio'] ?? '');
    
    // Sanitizar sucursal
    $sucursal_envio = sanitizarSucursal($_POST['sucursal_envio'] ?? '');

    // Sanitizar números
    $id_persona = sanitizarEntero($_SESSION['id'] ?? null, 1);
    if ($id_persona === null) {
        echo json_encode(['success' => false, 'message' => 'El ID de persona no es válido']);
        exit;
    }

    $precio_total_usd = sanitizarDecimal($_POST['precio_total_usd'] ?? null, 0);
    $precio_total_bs = sanitizarDecimal($_POST['precio_total_bs'] ?? null, 0);

    // Sanitizar id_metodoentrega
    $id_metodoentrega = sanitizarEntero($_POST['id_metodoentrega'] ?? null, 1);
    if ($id_metodoentrega === null) {
        echo json_encode(['success' => false, 'message' => 'El método de entrega no es válido']);
        exit;
    }

    // Sanitizar id_metodopago
    $id_metodopago = sanitizarEntero($_POST['id_metodopago'] ?? null, 1);
    if ($id_metodopago === null) {
        echo json_encode(['success' => false, 'message' => 'El método de pago no es válido']);
        exit;
    }

    // Sanitizar id_delivery de sesión
    $id_delivery = null;
    if (!empty($_SESSION['pedido_entrega']['id_delivery'])) {
        $id_delivery = sanitizarEntero($_SESSION['pedido_entrega']['id_delivery'], 1);
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