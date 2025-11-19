<?php

use LoveMakeup\Proyecto\Modelo\Notificacion;
use LoveMakeup\Proyecto\Modelo\TipoUsuario;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Detectar si es una petición AJAX (tiene parámetro `accion`)
$esAjax = ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']))
       || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['accion']));

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
    && ($_GET['accion'] ?? '') === 'count')
{
    header('Content-Type: application/json');
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
    && ($_GET['accion'] ?? '') === 'nuevos')
{
    header('Content-Type: application/json');
    // Asegura notificaciones antes de listar
    $N->generarDePedidos();

    $lastId = (int)($_GET['lastId'] ?? 0);
    $nuevos = $N->getNuevosPedidos($lastId);

        if (ob_get_level()) { ob_end_clean(); }
        echo json_encode([
            'count'   => count($nuevos),
            'pedidos' => $nuevos
        ]);
    exit;
}

// 3) POST → solo ‘leer’ y siempre respondo JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['accion'])) {
    header('Content-Type: application/json');

    $accion = $_GET['accion'];
    $id     = (int)($_POST['id'] ?? 0);
    $success = false;
    $mensaje = '';

    // Admin
    if ($accion === 'marcarLeida' && $nivel === 3 && $id > 0) {
        $success = $N->marcarLeida($id);
        $mensaje = $success
            ? 'Notificación marcada como leída.'
            : 'Error al marcar como leída.';
    }
    // Asesora
    elseif ($accion === 'marcarLeidaAsesora' && $nivel === 2 && $id > 0) {
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
$N->generarDePedidos();
$all = $N->getAll();

// FILTRADO según rol:
//  - Admin ve estados 1 (nuevas) y 4 (leídas solo por asesora)
//  - Asesora ve solo estado 1
if ($nivel === 3) {
    $notificaciones = array_filter(
      $all,
      fn($n) => in_array((int)$n['estado'], [1,4])
    );
}
elseif ($nivel === 2) {
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
    if($_SESSION["nivel_rol"] >= 2 && tieneAcceso(18, 'ver')){
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
