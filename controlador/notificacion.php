<?php

use LoveMakeup\Proyecto\Modelo\Notificacion;
use LoveMakeup\Proyecto\Modelo\TipoUsuario;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CAPA 4: SANITIZACIÓN DE DATOS
// ============================================
// Sanitizar acción recibida
$accionRaw = isset($_GET['accion']) ? trim($_GET['accion']) : '';
$accionRaw = htmlspecialchars($accionRaw, ENT_QUOTES, 'UTF-8');

// Sanitizar otros parámetros
$lastIdRaw = isset($_GET['lastId']) ? trim($_GET['lastId']) : '';
$idPostRaw = isset($_POST['id']) ? trim($_POST['id']) : '';

// Detectar si es una petición AJAX (tiene parámetro `accion`)
$esAjax = ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($accionRaw))
       || ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($accionRaw));

// ============================================
// CAPA 5: VALIDACIÓN CON EXPRESIONES REGULARES
// ============================================
// Validar formato de acción (solo letras y guiones bajos)
if (!empty($accionRaw) && !preg_match('/^[a-zA-Z_]+$/', $accionRaw)) {
    if ($esAjax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Acción inválida']);
        exit;
    }
    header('Location:?pagina=login');
    exit;
}

// Validar que lastId sea un número entero no negativo si viene proporcionado
if (!empty($lastIdRaw)) {
    if (!preg_match('/^\d+$/', $lastIdRaw)) {
        if ($esAjax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'ID inválido']);
            exit;
        }
        header('Location:?pagina=notificacion');
        exit;
    }
}

// Validar que id de POST sea un número entero positivo si viene proporcionado
if (!empty($idPostRaw)) {
    if (!preg_match('/^\d+$/', $idPostRaw)) {
        if ($esAjax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'ID de notificación inválido']);
            exit;
        }
        header('Location:?pagina=notificacion');
        exit;
    }
}

// Si no hay sesión y es AJAX, responder 401 JSON en vez de redirigir a login HTML
if (empty($_SESSION['id'])) {
    if ($esAjax) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => 'No autorizado']);
        exit;
    }
    header('Location:?pagina=login');
    exit;
}

$nivel = (int)($_SESSION['nivel_rol'] ?? 0);

// Solo cargar estos archivos si NO es una petición AJAX
if (!$esAjax) {
    if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
    } 
    
    if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }
    
    require_once __DIR__ . '/permiso.php';
}

// Si es AJAX: evitar que warnings/HTML rompan la respuesta JSON
if ($esAjax) {
    // iniciar buffering para capturar cualquier salida inesperada
    if (!ob_get_level()) {
        ob_start();
    }
    // no mostrar errores en HTML
    ini_set('display_errors', '0');

    // convertir errores en excepciones para manejarlos y devolver JSON
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
    set_exception_handler(function($e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => $e->getMessage()]);
        exit;
    });

    // si hay salida en el buffer al final, devolverla como mensaje de error JSON
    register_shutdown_function(function() {
        $buf = '';
        if (ob_get_level()) {
            $buf = ob_get_clean();
        }
        if ($buf !== '') {
            http_response_code(500);
            header('Content-Type: application/json');
            $msg = strip_tags($buf);
            echo json_encode(['error' => true, 'message' => substr($msg, 0, 200)]);
            exit;
        }
    });
}

$N   = new Notificacion();
$Bit = new TipoUsuario();

// 1) AJAX GET → sólo devuelvo el conteo (badge)
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && ($accionRaw ?? '') === 'count')
{
    header('Content-Type: application/json');
    
    // ============================================
    // CAPA 3: VALIDACIÓN DE PERMISOS ESPECÍFICA
    // ============================================
    // Validar que el usuario tenga un rol válido para ver notificaciones
    if ($nivel < 2) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'No tiene permisos para esta acción']);
        exit;
    }
    
    $N->generarDePedidos();

    if ($nivel === 3) {
        // Admin cuenta estados 1 y 4
        $count = $N->contarParaAdmin();
    } elseif ($nivel === 2) {
        // Asesora cuenta solo estado 1
        $count = $N->contarNuevas();
    } else {
        $count = 0;
    }

    if (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['count' => $count]);
    exit;
}

// 2) AJAX GET → nuevos pedidos/reservas
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && ($accionRaw ?? '') === 'nuevos')
{
    header('Content-Type: application/json');
    
    // ============================================
    // CAPA 3: VALIDACIÓN DE PERMISOS ESPECÍFICA
    // ============================================
    // Validar que el usuario tenga un rol válido para ver notificaciones
    if ($nivel < 2) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'No tiene permisos para esta acción']);
        exit;
    }
    
    // Asegura notificaciones antes de listar
    $N->generarDePedidos();

    // ============================================
    // CAPA 3: VALIDACIÓN DE CAMPOS VACÍOS Y LÓGICA
    // ============================================
    // Convertir a entero después de validación regex previa
    $lastId = !empty($lastIdRaw) ? (int)$lastIdRaw : 0;
    
    // Validar que lastId sea >= 0
    if ($lastId < 0) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'ID inválido']);
        exit;
    }
    
    $nuevos = $N->getNuevosPedidos($lastId);

        if (ob_get_level()) { ob_end_clean(); }
        echo json_encode([
            'count'   => count($nuevos),
            'pedidos' => $nuevos
        ]);
    exit;
}

// 3) POST → solo 'leer' y siempre respondo JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($accionRaw)) {
    header('Content-Type: application/json');

    $accion = $accionRaw;
    
    // ============================================
    // CAPA 3: VALIDACIÓN DE CAMPOS VACÍOS Y LÓGICA
    // ============================================
    // Convertir a entero después de validación regex previa
    $id = !empty($idPostRaw) ? (int)$idPostRaw : 0;
    
    // Validar que id sea > 0 para operaciones que lo requieren
    $success = false;
    $mensaje = '';

    // ============================================
    // CAPA 3: VALIDACIÓN DE PERMISOS ESPECÍFICA
    // ============================================
    // Admin
    if ($accion === 'marcarLeida' && $nivel === 3 && $id > 0) {
        // Validación adicional: verificar que el ID sea válido
        if (!is_numeric($id) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'ID de notificación inválido']);
            exit;
        }
        $success = $N->marcarLeida($id);
        $mensaje = $success
            ? 'Notificación marcada como leída.'
            : 'Error al marcar como leída.';
    }
    // Asesora
    elseif ($accion === 'marcarLeidaAsesora' && $nivel === 2 && $id > 0) {
        // Validación adicional: verificar que el ID sea válido
        if (!is_numeric($id) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'ID de notificación inválido']);
            exit;
        }
        $success = $N->marcarLeidaAsesora($id);
        $mensaje = $success
            ? 'Notificación marcada como leída para ti.'
            : 'Error al marcar como leída.';
    }
    else {
        http_response_code(400);
        if (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'mensaje' => 'Acción inválida o no autorizada.']);
        exit;
    }

    // Respondo siempre JSON y salgo
    if (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['success' => $success, 'mensaje' => $mensaje]);
    exit;
}


// 4) GET normal: regenerar y listar
try {
    $N->generarDePedidos();
    $all = $N->getAll();
} catch (\Throwable $e) {
    // Si hay un error con notificaciones (p. ej. esquema de BD), no detener la aplicación
    error_log('notificacion.php fallo al generar/listar: ' . $e->getMessage());
    $all = [];
}

// FILTRADO según rol:
// Estados:
//  1 = Nueva (no leída) - visible para ambos
//  2 = Leída por admin - oculta para ambos
//  4 = Leída por asesora - visible solo para admin
// 
// - Admin (nivel 3) ve estados 1 (nuevas) y 4 (leídas solo por asesora)
// - Asesora (nivel 2) ve solo estado 1 (nuevas) - NO ve leídas
if ($nivel === 3) {
    // Admin ve notificaciones nuevas y leídas por asesora
    $notificaciones = array_filter(
      $all,
      fn($n) => in_array((int)$n['estado'], [1, 4])
    );
}
elseif ($nivel === 2) {
    // Asesora ve solo notificaciones nuevas (estado 1)
    // NO ve estado 2 (leídas por admin) ni estado 4 (leídas por ella)
    $notificaciones = array_filter(
      $all,
      fn($n) => (int)$n['estado'] === 1
    );
}
else {
    $notificaciones = [];
}

// Conteo para badge nav
if ($nivel === 3) {
    $newCount = $N->contarParaAdmin();
}
elseif ($nivel === 2) {
    $newCount = $N->contarNuevas();
}
else {
    $newCount = 0;
}

// 5) Cargar vista
if ($nivel >= 2) {
    if($_SESSION["nivel_rol"] >= 2 && tieneAcceso(18, 1)){
        require_once __DIR__ . '/../vista/notificacion.php';
    } else{
        require_once 'vista/seguridad/privilegio.php';
    }
} elseif ($nivel === 1) {
    header("Location: ?pagina=catalogo");
    exit();
} else {
    require_once 'vista/seguridad/privilegio.php';
}
