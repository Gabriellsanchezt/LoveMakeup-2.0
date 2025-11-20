<?php

use LoveMakeup\Proyecto\Modelo\ReservaCliente;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id'])) {
  header("Location:?pagina=login");
  exit;
}

$reserva = new ReservaCliente();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  ini_set('display_errors', 0);
  ini_set('log_errors', 1);
  ini_set('error_log', __DIR__ . '/../errores_reserva.log');

  try {

    if (empty($_SESSION['carrito'])) {
      throw new \Exception('El carrito está vacío.');
    }

    // Validaciones
    $referencia = trim($_POST['referencia_bancaria'] ?? '');
    $telefono   = trim($_POST['telefono_emisor'] ?? '');
    $banco      = trim($_POST['banco'] ?? '');
    $banco_destino = trim($_POST['banco_destino'] ?? '');
    $precio_bs  = floatval($_POST['precio_total_bs'] ?? 0);
    $precio_usd = floatval($_POST['precio_total_usd'] ?? 0);
    $id_metodopago = $_POST['id_metodopago'] ?? '1';

    if (!preg_match('/^[0-9]{4,6}$/', $referencia)) {
      throw new \Exception('Referencia bancaria inválida.');
    }

    if (!preg_match('/^(0412|0414|0416|0424|0426)[0-9]{7}$/', $telefono)) {
      throw new \Exception('Teléfono del emisor inválido.');
    }

    if (!$banco) throw new \Exception('Seleccione un banco de origen.');
    if (!$banco_destino) throw new \Exception('Seleccione un banco de destino.');

    // Procesar imagen
    if (empty($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
      throw new \Exception('Debe adjuntar un comprobante de pago.');
    }

    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $permitidas)) {
      throw new \Exception('Formato de imagen no permitido.');
    }

    $carpeta = __DIR__ . '/../assets/img/captures/';
    if (!is_dir($carpeta)) {
      mkdir($carpeta, 0775, true);
    }

    $nombreArchivo = uniqid('img_').'.'.$ext;
    $rutaRelativa = 'assets/img/captures/'.$nombreArchivo;
    $rutaAbsoluta = $carpeta.$nombreArchivo;

    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaAbsoluta)) {
      throw new \Exception('No se pudo guardar el comprobante.');
    }

    // Construir datos para el modelo
    $datos = [
      'operacion' => 'registrar_reserva',
      'datos' => [
        'id_persona'          => $_SESSION['id'],
        'precio_total_usd'    => $precio_usd,
        'precio_total_bs'     => $precio_bs,
        'id_metodopago'       => $id_metodopago,
        'referencia_bancaria' => $referencia,
        'telefono_emisor'     => $telefono,
        'banco'               => $banco,
        'banco_destino'       => $banco_destino,
        'monto'               => $precio_bs,
        'monto_usd'           => $precio_usd,
        'imagen'              => $rutaRelativa,
        'carrito'             => $_SESSION['carrito'],
      ]
    ];

    $res = $reserva->procesarReserva(json_encode($datos));

    if (!empty($res['success']) && isset($res['id_pedido'])) {
      unset($_SESSION['carrito']);

      echo json_encode([
        'success'  => true,
        'message'  => 'Reserva realizada correctamente.',
        'redirect' => '?pagina=confirmacion&id=' . $res['id_pedido']
      ]);
      exit;
    }

    throw new \Exception($res['message'] ?? 'Error al procesar la reserva.');

  } catch (Throwable $e) {

    error_log('[RESERVA_ERROR] '.$e->getMessage().' en '.$e->getFile().':'.$e->getLine());

    echo json_encode([
      'success' => false,
      'message' => $e->getMessage()
    ]);
    exit;
  }
}

require_once __DIR__ . '/../vista/tienda/reserva_cliente.php';
