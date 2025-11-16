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

// Obtener lista de deliveries para validaciones
$deliveries = $obj->consultar();

// Fijamos el rol en "Administrador"
$rolText = 'Administrador';

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el tipo de vehículo sea válido
 */
function validarTipo($tipo) {
    if (empty($tipo)) {
        return false;
    }
    $tipos_validos = ['Carro', 'Moto', 'Bicicleta'];
    return in_array($tipo, $tipos_validos, true);
}

/**
 * Valida que el estatus sea válido
 */
function validarEstatus($estatus) {
    if (empty($estatus) || !is_numeric($estatus)) {
        return false;
    }
    $estatus = (int)$estatus;
    $estatus_validos = [1, 2];
    return in_array($estatus, $estatus_validos, true);
}

/**
 * Valida que el id_delivery sea válido y exista en la base de datos
 */
function validarIdDelivery($id_delivery, $deliveries) {
    if (empty($id_delivery) || !is_numeric($id_delivery)) {
        return false;
    }
    $id_delivery = (int)$id_delivery;
    foreach ($deliveries as $delivery) {
        if ($delivery['id_delivery'] == $id_delivery && $delivery['estatus'] != 0) {
            return true;
        }
    }
    return false;
}

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
        $tipo = ucfirst(strtolower($_POST['tipo'] ?? ''));
        $estatus = (int)($_POST['estatus'] ?? 0);

        // Validar tipo
        if (!validarTipo($tipo)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'El tipo de vehículo seleccionado no es válido']);
            exit;
        }

        // Validar estatus
        if (!validarEstatus($estatus)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'El estatus seleccionado no es válido']);
            exit;
        }

        $d = [
            'nombre' => ucfirst(strtolower($_POST['nombre'])),
            'tipo' => $tipo,
            'contacto' => $_POST['contacto'],
            'estatus' => $estatus
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
        $id_delivery = (int)($_POST['id_delivery'] ?? 0);
        $tipo = ucfirst(strtolower($_POST['tipo'] ?? ''));
        $estatus = (int)($_POST['estatus'] ?? 0);

        // Validar id_delivery primero (debe existir en la base de datos)
        if (!validarIdDelivery($id_delivery, $deliveries)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El delivery seleccionado no es válido']);
            exit;
        }

        // Validar tipo
        if (!validarTipo($tipo)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El tipo de vehículo seleccionado no es válido']);
            exit;
        }

        // Validar estatus
        if (!validarEstatus($estatus)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El estatus seleccionado no es válido']);
            exit;
        }

        $d = [
            'id_delivery' => $id_delivery,
            'nombre' => ucfirst(strtolower($_POST['nombre'])),
            'tipo' => $tipo,
            'contacto' => $_POST['contacto'],
            'estatus' => $estatus
        ];
        // Obtener nombre actual para bitácora
        $old = $obj->consultarPorId($id_delivery);
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
        $id = (int)($_POST['id_delivery'] ?? 0);

        // Validar id_delivery (debe existir en la base de datos)
        if (!validarIdDelivery($id, $deliveries)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'El delivery seleccionado no es válido']);
            exit;
        }

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
        $id = (int)($_POST['id_delivery'] ?? 0);
        $estatus = (int)($_POST['estatus'] ?? 0);

        // Validar id_delivery (debe existir en la base de datos)
        if (!validarIdDelivery($id, $deliveries)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'cambiarEstatus', 'mensaje' => 'El delivery seleccionado no es válido']);
            exit;
        }

        // Validar estatus
        if (!validarEstatus($estatus)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'cambiarEstatus', 'mensaje' => 'El estatus seleccionado no es válido']);
            exit;
        }

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
} else  if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(11, 'ver')) {
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