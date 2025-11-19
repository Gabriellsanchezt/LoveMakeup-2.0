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
        '/(\bwaitfor\b.*\bdelay\b)/i',
        '/(\bchar\s*\(/i',
        '/(\bcast\s*\(/i',
        '/(\bconvert\s*\(/i'
    ];
    
    foreach ($patrones_peligrosos as $patron) {
        if (preg_match($patron, $valor_lower)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Sanitiza un string eliminando caracteres peligrosos para SQL
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
    
    // Escapar caracteres especiales
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida y sanitiza un número entero
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
 * Valida y sanitiza un número decimal
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

/**
 * Valida la estructura del carrito para prevenir inyecciones
 */
function validarCarrito($carrito) {
    if (!is_array($carrito)) {
        return [];
    }
    
    $carrito_validado = [];
    foreach ($carrito as $item) {
        if (!is_array($item)) {
            continue;
        }
        
        // Validar y sanitizar cada campo del item
        $item_validado = [
            'id' => sanitizarEntero($item['id'] ?? null, 1),
            'cantidad' => sanitizarEntero($item['cantidad'] ?? null, 1),
            'cantidad_mayor' => sanitizarEntero($item['cantidad_mayor'] ?? null, 1),
            'precio_detal' => sanitizarDecimal($item['precio_detal'] ?? null, 0),
            'precio_mayor' => sanitizarDecimal($item['precio_mayor'] ?? null, 0)
        ];
        
        // Solo agregar si todos los campos son válidos
        if ($item_validado['id'] && $item_validado['cantidad'] && 
            $item_validado['precio_detal'] !== null && $item_validado['precio_mayor'] !== null) {
            $carrito_validado[] = $item_validado;
        }
    }
    
    return $carrito_validado;
}

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

        // Sanitizar y validar id_metodopago
        $id_metodopago = sanitizarEntero($_POST['id_metodopago'] ?? null, 1);
        if (!$id_metodopago || !validarIdMetodoPago($id_metodopago, $metodos_pago)) {
            die(json_encode(['success' => false, 'message' => 'El método de pago seleccionado no es válido']));
        }

        // Sanitizar y validar id_metodoentrega
        $id_metodoentrega = sanitizarEntero($_POST['id_metodoentrega'] ?? null, 1);
        if (!$id_metodoentrega || !validarIdMetodoEntrega($id_metodoentrega, $metodos_entrega)) {
            die(json_encode(['success' => false, 'message' => 'El método de entrega seleccionado no es válido']));
        }

        // Sanitizar y validar banco
        $banco = sanitizarString($_POST['banco'] ?? '', 100);
        if (empty($banco) || !validarBanco($banco)) {
            die(json_encode(['success' => false, 'message' => 'El banco de origen seleccionado no es válido']));
        }

        // Sanitizar y validar banco_destino
        $banco_destino = sanitizarString($_POST['banco_destino'] ?? '', 100);
        if (empty($banco_destino) || !validarBancoDestino($banco_destino)) {
            die(json_encode(['success' => false, 'message' => 'El banco de destino seleccionado no es válido']));
        }

        // Sanitizar referencia bancaria
        $referencia_bancaria = sanitizarString($_POST['referencia_bancaria'] ?? '', 50);
        if (!empty($referencia_bancaria) && !validarReferenciaBancaria($referencia_bancaria)) {
            die(json_encode(['success' => false, 'message' => 'La referencia bancaria no es válida']));
        }

        // Sanitizar teléfono emisor
        $telefono_emisor = sanitizarString($_POST['telefono_emisor'] ?? '', 20);
        if (!empty($telefono_emisor) && !validarTelefono($telefono_emisor)) {
            die(json_encode(['success' => false, 'message' => 'El teléfono emisor no es válido']));
        }

        // Sanitizar dirección de envío
        $direccion_envio = sanitizarDireccion($_POST['direccion_envio'] ?? '');
        
        // Sanitizar sucursal
        $sucursal_envio = sanitizarSucursal($_POST['sucursal_envio'] ?? '');

        // Sanitizar y validar campos de texto
        $referencia_bancaria = !empty($_POST['referencia_bancaria']) ? sanitizarString($_POST['referencia_bancaria'], 50) : '';
        if (!empty($referencia_bancaria) && !validarReferenciaBancaria($referencia_bancaria)) {
            die(json_encode(['success' => false, 'message' => 'La referencia bancaria no es válida']));
        }

        $telefono_emisor = !empty($_POST['telefono_emisor']) ? sanitizarString($_POST['telefono_emisor'], 15) : '';
        if (!empty($telefono_emisor) && !validarTelefono($telefono_emisor)) {
            die(json_encode(['success' => false, 'message' => 'El teléfono emisor no es válido']));
        }

        $direccion_envio = !empty($_POST['direccion_envio']) ? sanitizarDireccion($_POST['direccion_envio']) : '';
        $sucursal_envio = !empty($_POST['sucursal_envio']) ? sanitizarSucursal($_POST['sucursal_envio']) : '';

        // Sanitizar números
        $id_persona = !empty($_POST['id_persona']) ? sanitizarEntero($_POST['id_persona'], 1) : null;
        if ($id_persona === null) {
            die(json_encode(['success' => false, 'message' => 'El ID de persona no es válido']));
        }

        $precio_total_usd = !empty($_POST['precio_total_usd']) ? sanitizarDecimal($_POST['precio_total_usd'], 0) : null;
        $precio_total_bs = !empty($_POST['precio_total_bs']) ? sanitizarDecimal($_POST['precio_total_bs'], 0) : null;

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
              
                'tipo'                => sanitizarEntero($_POST['tipo'] ?? '2', 1, 10) ?? 2,
                'fecha'               => date('Y-m-d h:i A'),
                'estado'              => sanitizarEntero($_POST['estado'] ?? '1', 1, 5) ?? 1,
                'precio_total_usd'    => $precio_total_usd ?? 0,
                'precio_total_bs'     => $precio_total_bs ?? 0,
                'id_persona'          => $id_persona,
            
                // **Pago**
                'id_metodopago'       => sanitizarEntero($_POST['id_metodopago'] ?? '', 1) ?? '',
                'referencia_bancaria' => $referencia_bancaria,
                'telefono_emisor'     => $telefono_emisor,
                'banco_destino'       => sanitizarString($_POST['banco_destino'] ?? '', 100),
                'banco'               => sanitizarString($_POST['banco'] ?? '', 100),
                'monto'               => $precio_total_bs ?? 0,
                'monto_usd'           => $precio_total_usd ?? 0,
                'imagen'              => $rutaImagen,
            
                // **Dirección**
                'id_delivery'         => !empty($_SESSION['pedido_entrega']['id_delivery']) ? sanitizarEntero($_SESSION['pedido_entrega']['id_delivery'], 1) : null,
                'direccion_envio'     => $direccion_envio,
                'sucursal_envio'      => $sucursal_envio,
                'id_metodoentrega'    => sanitizarEntero($_POST['id_metodoentrega'] ?? '', 1) ?? '',
            
                // Carrito (validar estructura del carrito)
                'carrito'             => validarCarrito($_SESSION['carrito'] ?? [])
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
