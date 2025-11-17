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

   
     //Valida que el tipo_documento sea válido
    function validarTipoDocumento($tipo_documento) {
        $tipos_validos = ['V', 'E'];
        return in_array($tipo_documento, $tipos_validos, true);
    }

if (isset($_POST['actualizar'])) {

    if(!empty($_POST['nombre']) &&!empty($_POST['apellido']) && !empty($_POST['cedula'])&&!empty($_POST['correo']) && !empty($_POST['telefono']) && !empty($_POST['tipo_documento'])){
        
        $nombre =  ucfirst(strtolower($_POST['nombre'])); $apellido = ucfirst(strtolower($_POST['apellido'])); $cedula = $_POST['cedula']; $correo = strtolower($_POST['correo']);  $telefono = $_POST['telefono']; $documento = $_POST['tipo_documento'];
    
        // Validar tipo_documento
        if (!validarTipoDocumento($documento)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El tipo de documento no es válido']);
            exit;
        }

        if (ctype_digit($cedula) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        
              $datosUsuario = [
                'operacion' => 'actualizar',
                'datos' => [
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'cedula' => $cedula,
                    'correo' => $correo,
                    'telefono' => $telefono,
                    'cedula_actual' => $_SESSION["id"],
                    'correo_actual' => $_SESSION["correo"],
                    'tipo_documento' => $documento
                ]
            ];

            $resultado = $objdatos->procesarUsuario(json_encode($datosUsuario));

                if ($resultado['respuesta'] == 1) {
                    $bitacora = [
                        'id_usuario' => $_SESSION["id_usuario"],
                        'accion' => 'Modificación de Usuario',
                        'descripcion' => 'El usuario con ID: ' .
                                    ' cedula: ' . $datosUsuario['datos']['cedula'] .
                                        ' nombre: ' . $datosUsuario['datos']['nombre'] .
                                        ' apellido: ' . $datosUsuario['datos']['apellido'] .
                                        ' telefono: ' . $datosUsuario['datos']['telefono'] .
                                    ' Correo: ' . $datosUsuario['datos']['correo']
                    ];
                    $bitacoraObj = new Bitacora();
                    $bitacoraObj->registrarOperacion($bitacora['accion'], 'datos', $bitacora);
                }

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
        } else {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'La cédula o correo no es válido']);
            exit; 
        }

    } else{
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'datos vacios']);
        exit;
    }
      
} else if(isset($_POST['actualizarclave'])){ //  ||||||||||||||||||||||||||||||||||||||||||||||| ACTUALIZAR CLAVE
    if(!empty($_POST['clave']) && !empty($_POST['clavenueva'])){
    
        $datosUsuario = [
            'operacion' => 'actualizarclave',
            'datos' => [
                'id_usuario' => $_SESSION["id_usuario"],
                'clave_actual' => $_POST['clave'],
                'clave' => $_POST["clavenueva"]
            ]
        ];

        $resultado = $objdatos->procesarUsuario(json_encode($datosUsuario));
    
            if ($resultado['respuesta'] == 1) {
                $bitacora = [
                    'id_persona' => $_SESSION["id"],
                    'accion' => 'Modificación de Usuario',
                    'descripcion' => 'El usuario con cambio su clave, ID: ' . $_SESSION["id"] 
                                
                ];
                $bitacoraObj = new Bitacora();
                $bitacoraObj->registrarOperacion($bitacora['accion'], 'datos', $bitacora);
                
            }
            
        echo json_encode($resultado);
    }else{
        $res = ['respuesta' => 0, 'accion' => 'clave','text' => 'Datos Vacios'];
        echo json_encode($res);
        exit;
    }
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
    } else{
        require_once 'vista/seguridad/datos.php';
    }
} 

?>
