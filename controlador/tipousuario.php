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
$objRol = new TipoUsuario();

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/


if (isset($_POST['registrar'])) {

    if(!empty($_POST['nombreRol']) && !empty($_POST['nivelRol'])){

    $nombre = $_POST['nombreRol'];
    $nivel = $_POST['nivelRol'];
    
    $datosRol = [
        'operacion' => 'registrar',
        'datos' => [
            'nombre' =>  $nombre,
            'nivel' =>  $nivel
        ] 
    ];

    $resultado = $objRol->procesarRol(json_encode($datosRol));
    echo json_encode($resultado);
    exit; 

    } else {
        echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "Vacios"]);
        exit;
    }
    

} else if(isset($_POST['modificar'])){ /* |||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| PARA BUSCAR Y VER LOS PERMISOS  */

    if (!empty($_POST['modificar']) && !empty($_POST['RolNombre'])) {   /* VACIOS   | VER LOS PERMISOS  */

        $id_rol = $_POST['modificar'];
        $usuario = $_SESSION['id_usuario'];
      
      
/*
            if ($id_usuario == $_SESSION['id_usuario']) {
                header("location:?pagina=usuario");
                exit;
            }

            if ($id_usuario == 2) {
                header("location:?pagina=usuario");
                exit;
            }
*/
            $modificar = $objRol->buscar($id_rol);
            $nivel_usuario = $objRol->obtenerNivelPorId($usuario);

            $nombre_usuario = trim($_POST['RolNombre']);
            require_once("vista/seguridad/permiso.php");

     

    } else{  /* DATOS VACIOS | VER LOS PERMISOS  */
        header("location:?pagina=usuario");
      exit;
    }  
       
} else if (isset($_POST['actualizar_permisos'])) {

    // Permisos enviados desde la vista
    $permisosRecibidos = $_POST['permiso'] ?? [];      // switches activos
    $permisosId = $_POST['permiso_id'] ?? [];          // id_permiso_rol existentes

    

    $listaPermisos = [];

    /*
        Estructura recibida:

        permiso_id[modulo][id_permiso] = id_permiso_rol
        permiso[modulo][id_permiso] = on (si está activo)

        Ahora recorremos TODOS los permisos existentes
        y determinamos si deben quedar en estado 1 o 0.
    */

    foreach ($permisosId as $modulo_id => $permisosModulo) {
        foreach ($permisosModulo as $id_permiso => $id_permiso_rol) {

            // Si el switch está marcado → estado = 1, si no → 0
            $estado = isset($permisosRecibidos[$modulo_id][$id_permiso]) ? 1 : 0;

            $listaPermisos[] = [
                'id_permiso_rol' => (int)$id_permiso_rol,
                'id_modulo'      => (int)$modulo_id,
                'id_permiso'     => (int)$id_permiso, // 1..5
                'estado'         => $estado
            ];
        }
    }

    // Datos para enviar al modelo
    $datosPermiso = [
        'operacion' => 'actualizar_permisos',
        'datos' => $listaPermisos
    ];

    // Procesar actualización
    $resultado = $objRol->procesarRol(json_encode($datosPermiso));

    /* Registrar en bitácora si todo salió bien
    if ($resultado['respuesta'] == 1) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Modificar Permiso',
            'descripcion' => 'Se modificaron los permisos del usuario con ID: ' . $id_rol
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'usuario', $bitacora);
    }*/

    echo json_encode($resultado);
    exit;
}else if(isset($_POST['actualizar'])){
    
    if(!empty($_POST['id_rol']) && !empty($_POST['nombre']) && !empty($_POST['nivel']) && !empty($_POST['nivel_actual'])){

        $id_rol = $_POST['id_rol'];  $nombre = $_POST['nombre'];  $nivel = $_POST['nivel']; $nivel_actual = $_POST['nivel_actual'];
        
        $datosRol = [
            'operacion' => 'actualizar',
            'datos' => [
                'id_rol' =>  $id_rol,
                'nombre' =>  $nombre,
                'nivel' =>  $nivel,
                'nivel_actual' => $nivel_actual
            ] 
        ];

        $resultado = $objRol->procesarRol(json_encode($datosRol));
        echo json_encode($resultado);
        exit; 

    } else {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "Vacios"]);
        exit;
    }

} else if(isset($_POST['eliminar'])){

 if(!empty($_POST['id_rol'])){

    
    $id_rol = $_POST['id_rol'];

    if($id_rol == 1 || $id_rol == 2 || $id_rol == 3 || $id_rol == 4 ){
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "no se puede"]);
        exit;
    }
    
    $datosRol = [
        'operacion' => 'eliminar',
        'datos' => [
            'id_rol' =>  $id_rol
        ] 
    ];

    $resultado = $objRol->procesarRol(json_encode($datosRol));
    echo json_encode($resultado);
    exit; 

    } else {
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "Vacios"]);
        exit;
    }


} else if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(17, 1)) {
      
        $registro = $objRol->consultar();
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'tipousuario';

        require_once 'vista/tipousuario.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} 