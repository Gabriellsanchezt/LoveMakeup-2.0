<?php  

use LoveMakeup\Proyecto\Modelo\Datos;
use LoveMakeup\Proyecto\Modelo\Bitacora;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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


function validarEntradaSQL($input) {
    // Lista negra de palabras y símbolos comunes en SQL Injection
    $blacklist = [
        'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER',
        'CREATE', 'RENAME', 'REPLACE', 'UNION', 'JOIN', 'WHERE', 'HAVING',
        'FROM', 'TABLE', 'DATABASE', 'SCHEMA', 'GRANT', 'REVOKE',
        '--', ';', '#', '/*', '*/', '@@', '@', 'CHAR', 'CAST', 'CONVERT',
        'EXEC', 'EXECUTE', 'xp_', 'sp_', 'OR', 'AND'
    ];

    // Normalizar a mayúsculas para comparar
    $inputUpper = strtoupper($input);

    foreach ($blacklist as $prohibida) {
        if (strpos($inputUpper, $prohibida) !== false) {
            return false; // Contiene palabra prohibida
        }
    }
    return true; // Seguro
}

   
     //Valida que el tipo_documento sea válido
function validarTipoDocumento($tipo_documento) {
    $tipos_validos = ['V', 'E'];
    return in_array($tipo_documento, $tipos_validos, true);
}

if (isset($_POST['actualizar'])) {

    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */

        if(!empty($_POST['nombre']) &&!empty($_POST['apellido']) && !empty($_POST['cedula']) && !empty($_POST['correo']) && 
        !empty($_POST['telefono']) && !empty($_POST['tipo_documento']) && !empty($_POST['cedula_actual']) && !empty($_POST['correo_actual'])){  /* V2 */
            
            $nombre =  ucfirst(strtolower($_POST['nombre'])); $apellido = ucfirst(strtolower($_POST['apellido'])); $cedula = $_POST['cedula']; 
            $correo = strtolower($_POST['correo']);  $telefono = $_POST['telefono']; $documento = $_POST['tipo_documento'];
            $cedula_actual = $_POST['cedula_actual']; $correo_actual = $_POST['correo_actual'];

            $campos = [
                'Nombre' => $nombre,
                'Apellido' => $apellido,      
                'Cedula' => $cedula, 
                'cedula_actual' => $cedula_actual, 
                'Documento' => $documento, 
                'Telefono' => $telefono
        
            ];
                 /// Sanitización de Entradas
                foreach ($campos as $nombree => $valor) {  /* V3 */ 
                    if (!validarEntradaSQL($valor)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0400 - Entrada inválida detectada en el campo: $nombree"]);
                        exit;
                    }
                }   

                //Validar datos V4
                if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Cedula inválida"]);
                    exit;
                }
                if (!preg_match('/^[0-9]{7,8}$/', $cedula_actual)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Cedula inválida"]);
                    exit;
                }
               
                if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 200) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Correo inválido."]);
                    exit;
                }
                if (!filter_var($correo_actual, FILTER_VALIDATE_EMAIL) || strlen($correo_actual) < 5 || strlen($correo_actual) > 200) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Correo inválido."]);
                    exit;
                }
                
                if (!preg_match('/^[A-Za-z]{1}$/', $documento)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Documento inválido."]);
                    exit;
                }

                if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $telefono)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Teléfono inválido"]);
                    exit;
                }
                
                if (!preg_match('/^[A-Za-z]{3,20}$/', $nombre)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Nombre inválido"]);
                    exit;
                }
                
                if (!preg_match('/^[A-Za-z]{3,20}$/', $apellido)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Apellido inválido"]);
                    exit;
                }

                    if (!validarTipoDocumento($documento)) { // Validar tipo_documento
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0420 - El tipo de documento no es válido']);
                        exit;
                    }

                    $datosUsuario = [
                        'operacion' => 'actualizar',
                        'datos' => [
                            'nombre' => $nombre,
                            'apellido' => $apellido,
                            'cedula' => $cedula,
                            'correo' => $correo,
                            'telefono' => $telefono,
                            'cedula_actual' => $cedula_actual,
                            'correo_actual' => $correo_actual,
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
                    exit;
        } else{
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0200 - datos vacios']);
            exit;
        }

    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0100 - Session no encontrada']);
        exit;
    } 
} else if(isset($_POST['actualizarclave'])){ //  ||||||||||||||||||||||||||||||||||||||||||||||| ACTUALIZAR CLAVE

    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */

        if(!empty($_POST['clave']) && !empty($_POST['clavenueva'])){
            
            $clave = $_POST['clave']; $clavenueva = $_POST['clavenueva'];
            
            $campos = [
                'Clave' => $clave,
                'Clavenueva' => $clavenueva
            ];
                 /// Sanitización de Entradas
                foreach ($campos as $nombree => $valor) {  /* V3 */ 
                    if (!validarEntradaSQL($valor)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => "#0300 - Entrada inválida detectada en el campo: $nombree"]);
                        exit;
                    }
                }   
                    //Validar datos V4
                    if (!preg_match('/^[A-Za-z0-9\.\$\#\*\/]{8,16}$/', $clave)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => "#0410 - Clave inválida"]);
                        exit;
                    }

                    if (!preg_match('/^[A-Za-z0-9\.\$\#\*\/]{8,16}$/', $clavenueva)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => "#0410 - Clave (N) inválida"]);
                        exit;
                    }

                        $datosUsuario = [
                            'operacion' => 'actualizarclave',
                            'datos' => [
                                'id_usuario' => $_SESSION["id_usuario"],
                                'clave_actual' => $clave,
                                'clave' => $clavenueva
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
                        exit;
        }else{
            echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => '#0200 - Datos Vacios']);
            exit;
        }
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => '#0100 - Session no encontrada']);
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






