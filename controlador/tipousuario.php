<?php

use LoveMakeup\Proyecto\Modelo\TipoUsuario;
use LoveMakeup\Proyecto\Modelo\Bitacora;

// controlador/tipousuario.php

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
$obj = new TipoUsuario();

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el nivel sea válido
 */
function validarNivel($nivel) {
    if (empty($nivel) || !is_numeric($nivel)) {
        return false;
    }
    $nivel = (int)$nivel;
    $niveles_validos = [2, 3];
    return in_array($nivel, $niveles_validos, true);
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
 * Valida que el id_tipo sea válido y exista en la base de datos
 */
function validarIdTipo($id_tipo, $tipos) {
    if (empty($id_tipo) || !is_numeric($id_tipo)) {
        return false;
    }
    $id_tipo = (int)$id_tipo;
    foreach ($tipos as $tipo) {
        if ($tipo['id_rol'] == $id_tipo && $tipo['estatus'] >= 1 && $tipo['id_rol'] > 1) {
            return true;
        }
    }
    return false;
}

// 0) Bitácora de acceso al módulo (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $bit = [
        'id_persona' => $_SESSION['id'],
        'accion'     => 'Acceso a módulo',
        'descripcion'=> 'Ingreso al módulo Tipo Usuario'
    ];
    $bitacoraObj = new Bitacora();
    $bitacoraObj->registrarOperacion($bit['accion'], 'tipousuario', $bit);
}

// 1) CRUD JSON‐driven
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // —— Registrar nuevo rol ——  
    if (isset($_POST['registrar'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $nivel  = (int)($_POST['nivel'] ?? 0);
        $estatus = (int)($_POST['estatus'] ?? 1);

        // Validar nivel
        if (!validarNivel($nivel)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'El nivel seleccionado no es válido']);
            exit;
        }

        // Validar estatus
        if (!validarEstatus($estatus)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'El estatus seleccionado no es válido']);
            exit;
        }

        $payload = [
            'operacion'=>'registrar',
            'datos'    => ['nombre'=>$nombre,'nivel'=>$nivel,'estatus'=>$estatus]
        ];
        $res = $obj->procesarTipousuario(json_encode($payload));

        if ($res['respuesta'] == 1) {
            $estatusText = $estatus == 1 ? 'Activo' : 'Inactivo';
            $bit = [
                'id_persona' => $_SESSION['id'],
                'accion'     => 'Registrar rol',
                'descripcion'=> sprintf(
                    'Registró rol "%s" con nivel %d, estatus %s',
                    $nombre, $nivel, $estatusText
                )
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bit['accion'], 'tipousuario', $bit);
        }

        echo json_encode($res);
        exit;
    }

    // —— Modificar rol ——  
    if (isset($_POST['modificar'])) {
        $idTipo = (int)($_POST['id_tipo'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $nivel  = (int)($_POST['nivel'] ?? 0);
        $estatus= (int)($_POST['estatus'] ?? 1);

        // Validar nivel
        if (!validarNivel($nivel)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El nivel seleccionado no es válido']);
            exit;
        }

        // Validar estatus
        if (!validarEstatus($estatus)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El estatus seleccionado no es válido']);
            exit;
        }

        // Validar id_tipo
        $tipos = $obj->consultar();
        if (!validarIdTipo($idTipo, $tipos)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El tipo de usuario seleccionado no es válido']);
            exit;
        }

        $payload = [
            'operacion'=>'actualizar',
            'datos'=>[
                'id_tipo'=>$idTipo,
                'nombre' =>$nombre,
                'nivel'  =>$nivel,
                'estatus'=>$estatus
            ]
        ];
        $res = $obj->procesarTipousuario(json_encode($payload));

        if ($res['respuesta'] == 1) {
            $estatusText = $estatus == 1 ? 'Activo' : 'Inactivo';
            $bit = [
                'id_persona' => $_SESSION['id'],
                'accion'     => 'Modificar rol',
                'descripcion'=> sprintf(
                    'Modificó rol "%s": nivel %d, estatus %s',
                    $nombre, $nivel, $estatusText
                )
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bit['accion'], 'tipousuario', $bit);
        }

        echo json_encode($res);
        exit;
    }

    // —— Eliminar (desactivar) rol ——  
    if (isset($_POST['eliminar'])) {
        $idTipo = (int)($_POST['id_tipo'] ?? 0);

        // Validar id_tipo
        $tipos = $obj->consultar();
        if (!validarIdTipo($idTipo, $tipos)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'El tipo de usuario seleccionado no es válido']);
            exit;
        }

        // Obtiene nombre del rol
        $todos   = $obj->consultar();
        $rolNom  = 'ID '.$idTipo;
        foreach ($todos as $r) {
            if ((int)$r['id_rol'] === $idTipo) {
                $rolNom = $r['nombre'];
                break;
            }
        }

        $payload = [
            'operacion'=>'eliminar',
            'datos'=>['id_tipo'=>$idTipo]
        ];
        $res = $obj->procesarTipousuario(json_encode($payload));

        if ($res['respuesta'] == 1) {
            $bit = [
                'id_persona' => $_SESSION['id'],
                'accion'     => 'Eliminar rol',
                'descripcion'=> sprintf(
                    'Eliminó rol "%s"',
                    $rolNom
                )
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bit['accion'], 'tipousuario', $bit);
        }

        echo json_encode($res);
        exit;
    }
} if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(17, 'ver')) {
      
        $registro = $obj->consultar();
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'tipousuario';
        require_once 'vista/tipousuario.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} 