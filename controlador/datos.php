<?php  

use LoveMakeup\Proyecto\Modelo\Datos;
use LoveMakeup\Proyecto\Modelo\Bitacora;

session_start();
if (empty($_SESSION["id"])) {
    header("location:?pagina=login");
    exit;
} /* Validacion URL */
if (!empty($_SESSION['id'])) { 
        require_once 'verificarsession.php';
 } 

 if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }/*  Validacion cliente  */

require_once 'permiso.php';

$objdatos = new Datos();

if (isset($_POST['actualizar'])) {
     $datosUsuario = [
        'operacion' => 'actualizar',
        'datos' => [

            'nombre' => ucfirst(strtolower($_POST['nombre'])),
            'apellido' => ucfirst(strtolower($_POST['apellido'])),
            'cedula' => $_POST['cedula'],
            'correo' => strtolower($_POST['correo']),
            'telefono' => $_POST['telefono'],
            'cedula_actual' => $_SESSION["id"],
            'correo_actual' => $_SESSION["correo"],
            'tipo_documento' => $_POST['tipo_documento']
        ]
    ];

    $nombre_actual = ucfirst(strtolower($_SESSION["nombre"]));
    $apellido_actual = ucfirst(strtolower($_SESSION["apellido"]));
    $telefono_actual = $_SESSION["telefono"];
     $documento_actual = $_SESSION["documento"];
   

    $datos = $datosUsuario['datos'];

    $hayCambios = (
        $nombre_actual !== $datos['nombre'] ||
        $apellido_actual !== $datos['apellido'] ||
        $telefono_actual !== $datos['telefono'] ||
        $datos['cedula_actual'] !== $datos['cedula'] ||
         $documento_actual !== $datos['tipo_documento'] ||
        strtolower($datos['correo_actual']) !== strtolower($datos['correo']) // Comparación case-insensitive
    );

    if (!$hayCambios) {
        $res = [
            'respuesta' => 0,
            'accion' => 'actualizar',
            'text' => 'No se realizaron cambios en los datos.'
        ];
        echo json_encode($res);
        exit;
    }

   $resultado = $objdatos->procesarUsuario(json_encode($datosUsuario));
    /*
     if ($resultado['respuesta'] == 1) {
        $bitacora = [
            'id_usuario' => $_SESSION["id_usuario"],
            'accion' => 'Modificación de Usuario',
            'descripcion' => 'El usuario con ID: ' . $datosUsuario['datos']['id_persona'] . 
                           ' cedula: ' . $datosUsuario['datos']['cedula'] .
                            ' nombre: ' . $datosUsuario['datos']['nombre'] .
                            ' apellido: ' . $datosUsuario['datos']['apellido'] .
                            ' telefono: ' . $datosUsuario['datos']['telefono'] .
                           ' Correo: ' . $datosUsuario['datos']['correo']
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'datos', $bitacora);
    }

       */

    if ($resultado['respuesta'] == 1) {
        $id_usuario = $_SESSION["id_usuario"];
        $resultado1 = $objdatos->consultardatos($id_usuario);

                // Verificamos que hay al menos un resultado
            if (!empty($resultado1) && is_array($resultado1)) {
                $datos = $resultado1[0]; // Accedemos al primer elemento

                $_SESSION["nombre"]   = $datos["nombre"];
                $_SESSION["apellido"] = $datos["apellido"];
                $_SESSION["telefono"] = $datos["telefono"];
                $_SESSION["correo"]   = $datos["correo"];
                $_SESSION["documento"]   = $datos["tipo_documento"];
                $_SESSION["id"]   = $datos["cedula"];
            }
      }   

    echo json_encode($resultado);
      
} else if(isset($_POST['actualizarclave'])){
    $datosUsuario = [
        'operacion' => 'actualizarclave',
        'datos' => [
            'id_usuario' => $_SESSION["id_usuario"],
            'clave_actual' => $_POST['clave'],
            'clave' => $_POST["clavenueva"]
        ]
    ];

  $resultado = $objdatos->procesarUsuario(json_encode($datosUsuario));
    /*
     if ($resultado['respuesta'] == 1) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Modificación de Usuario',
            'descripcion' => 'El usuario con cambio su clave, ID: ' . $datosUsuario['datos']['id_persona'] 
                           
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'datos', $bitacora);
        
    }
        */
        echo json_encode($resultado);

 
}else{
    $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Modificar Datos'
    ];
    $bitacoraObj = new Bitacora();
    $bitacoraObj->registrarOperacion($bitacora['accion'], 'datos', $bitacora);
   
    if ($_SESSION["nivel_rol"] != 2 && $_SESSION["nivel_rol"] != 3) {
    header("Location: ?pagina=catalogo");
    exit();
    }
    require_once 'vista/seguridad/datos.php';
} 


?>
