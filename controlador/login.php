<?php

use LoveMakeup\Proyecto\Modelo\Login;
use LoveMakeup\Proyecto\Modelo\Bitacora;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
session_start();
}

$objlogin = new Login();

function validarTipoDocumento($tipo_documento) {
        $tipos_validos = ['V', 'E','J'];
        return in_array($tipo_documento, $tipos_validos, true);
}

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

if (isset($_POST['ingresar'])) { /*|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||  INGRESAR AL SISTEMA */
    if (empty($_SESSION['id'])) { /* V1 */
        if ( !empty($_POST['fecha']) && !empty($_POST['usuario']) && !empty($_POST['clave'])&& !empty($_POST['tipo_documento'])) {

        $fecha = $_POST['fecha'];  $dolar = $_POST['tasa'];  
        $usuario = $_POST['usuario']; $clave = $_POST['clave'];  $documento = $_POST['tipo_documento'];

        $campos = [
            'Fecha' => $fecha,
            'Dolar' => $dolar,
            'Usuario' => $usuario,
            'Clave' => $clave,
            'Documento' => $documento
        ];
        /// Sanitización de Entradas
            foreach ($campos as $nombre => $valor) {  /* V2 */ 
                if (!validarEntradaSQL($valor)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => "#0200 - Entrada inválida detectada en el campo: $nombre"]);
                    exit;
                }
            }

                 //// Validar Datos  V3
                 if (!preg_match('/^[0-9]{7,8}$/', $usuario)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => "#0310 - Cedula inválida"]);
                    exit;
                }

                if (!preg_match('/^[A-Za-z]{1}$/', $documento)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => "#0310 - Documento inválido."]);
                    exit;
                }

                if (!preg_match('/^[A-Za-z0-9\.\$\#\*\/]{8,16}$/', $clave)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => "#0310 - Clave inválida"]);
                    exit;
                }

                $hoy = date('Y-m-d');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha < $hoy) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => "#0310 - Fecha inválida o menor a hoy ($fecha)"]);
                    exit;
                }

                if(!$dolar === 0){
                    if (!preg_match('/^\d{1,5}([.,]\d{1,3})?$/', $dolar) || strlen(str_replace([',','.'],'',$dolar)) < 4 || strlen(str_replace([',','.'],'',$dolar)) > 8) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => "#0310 - Tasa inválida ($dolar)"]);
                        exit;
                    }
                }
               
                // Validar tipo_documento
                if (!validarTipoDocumento($documento)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => '#0320 - El tipo de documento no es válido']);
                    exit;
                }
 
                $datosLogin = [
                    'operacion' => 'verificar',
                        'datos' => [
                            'tipo_documento' => $documento,
                            'cedula' => $usuario,
                            'clave' => $clave
                        ]
                ];
    
                $resultado = $objlogin->procesarLogin(json_encode($datosLogin));

                if ($resultado && isset($resultado->cedula)) { // VERIFICADOR QUE NO ESTE ACTIVO
                    if ((int)$resultado->estatus === 2) {
                        echo json_encode([
                            'respuesta' => 0,
                            'accion' => 'ingresar',
                            'text' => 'Lo sentimos, su cuenta está suspendida. Por favor, póngase en contacto con el administrador.'
                        ]);
                        exit;
                    }

                    if ((int)$resultado->estatus === 1) { // VERIFICADOR QUE SI ESTE ACTIVO
        
                        $_SESSION["id"] = $resultado->cedula;
                        $_SESSION["rol"] = $resultado->id_rol;

                        $id_persona = $_SESSION["rol"]; 
                        $resultadopermiso = $objlogin->consultar($id_persona);
                        $_SESSION["permisos"] = $resultadopermiso;

                        $_SESSION['id_usuario']= $resultado->id_usuario;
                        $_SESSION['documento']= $resultado->tipo_documento;
                        $_SESSION["nombre"] = $resultado->nombre;
                        $_SESSION["apellido"] = $resultado->apellido;
                        $_SESSION["nivel_rol"] = $resultado->nivel;
                        $_SESSION['nombre_usuario'] = $resultado->nombre_rol;
                        $_SESSION["telefono"] = $resultado->telefono;
                        $_SESSION["correo"] = $resultado->correo;

                            if($dolar >= 1){
                                $datosLogin = [
                                    'operacion' => 'dolar',
                                    'datos' => [
                                        'fecha' => $fecha,
                                        'tasa' => $dolar,
                                        'fuente' => 'Automatico'
                                    ]
                                ];
                                $resultado = $objlogin->procesarLogin(json_encode($datosLogin));
                            } 
                        
                            $resultadoT = $objlogin->consultaTasaUltima();
                            $_SESSION["tasa"] = $resultadoT;
                        
                            if ($_SESSION["nivel_rol"] == 1) {
                                echo json_encode(['respuesta' => 1, 'accion' => 'ingresar']);
                                exit;
                
                            } else if ($_SESSION["nivel_rol"] == 2 || $_SESSION["nivel_rol"] == 3) {
                                echo json_encode(['respuesta' => 2, 'accion' => 'ingresar']);
                                exit;

                            } else {
                                echo json_encode(['respuesta' => 0,'accion' => 'ingresar','text' => 'Su nivel de acceso no está definido.' ]);
                                exit;
                            }
                    }

                } else {
                    echo json_encode(['respuesta' => 0,'accion' => 'ingresar','text' => 'Cédula y/o Clave inválida.']);
                    exit;
                }
        } else{
            echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => '#0100 - DATOS VACIOS']);
            exit; 
        }
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => 'Session Activa']);
        exit;
    }   
// ------------------
} else if (isset($_POST['registrar'])) { /*|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| REGISTRO CLIENTE */
    if ( !empty($_POST['nombre']) && !empty($_POST['apellido']) && !empty($_POST['cedula']) && !empty($_POST['telefono']) && !empty($_POST['correo']) && !empty($_POST['tipo_documento']) && !empty($_POST['clave'])) {
    
        $nombre = $_POST['nombre'];  $apellido  = $_POST['apellido']; $cedulaR = $_POST['cedula'];
        $telefono  = $_POST['telefono']; $correoR = $_POST['correo']; $tipoDocumento = $_POST['tipo_documento'];
        $claveRegistro = $_POST['clave'];

        $campos = [
            'Nombre' => $nombre,
            'Apellido' => $apellido,
            'Cedula' => $cedulaR,
            'Telefono' => $telefono,
           // 'Correo' => $correoR,
            'Documento' => $tipoDocumento,
            'Clave' => $claveRegistro
        ];
        /// Sanitización de Entradas
            foreach ($campos as $nombree => $valor) {  /* V2 */ 
                if (!validarEntradaSQL($valor)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0200 - Entrada inválida detectada en el campo: $nombree"]);
                    exit;
                }
            }

                 //// Validar Datos  V5
                 if (!preg_match('/^[0-9]{7,8}$/', $cedulaR)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0310 - Cedula inválida"]);
                    exit;
                }
               
                if (!filter_var($correoR, FILTER_VALIDATE_EMAIL) || strlen($correoR) < 5 || strlen($correoR) > 200) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0310 - Correo inválido."]);
                    exit;
                }
                
                if (!preg_match('/^[A-Za-z]{1}$/', $tipoDocumento)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0310 - Documento inválido."]);
                    exit;
                }
                
                if (!preg_match('/^[A-Za-z0-9\.\$\#\*\/]{8,16}$/', $claveRegistro)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0310 - Clave inválida"]);
                    exit;
                }
                
                if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $telefono)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0310 - Teléfono inválido"]);
                    exit;
                }
                
                if (!preg_match('/^[A-Za-z]{3,20}$/', $nombre)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0310 - Nombre inválido"]);
                    exit;
                }
                
                if (!preg_match('/^[A-Za-z]{3,20}$/', $apellido)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0310 - Apellido inválido"]);
                    exit;
                }

                if (!validarTipoDocumento($tipoDocumento)) { // Validar tipo_documento
                    echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => '#0320 - El tipo de documento no es válido']);
                    exit;
                }

                $datosRegistro = [
                    'operacion' => 'registrar',
                    'datos' => [
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'cedula' => $cedulaR,
                        'telefono' => $telefono,
                        'correo' => $correoR,
                        'tipo_documento' => $tipoDocumento,
                        'clave' => $claveRegistro
                    ]
                ];
    
                $resultado = $objlogin->procesarLogin(json_encode($datosRegistro));
    
                /*if ($resultado['respuesta'] == 1) {
                    require_once 'modelo/CORREObienvenida.php';
                    $envio = enviarBienvenida($correoR);
                }*/
    
                echo json_encode($resultado);
                exit;
    }else{
        echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => '#0100 - DATOS VACIOS']);
        exit; 
    }
// -------------
} else if (isset($_POST['validarclave'])) {

    if(!empty($_POST['cedula'])&&!empty($_POST['tipo_documentos'])){

        $cedulaClave = $_POST['cedula'];  $documentoClave = $_POST['tipo_documentos'];

        $campos = [
            'Cedula' => $cedulaClave,
            'Documento' => $documentoClave
        ];
        /// Sanitización de Entradas
            foreach ($campos as $nombre => $valor) {  /* V2 */ 
                if (!validarEntradaSQL($valor)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'validarclave', 'text' => "#0200 - Entrada inválida detectada en el campo: $nombre"]);
                    exit;
                }
            }

             //// Validar Datos  V3
             if (!preg_match('/^[0-9]{7,8}$/', $cedulaClave)) {
                echo json_encode(['respuesta' => 0, 'accion' => 'validarclave', 'text' => "#0310 - Cedula inválida"]);
                exit;
            }
            
            if (!preg_match('/^[A-Za-z]{1}$/', $documentoClave)) {
                echo json_encode(['respuesta' => 0, 'accion' => 'validarclave', 'text' => "#0310 - Documento inválido."]);
                exit;
            }
     
            if (!validarTipoDocumento($documentoClave)) { // Validar tipo_documento
                echo json_encode(['respuesta' => 0, 'accion' => 'validarclave', 'text' => '#0320 - El tipo de documento no es válido']);
                exit;
            }   

                $datosValidar = [
                    'operacion' => 'validar',
                    'datos' => [
                        'cedula' => $cedulaClave,
                        'tipo_documento' => $documentoClave
                    ]
                ];

                $resultado = $objlogin->procesarLogin(json_encode($datosValidar));
               
                if ($resultado && isset($resultado->cedula)) {
                    $_SESSION["cedula"] = $resultado->cedula;
                    $_SESSION["nombres"] = $resultado->nombre;
                    $_SESSION["apellidos"] = $resultado->apellido;
                    $_SESSION["correos"] = $resultado->correo;
                    $_SESSION["iduser"] = 1;
                    $_SESSION["nivel"] = $resultado->nivel;
                        echo json_encode(['respuesta' => 1, 'accion' => 'validarclave']);
                        exit;
                } else {
                    echo json_encode(['respuesta' => 0, 'accion' => 'validarclave', 'text' => 'Cédula incorrecta o no hay registro']);
                    exit;
                }   
    }else{
        echo json_encode(['respuesta' => 0, 'accion' => 'validarclave', 'text' => '#0100 - DATOS VACIOS']);
        exit;
    }
 // ------------------
} else if(isset($_POST['cedula'])){ /* |||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| VERIFICAR CEDULA  */
    
    if (!empty($_POST['cedula']) ) {   /*  VACIOS   | VERIFICAR CEDULA   */
        $cedulaValidar = $_POST['cedula'];
        
        $campos = [
            'Cedula' => $cedulaValidar
        ];
        /// Sanitización de Entradas
            foreach ($campos as $nombre => $valor) {  /* V2 */ 
                if (!validarEntradaSQL($valor)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => "#0200 - Entrada inválida detectada en el campo: $nombre"]);
                    exit;
                }
            }

            //// Validar Datos  V3
            if (!preg_match('/^[0-9]{7,8}$/', $cedulaValidar)) {
                echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => "#0310 - Cedula inválida"]);
                exit;
            }

                if (ctype_digit($cedulaValidar)) {
                    $datosLogin = [
                        'operacion' => 'verificarcedula',
                        'datos' => [
                            'cedula' => $_POST['cedula']
                        ] 
                    ];

                    $resultado = $objlogin->procesarLogin(json_encode($datosLogin));
                    echo json_encode($resultado);
                    exit;
                } else {
                    echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => '#0320 - La cédula no es válida.']);
                    exit; 
                }

     }else{ /* DATOS VACIOS | VERIFICAR CEDULA  */
        echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => '#0100 - Datos Vacios']);
        exit; 
     }

} else if(isset($_POST['correo'])){ /* |||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| VERIFICAR COREREO  */

     if (!empty($_POST['correo']) ) {   /*  VACIOS   | VERIFICAR CORREO   */
        $correo = trim($_POST['correo']);

            // Validar datos V3
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 200) {
                echo json_encode(['respuesta' => 0, 'accion' => 'verificarcorreo', 'text' => "#0310 - Correo inválido."]);
                exit;
            }

            $datosLogin = [
                'operacion' => 'verificarCorreo',
                'datos' => [
                    'correo' => $correo
                ] 
            ];

            $resultado = $objlogin->procesarLogin(json_encode($datosLogin));
            echo json_encode($resultado);
            exit; 

     }else{ /* DATOS VACIOS | VERIFICAR CEDULA  */
        echo json_encode(['respuesta' => 0, 'accion' => 'verificarcorreo', 'text' => '#0100 - Datos Vacios']);
        exit; 
     }
//--------------------------------
} else if (isset($_POST['cerrarolvido'])) {    /*||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| CERRAR OLVIDO*/  
    session_destroy();
    header('Location: ?pagina=login');
    exit;
    
// ------------------
} else if (isset($_POST['cerrar'])) { /*||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| CERRAR SESSION*/
    
    // Registrar en bitácora si es administrador o asesora de venta
    if (isset($_SESSION["nivel_rol"]) && ($_SESSION["nivel_rol"] == 2 || $_SESSION["nivel_rol"] == 3)) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Cierre de sesión',
            'descripcion' => 'El usuario ha cerrado sesión desde el panel administrativo.'
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'login', $bitacora);
    }
    
    session_destroy();
    header('Location: ?pagina=login');
    exit;

// ------------------
} else if (!empty($_SESSION['id'])) { /*||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| CERRAR SESSION SI ENTRE POR URL*/
 
    if (isset($_SESSION["nivel_rol"]) && ($_SESSION["nivel_rol"] == 2 || $_SESSION["nivel_rol"] == 3)) {
    $bitacora = [
        'id_persona' => $_SESSION["id"],
        'accion' => 'Cierre de sesión',
        'descripcion' => 'El usuario ha cerrado sesión por URL.'
    ];
    $bitacoraObj = new Bitacora();
    $bitacoraObj->registrarOperacion($bitacora['accion'], 'login', $bitacora);
    }

    session_destroy();
    header('Location: ?pagina=login');
    exit;
//---------------------------------
} else {    
    require_once 'vista/login.php';
}

?>

