<?php

use LoveMakeup\Proyecto\Modelo\Proveedor;
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
$obj = new Proveedor();

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el tipo de documento sea válido
 */
function validarTipoDocumento($tipo_documento) {
    $tipos_validos = ['V', 'J', 'E', 'G'];
    return in_array($tipo_documento, $tipos_validos, true);
}

/**
 * Valida que el ID del proveedor sea válido y exista en la base de datos
 */
function validarIdProveedor($id_proveedor, $proveedores) {
    if (empty($id_proveedor) || !is_numeric($id_proveedor)) {
        return false;
    }
    $id_proveedor = (int)$id_proveedor;
    foreach ($proveedores as $proveedor) {
        if ($proveedor['id_proveedor'] == $id_proveedor && $proveedor['estatus'] == 1) {
            return true;
        }
    }
    return false;
}

// Fijamos el rol en "Administrador"
$rolText = 'Administrador';

// 0) Registrar acceso al módulo (GET sin AJAX ni operaciones)
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && !isset($_POST['consultar_proveedor'])
) {
    $obj->registrarBitacora(json_encode([
        'id_persona'  => $_SESSION['id'],
        'accion'      => 'Acceso a Proveedores',
        'descripcion' => "$rolText accedió al módulo Proveedores"
    ]));
}

// 1) AJAX JSON: Consultar, Registrar, Actualizar, Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['generar'])
) {
    header('Content-Type: application/json');

    // a) Consultar proveedor para edición
    if (isset($_POST['consultar_proveedor'])) {
        // Validar id_proveedor
        $proveedores = $obj->consultar();
        if (!validarIdProveedor($_POST['id_proveedor'], $proveedores)) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'El proveedor seleccionado no es válido']);
            exit;
        }
        
        echo json_encode(
            $obj->consultarPorId((int)$_POST['id_proveedor'])
        );
        exit;
    }

    // b) Registrar nuevo proveedor
    if (isset($_POST['registrar'])) {
        // Validar tipo_documento
        if (!validarTipoDocumento($_POST['tipo_documento'])) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'El tipo de documento seleccionado no es válido']);
            exit;
        }

        $d = [
            'numero_documento' => $_POST['numero_documento'],
            'tipo_documento'   => $_POST['tipo_documento'],
            'nombre'           => ucfirst(strtolower($_POST['nombre'])),
            'correo'           => $_POST['correo'],
            'telefono'         => $_POST['telefono'],
            'direccion'        => $_POST['direccion']
        ];
        $res = $obj->procesarProveedor(
            json_encode(['operacion'=>'registrar','datos'=>$d])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Incluir Proveedor',
                'descripcion' => "$rolText registró proveedor {$d['nombre']}"
            ]));
        }
        echo json_encode($res);
        exit;
    }

    // c) Actualizar proveedor existente
    if (isset($_POST['actualizar'])) {
        // Validar tipo_documento
        if (!validarTipoDocumento($_POST['tipo_documento'])) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El tipo de documento seleccionado no es válido']);
            exit;
        }

        // Validar id_proveedor
        $proveedores = $obj->consultar();
        if (!validarIdProveedor($_POST['id_proveedor'], $proveedores)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El proveedor seleccionado no es válido']);
            exit;
        }

        $d = [
            'id_proveedor'     => $_POST['id_proveedor'],
            'numero_documento' => $_POST['numero_documento'],
            'tipo_documento'   => $_POST['tipo_documento'],
            'nombre'           => ucfirst(strtolower($_POST['nombre'])),
            'correo'           => $_POST['correo'],
            'telefono'         => $_POST['telefono'],
            'direccion'        => $_POST['direccion']
        ];
        // Obtener nombre actual para bitácora
        $old = $obj->consultarPorId((int)$d['id_proveedor']);
        $res = $obj->procesarProveedor(
            json_encode(['operacion'=>'actualizar','datos'=>$d])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Actualizar Proveedor',
                'descripcion' => "$rolText actualizó proveedor {$old['nombre']}"
            ]));
        }
        echo json_encode($res);
        exit;
    }

    // d) Eliminar (desactivar) proveedor
    if (isset($_POST['eliminar'])) {
        // Validar id_proveedor
        $proveedores = $obj->consultar();
        if (!validarIdProveedor($_POST['id_proveedor'], $proveedores)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'El proveedor seleccionado no es válido']);
            exit;
        }

        $id   = (int)$_POST['id_proveedor'];
        $prov = $obj->consultarPorId($id);
        $nombre = $prov['nombre'] ?? "ID $id";

        $res = $obj->procesarProveedor(
            json_encode(['operacion'=>'eliminar','datos'=>['id_proveedor'=>$id]])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Eliminar Proveedor',
                'descripcion' => "$rolText eliminó proveedor $nombre"
            ]));
        }
        echo json_encode($res);
        exit;
    }
} else  if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(9, 'ver')) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Proveedor'
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'proveedor', $bitacora);

       $registro = $obj->consultar();
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'proveedor';
        require_once 'vista/proveedor.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} 