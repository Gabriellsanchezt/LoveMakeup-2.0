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

    // Sanitizar metodo_entrega
    $me = sanitizarEntero($_POST['metodo_entrega'] ?? null, 1, 4);
    if (!$me || !validarMetodoEntrega($me)) {
        echo json_encode(['success'=>false,'message'=>'Método de entrega inválido.']);
        exit;
    }

    // Sanitizar empresa_envio si existe
    $empresa_envio = !empty($_POST['empresa_envio']) ? sanitizarEntero($_POST['empresa_envio'], 1, 10) : null;

    if ($me == 2 && $empresa_envio == 3) {
        $me = 3;
    }

    // Construye array de entrega
    $entrega = ['id_metodoentrega' => $me];
    switch ($me) {
        case 4: // Tienda física
            $entrega['direccion_envio'] = sanitizarDireccion($_POST['direccion_envio'] ?? '');
            $entrega['sucursal_envio']  = null;
            break;
        case 2: // MRW
            if (empty($_POST['empresa_envio']) || empty($_POST['sucursal_envio'])) {
                echo json_encode(['success'=>false,'message'=>'Complete empresa y sucursal.']);
                exit;
            }
            // Sanitizar y validar empresa_envio
            $empresa_envio = sanitizarEntero($_POST['empresa_envio'], 1, 10);
            if (!$empresa_envio || !validarEmpresaEnvio($empresa_envio)) {
                echo json_encode(['success'=>false,'message'=>'Empresa de envío inválida.']);
                exit;
            }
            $entrega['empresa_envio']   = $empresa_envio;
            $entrega['sucursal_envio']  = sanitizarSucursal($_POST['sucursal_envio']);
            $entrega['direccion_envio'] = sanitizarDireccion($_POST['direccion_envio'] ?? '');
            break;

        case 3: //ZOOM
                if (empty($_POST['empresa_envio']) || empty($_POST['sucursal_envio'])) {
                    echo json_encode(['success'=>false,'message'=>'Complete empresa y sucursal.']);
                    exit;
                }
                // Sanitizar y validar empresa_envio
                $empresa_envio = sanitizarEntero($_POST['empresa_envio'], 1, 10);
                if (!$empresa_envio || !validarEmpresaEnvio($empresa_envio)) {
                    echo json_encode(['success'=>false,'message'=>'Empresa de envío inválida.']);
                    exit;
                }
                $entrega['empresa_envio']   = $empresa_envio;
                $entrega['sucursal_envio']  = sanitizarSucursal($_POST['sucursal_envio']);
                $entrega['direccion_envio'] = sanitizarDireccion($_POST['direccion_envio'] ?? '');
                break;
     

        case 1: // Delivery propio

            if (empty($_POST['id_delivery'])) {
                echo json_encode(['success'=>false,'message'=>'Debe seleccionar un delivery.']);
                exit;
            }
            // Sanitizar y validar id_delivery
            $id_delivery = sanitizarEntero($_POST['id_delivery'], 1);
            if (!$id_delivery || !validarIdDelivery($id_delivery, $delivery_activos)) {
                echo json_encode(['success'=>false,'message'=>'El delivery seleccionado no es válido.']);
                exit;
            }
                foreach (['zona','parroquia','sector','direccion_envio'] as $f) {
                    if (empty($_POST[$f])) {
                        echo json_encode(['success'=>false,'message'=>"Falta el campo $f."]);
                        exit;
                    }
                }
            
                // Los valores individuales (sanitizados)
                $zona      = sanitizarString($_POST['zona'], 50);
                $parroquia = sanitizarString($_POST['parroquia'], 100);
                $sector    = sanitizarString($_POST['sector'], 100);
                $dirDetall = sanitizarDireccion($_POST['direccion_envio']);

                // Validar zona
                if (empty($zona) || !validarZona($zona)) {
                    echo json_encode(['success'=>false,'message'=>'La zona seleccionada no es válida.']);
                    exit;
                }

                // Validar parroquia
                if (empty($parroquia) || !validarParroquia($parroquia)) {
                    echo json_encode(['success'=>false,'message'=>'La parroquia no es válida.']);
                    exit;
                }

                // Validar sector
                if (empty($sector) || !validarSector($sector)) {
                    echo json_encode(['success'=>false,'message'=>'El sector no es válido.']);
                    exit;
                }

            
                // Concatenamos en una sola dirección
                $entrega['direccion_envio'] = "Zona: {$zona}, Parroquia: {$parroquia}, Sector: {$sector}, Dirección: {$dirDetall}";

              $entrega['id_delivery'] = $id_delivery;
              $entrega['delivery_nombre'] = sanitizarString($_POST['delivery_nombre'] ?? '', 100);
              $entrega['delivery_tipo'] = sanitizarString($_POST['delivery_tipo'] ?? '', 50);
              $entrega['delivery_contacto'] = sanitizarString($_POST['delivery_contacto'] ?? '', 50);
              
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
