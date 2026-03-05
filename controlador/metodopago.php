<?php  

use LoveMakeup\Proyecto\Modelo\MetodoPago;

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

$objMetodoPago = new MetodoPago();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['registrar'])) {
        $nombre = $objMetodoPago->sanitizarStringP($_POST['nombre'] ?? '');
        $descripcion = $objMetodoPago->sanitizarStringP($_POST['descripcion'] ?? '');

        if (!empty($nombre) && !empty($descripcion)) {
            $datosPeticion = [
                'operacion' => 'incluir',
                'datos' => [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion
                ]
            ];

            $respuesta = $objMetodoPago->procesarMetodoPago(json_encode($datosPeticion));
            echo json_encode($respuesta);
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Faltan datos para registrar o son inválidos']);
        }

    }else if (isset($_POST['modificar'])) {
        $id_metodopago = $objMetodoPago->sanitizarEnteroP($_POST['id_metodopago'] ?? 0, 1);
        $nombre = $objMetodoPago->sanitizarStringP($_POST['nombre'] ?? '');
        $descripcion = $objMetodoPago->sanitizarStringP($_POST['descripcion'] ?? '');

        if ($id_metodopago && !empty($nombre) && !empty($descripcion)) {
            $datosPeticion = [
                'operacion' => 'modificar',
                'datos' => [
                    'id_metodopago' => $id_metodopago,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion
                ]
            ];

            $respuesta = $objMetodoPago->procesarMetodoPago(json_encode($datosPeticion));
            echo json_encode($respuesta);
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Faltan datos para actualizar o son inválidos']);
        }
    }

    // --- ELIMINAR MÉTODO DE PAGO ---
    else if (isset($_POST['eliminar'])) {
        $id_metodopago = $objMetodoPago->sanitizarEnteroP($_POST['id_metodopago'] ?? 0, 1);

        if ($id_metodopago) {
            $datosPeticion = [
                'operacion' => 'eliminar',
                'datos' => [
                    'id_metodopago' => $id_metodopago
                ]
            ];

            $respuesta = $objMetodoPago->procesarMetodoPago(json_encode($datosPeticion));
            echo json_encode($respuesta);
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Falta ID válido para eliminar']);
        }
    }

    exit;
} else if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(13, 1)) {
     /* $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Metodo Pago'
        ];
        $objMetodoPago->registrarBitacora(json_encode($bitacora));*/
       
        $metodos = $objMetodoPago->consultar();
         $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'metodopago';
        require_once __DIR__ . '/../vista/metodopago.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} 