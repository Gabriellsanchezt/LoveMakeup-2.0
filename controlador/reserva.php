<?php  
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
require_once 'modelo/reserva.php';

use LoveMakeup\Proyecto\Modelo\Reservas;

$objReservas = new Reservas();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Confirmar / cambiar estado
    if (!isset($_POST['id_pedido'])) {
        echo json_encode([
            'respuesta' => 0,
            'mensaje' => 'ID de pedido no recibido'
        ]);
        exit;
    }
  /* ========= SANITIZAR ========= */

  $id_pedido = $objReservas->sanitizarEntero($_POST['id_pedido'],1);

  if ($id_pedido === null) {
      echo json_encode([
          'respuesta' => 0,
          'mensaje' => 'ID de pedido inválido'
      ]);
      exit;
  }

  /* ========= DETECTAR INYECCION ========= */

  if ($objReservas->detectarInyeccionSQL($_POST['id_pedido'])) {
      echo json_encode([
          'respuesta' => 0,
          'mensaje' => 'Intento de inyección detectado'
      ]);
      exit;
  }


  /* ========= CONFIRMAR RESERVA ========= */

  if (isset($_POST['confirmar'])) {

      $datosPeticion = [
          'operacion' => 'cambiar_estado',
          'datos' => [
              'id_pedido' => $id_pedido,
              'estado' => 2
          ]
      ];

      $respuesta = $objReservas->procesarReserva(json_encode($datosPeticion));
      echo json_encode($respuesta);
      exit;
  }


  /* ========= ELIMINAR RESERVA ========= */

  if (isset($_POST['eliminar'])) {

      $datosPeticion = [
          'operacion' => 'eliminar',
          'datos' => $id_pedido
      ];

      $respuesta = $objReservas->procesarReserva(json_encode($datosPeticion));
      echo json_encode($respuesta);
      exit;
  }


  /* ========= ERROR ========= */

  echo json_encode([
      'respuesta' => 0,
      'mensaje' => 'Operación no válida'
  ]);
  exit;
}



/* =============================
 CONSULTAR RESERVAS
============================= */

$reservas = $objReservas->consultarReservasCompletas();

foreach ($reservas as &$reserva) {

  $id = $objReservas->sanitizarEntero($reserva['id_pedido'],1);

  if ($id !== null) {
      $reserva['detalles'] = $objReservas->consultarDetallesReserva($id);
  } else {
      $reserva['detalles'] = [];
  }
}



// Verificación de privilegios
if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(4, 1)) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'reserva';
    require_once 'vista/reserva.php'; // Asegúrate de tener esta vista
} else {
    require_once 'vista/seguridad/privilegio.php';
}

