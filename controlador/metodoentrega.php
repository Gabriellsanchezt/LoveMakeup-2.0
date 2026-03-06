<?php  

use LoveMakeup\Proyecto\Modelo\MetodoEntrega;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION["id"])) {
    header("location:?pagina=login");
    exit;
}
if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
} 

if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }/*  Validacion cliente  */

require_once 'permiso.php';
$objEntrega = new MetodoEntrega();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['registrar'])) {
        $nombre = $objEntrega->sanitizarString($_POST['nombre'] ?? '');
        $descripcion = $objEntrega->sanitizarString($_POST['descripcion'] ?? '');

        if (!empty($nombre) && !empty($descripcion)) {
            $datosPeticion = [
                'operacion' => 'incluir',
                'datos' => [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion
                ]
            ];

            $respuesta = $objEntrega->procesarMetodoEntrega(json_encode($datosPeticion));
            echo json_encode($respuesta);
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Faltan datos para registrar o son inválidos']);
        }
    

    } else if (isset($_POST['actualizar'])) {
        $id_entrega = $objEntrega->sanitizarEntero($_POST['id_entrega'] ?? 0, 1);
        $nombre = $objEntrega->sanitizarString($_POST['nombre'] ?? '');
        $descripcion = $objEntrega->sanitizarString($_POST['descripcion'] ?? '');

        if ($id_entrega && !empty($nombre) && !empty($descripcion)) {
            $datosPeticion = [
                'operacion' => 'modificar',
                'datos' => [
                    'id_entrega' => $id_entrega,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion
                ]
            ];

            $respuesta = $objEntrega->procesarMetodoEntrega(json_encode($datosPeticion));
            echo json_encode($respuesta);
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Faltan datos para actualizar o son inválidos']);
        }

    } else if (isset($_POST['eliminar'])) {
        $id_entrega = $objEntrega->sanitizarEntero($_POST['id_entrega'] ?? 0, 1);

        if ($id_entrega) {
            $datosPeticion = [
                'operacion' => 'eliminar',
                'datos' => [
                    'id_entrega' => $id_entrega
                ]
            ];

            $respuesta = $objEntrega->procesarMetodoEntrega(json_encode($datosPeticion));
            echo json_encode($respuesta);
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta ID válido para eliminar']);
        }
    }

    exit;
} else if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(12, 1)) {
     /* $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Metodo Entrega'
        ];
        $objEntrega->registrarBitacora(json_encode($bitacora));*/
            $metodos = $objEntrega->consultar(); 
            $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'metodoentrega';
            require_once __DIR__ . '/../vista/metodoentrega.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} 
?>
