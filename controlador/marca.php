<?php

use LoveMakeup\Proyecto\Modelo\Marca;
use LoveMakeup\Proyecto\Modelo\Bitacora;

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
    }
require_once 'permiso.php';
$Cat = new Marca();

// 0) GET → acceso + bitácora
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $Cat->registrarBitacora(json_encode([
        'id_persona'  => $_SESSION['id'],
        'accion'      => 'Acceso a Marcas',
        'descripcion' => 'Usuario accedió al módulo Marca'
    ]));
}

// 1) Registrar
if (isset($_POST['registrar'])) {
    if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] < 2 || !tieneAcceso(7, 2)) { // requiere nivel >=2 y permiso 2 (registrar)
        echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'No tiene permisos para realizar esta acción']);
        exit;
    }
    $datos = ['nombre'=>$_POST['nombre']];
    $res   = $Cat->procesarMarca(
        json_encode(['operacion'=>'incluir','datos'=>$datos])
    );
    if ($res['respuesta']==1) {
        $Cat->registrarBitacora(json_encode([
            'id_persona'=>$_SESSION['id'],
            'accion'    =>'Incluir Marca',
            'descripcion'=>"Registró marca “{$datos['nombre']}”"
        ]));
    }
    echo json_encode($res);
    exit;
}

// 2) Modificar
if (isset($_POST['modificar'])) {
    if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] < 2 || !tieneAcceso(7, 3)) { // requiere nivel >=2 y permiso 3 (modificar)
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
        exit;
    }
    $datos = [
        'id_marca'=>$_POST['id_marca'],
        'nombre'      =>$_POST['nombre']
    ];
    $res = $Cat->procesarMarca(
        json_encode(['operacion'=>'actualizar','datos'=>$datos])
    );
    if ($res['respuesta']==1) {
        $Cat->registrarBitacora(json_encode([
            'id_persona'=>$_SESSION['id'],
            'accion'    =>'Actualizar Marca',
            'descripcion'=>"Actualizó marca ID {$datos['id_marca']} → “{$datos['nombre']}”"
        ]));
    }
    echo json_encode($res);
    exit;
}

// 3) Eliminar
if (isset($_POST['eliminar'])) {
    if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] < 2 || !tieneAcceso(7, 4)) { // requiere nivel >=2 y permiso 4 (eliminar)
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
        exit;
    }
    $id = (int) $_POST['id_marca'];

    // obtener nombre antes de eliminar
    try {
        $db = $Cat->getConex1();
        $stmt = $db->prepare("SELECT nombre FROM Marca WHERE id_marca=:id");
        $stmt->execute(['id'=>$id]);
        $nombre = $stmt->fetchColumn() ?: "ID $id";
        $db = null;
    } catch (\PDOException $e) {
        $nombre = "ID $id";
    }

    $res = $Cat->procesarMarca(
        json_encode(['operacion'=>'eliminar','datos'=>['id_marca'=>$id]])
    );
    if ($res['respuesta']==1) {
        $Cat->registrarBitacora(json_encode([
            'id_persona'=>$_SESSION['id'],
            'accion'    =>'Eliminar Marca',
            'descripcion'=>"Eliminó marca “{$nombre}”"
        ]));
    }
    echo json_encode($res);
    exit;

} else if (isset($_SESSION["nivel_rol"]) && $_SESSION["nivel_rol"] >= 2 && tieneAcceso(7, 1)) {
         $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Marca'
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'Marca', $bitacora);
        $marcas = $Cat->consultar();
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'Marca';
        require_once 'vista/marca.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} 
