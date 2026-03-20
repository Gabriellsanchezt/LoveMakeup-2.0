<?php  

use LoveMakeup\Proyecto\Modelo\catalogo_datos;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$nombre = isset($_SESSION["nombre"]) && !empty($_SESSION["nombre"]) ? $_SESSION["nombre"] : "Estimado Cliente";
$apellido = isset($_SESSION["apellido"]) && !empty($_SESSION["apellido"]) ? $_SESSION["apellido"] : ""; 
$nombreCompleto = trim($nombre . " " . $apellido);
$sesion_activa = isset($_SESSION["id"]) && !empty($_SESSION["id"]);

if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
    require_once 'permiso.php';
}

$objdatos = new Catalogo_datos();

 function validarEntradaSQL($input) {
        // Si es array → validar cada elemento
        if (is_array($input)) {
            foreach ($input as $valor) {
                if (!validarEntradaSQL($valor)) { 
                    return false;
                }
            }
            return true;
        }
        // Convertir a string por seguridad
        $input = (string)$input;

        $blacklist = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER',
            'CREATE', 'RENAME', 'REPLACE', 'UNION', 'JOIN', 'WHERE', 'HAVING',
            'FROM', 'TABLE', 'DATABASE', 'SCHEMA', 'GRANT', 'REVOKE',
            '--', ';', '#', '/*', '*/', '@@', '@', 'CHAR', 'CAST', 'CONVERT',
            'EXEC', 'EXECUTE', 'xp_', 'sp_', 'OR', 'AND'
        ];
      
        foreach ($blacklist as $prohibida) {
            $pattern = '/\b' . preg_quote($prohibida, '/') . '\b/i'; 
            if (preg_match($pattern, $input)) {
                return false;
            }
        }
        return true;
    }


//Valida que el tipo_documento sea válido
function validarTipoDocumento($tipo_documento) {
    $tipos_validos = ['V', 'E'];
    return in_array($tipo_documento, $tipos_validos, true);
}

if (isset($_POST['actualizar'])) {

    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if ($_SESSION["nivel_rol"] == 1 && tieneAcceso(20, 3)) { 

        if(!empty($_POST['nombre']) &&!empty($_POST['apellido']) && !empty($_POST['cedula']) &&!empty($_POST['correo'])
         && !empty($_POST['telefono']) && !empty($_POST['tipo_documento']) && !empty($_POST['cedula_actual']) && !empty($_POST['correo_actual'])){ /* V2 */ 
          
            $nombre =  ucfirst(strtolower($_POST['nombre'])); $apellido = ucfirst(strtolower($_POST['apellido'])); $cedula = $_POST['cedula']; 
            $correo = strtolower($_POST['correo']);  $telefono = $_POST['telefono']; $documento = $_POST['tipo_documento'];
            $cedula_actual = $_POST['cedula_actual']; $correo_actual = strtolower($_POST['correo_actual']);

            $campos = [
                'Nombre' => $nombre,
                'Apellido' => $apellido,      
                'Cedula' => $cedula, 
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
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Cedula inválida"]);
                    exit;
                }
               
                if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 200) {
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

                // Validar tipo_documento
                if (!validarTipoDocumento($documento)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0420 - El tipo de documento no es válido']);
                    exit;
                }
        
                    $datosCliente = [
                        'operacion' => 'actualizar',
                        'datos' => [
                            'id_persona' => $_SESSION["id"],
                            'nombre' => $nombre,
                            'apellido' => $apellido,
                            'cedula' => $cedula,
                            'correo' => $correo,
                            'telefono' => $telefono,
                            'tipo_documento' => $documento,
                            'cedula_actual' =>$cedula_actual,
                            'correo_actual' => $correo_actual                        ]
                    ];

                    $resultado = $objdatos->procesarCliente(json_encode($datosCliente));
                
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
                echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0200 - Datos Vacios']);
                exit; 
            }
        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#3200 - No puedes realizar esta operacion, intente mas tarde']);
            exit;
        }  
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0100 - Session no encontrada']);
        exit;
    }     

} else if(isset($_POST['eliminar'])){ // ||||||||||||||||||||||||||||||||||||||||||||||||||||||| ELIMINAR CLIENTE 
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
      if ($_SESSION["nivel_rol"] == 1 && tieneAcceso(20, 4)) { 

        if(!empty($_POST['persona'])){/* V2 */
            $persona = $_POST['persona']; 
        
            $campos = [
                'Persona' => $persona
            ];
                 /// Sanitización de Entradas
                foreach ($campos as $nombree => $valor) {  /* V3 */ 
                    if (!validarEntradaSQL($valor)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "#0300 - Entrada inválida detectada en el campo: $nombree"]);
                        exit;
                    }
                }   

                //Validar datos V4
                if (!preg_match('/^[0-9]{7,8}$/', $persona)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "#0410 - Datos invalidos"]);
                    exit;
                }

                if($persona === $_SESSION['id']){

                    $datosCliente = [
                            'operacion' => 'eliminar',
                            'datos' => [
                                'id_usuario' => $_SESSION['id_usuario'],
                                'cedula' => $persona
                            ]
                    ];

                    $resultado = $objdatos->procesarCliente(json_encode($datosCliente));
                        
                        if ($resultado['respuesta'] == 1) {

                            echo json_encode($resultado);
                            session_destroy();
                            exit;
                        } else{

                            echo json_encode($resultado);
                            exit;
                        }

                } else{
                    echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'La datos de la persona no encontrados ']);
                    exit; 
                }
              
            } else{
                echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => '#0200 - datos vacios']);
                exit; 
            }

        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => '#3200 - No puedes realizar esta operacion, intente mas tarde']);
            exit;
        }  
    
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => '#0100 - Session no encontrada']);
        exit;
    }  
 
} else if(isset($_POST['actualizarclave'])){ //||||||||||||||||||||||||||||||||||||||||||||||||||||| ACTUALIZAR CLAVE
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
     if ($_SESSION["nivel_rol"] == 1 && tieneAcceso(20, 3)) { 

        if(!empty($_POST['clave'])&&!empty($_POST['clavenueva'])){ 

            $clave = $_POST['clave']; $clavenueva = $_POST['clavenueva'];

            $campos = [
                'Clave' => $clave,
                'Clavenueva' => $clavenueva
            ];
                 /// Sanitización de Entradas
                foreach ($campos as $nombre => $valor) {  /* V3 */ 
                    if (!validarEntradaSQL($valor)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => "#0300 - Entrada inválida detectada en el campo: $nombre"]);
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

                        $datosCliente = [
                            'operacion' => 'actualizarclave',
                                'datos' => [
                                    'id_usuario' => $_SESSION["id_usuario"],
                                    'clave_actual' => $_POST['clave'],
                                    'clave' => $_POST["clavenueva"]
                                ]
                        ];

                        $resultado = $objdatos->procesarCliente(json_encode($datosCliente));

                        echo json_encode($resultado);
                        exit;

            }else{ // datos vacios
                echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => '#0200 - Datos Vacios']);
                exit;
            }
        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => '#3200 - No puedes realizar esta operacion, intente mas tarde']);
            exit;
        }  

    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'clave', 'text' => '#0100 - Session no encontrada']);
        exit;
    }  
     
} if ($sesion_activa) {
     if($_SESSION["nivel_rol"] == 1  && tieneAcceso(20, 1))  { 
      require_once('vista/tienda/catalogo_datos.php');
    } else{
        header('Location: ?pagina=catalogo');
    }   
} else {
   header('Location: ?pagina=catalogo');
}

?>


