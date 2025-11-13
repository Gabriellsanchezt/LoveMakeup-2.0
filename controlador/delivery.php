<?php

use LoveMakeup\Proyecto\Modelo\Delivery;
use LoveMakeup\Proyecto\Modelo\Bitacora;

session_start();
if (empty($_SESSION['id'])) {
    header('Location:?pagina=login');
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
$obj = new Delivery();

// Fijamos el rol en "Administrador"
$rolText = 'Administrador';

// 0) Registrar acceso al módulo (GET sin AJAX ni operaciones)
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && !isset($_POST['consultar_delivery'])
) {
    $obj->registrarBitacora(json_encode([
        'id_persona'  => $_SESSION['id'],
        'accion'      => 'Acceso a Delivery',
        'descripcion' => "$rolText accedió al módulo Delivery"
    ]));
}

// 1) AJAX JSON: Consultar, Registrar, Actualizar, Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['generar'])
) {
    header('Content-Type: application/json');

    // a) Consultar delivery para edición
    if (isset($_POST['consultar_delivery'])) {
        echo json_encode(
            $obj->consultarPorId((int)$_POST['id_delivery'])
        );
        exit;
    }

    // b) Registrar nuevo delivery
    if (isset($_POST['registrar'])) {
        $d = [
            'nombre' => ucfirst(strtolower($_POST['nombre'])),
            'tipo' => ucfirst(strtolower($_POST['tipo'])),
            'contacto' => $_POST['contacto'],
            'estatus' => (int)$_POST['estatus']
        ];
        $res = $obj->procesarDelivery(
            json_encode(['operacion'=>'registrar','datos'=>$d])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Incluir Delivery',
                'descripcion' => "$rolText registró delivery {$d['nombre']}"
            ]));
        }
        echo json_encode($res);
        exit;
    }

    // c) Actualizar delivery existente
    if (isset($_POST['actualizar'])) {
        $d = [
            'id_delivery' => $_POST['id_delivery'],
            'nombre' => ucfirst(strtolower($_POST['nombre'])),
            'tipo' => ucfirst(strtolower($_POST['tipo'])),
            'contacto' => $_POST['contacto'],
            'estatus' => (int)$_POST['estatus']
        ];
        // Obtener nombre actual para bitácora
        $old = $obj->consultarPorId((int)$d['id_delivery']);
        $res = $obj->procesarDelivery(
            json_encode(['operacion'=>'actualizar','datos'=>$d])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Actualizar Delivery',
                'descripcion' => "$rolText actualizó delivery {$old['nombre']}"
            ]));
        }
        echo json_encode($res);
        exit;
    }

    // d) Eliminar (desactivar) delivery
    if (isset($_POST['eliminar'])) {
        $id   = (int)$_POST['id_delivery'];
        $delivery = $obj->consultarPorId($id);
        $nombre = $delivery['nombre'] ?? "ID $id";

        $res = $obj->procesarDelivery(
            json_encode(['operacion'=>'eliminar','datos'=>['id_delivery'=>$id]])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Eliminar Delivery',
                'descripcion' => "$rolText eliminó delivery $nombre"
            ]));
        }
        echo json_encode($res);
        exit;
    }
    
    // e) Cambiar estatus delivery
    if (isset($_POST['cambiarEstatus'])) {
        $id   = (int)$_POST['id_delivery'];
        $estatus = (int)$_POST['estatus'];
        $delivery = $obj->consultarPorId($id);
        $nombre = $delivery['nombre'] ?? "ID $id";

        $res = $obj->procesarDelivery(
            json_encode(['operacion'=>'cambiarEstatus','datos'=>['id_delivery'=>$id, 'estatus'=>$estatus]])
        );
        if ($res['respuesta'] == 1) {
            $accionEstatus = $estatus == 1 ? 'activó' : 'inactivó';
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Cambiar Estatus Delivery',
                'descripcion' => "$rolText $accionEstatus delivery $nombre"
            ]));
        }
        echo json_encode($res);
        exit;
    }
} else  if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(6, 'ver')) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Delivery'
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'delivery', $bitacora);

       $registro = $obj->consultar();
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'delivery';
        require_once 'vista/delivery.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

}