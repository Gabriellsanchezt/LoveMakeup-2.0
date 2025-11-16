<?php

use LoveMakeup\Proyecto\Modelo\Login;
use LoveMakeup\Proyecto\Modelo\Bitacora;

session_start();

$objlogin = new Login();

function validarTipoDocumento($tipo_documento) {
        $tipos_validos = ['V', 'E','J'];
        return in_array($tipo_documento, $tipos_validos, true);
}

if (isset($_POST['ingresar'])) { /*|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||  INGRESAR AL SISTEMA */
    
    if ( !empty($_POST['fecha']) && !empty($_POST['usuario']) && !empty($_POST['clave'])&& !empty($_POST['tipo_documento'])) {

        $fecha = $_POST['fecha'];  $dolar = $_POST['tasa'];  $usuario = $_POST['usuario'];
        $clave = $_POST['clave'];  $documento = $_POST['tipo_documento'];

         // Validar tipo_documento
        if (!validarTipoDocumento($documento)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => 'El tipo de documento no es válido']);
            exit;
        }

        if (isset($usuario) && ctype_digit($usuario)) {
            //--------------------------------------------------------
            $datosLogin = [
                'operacion' => 'verificar',
                    'datos' => [
                        'tipo_documento' => $documento,
                        'cedula' => $usuario,
                        'clave' => $clave
                    ]
                ];

                $resultado = $objlogin->procesarLogin(json_encode($datosLogin));

                if ($resultado && isset($resultado->cedula)) {
                    if ((int)$resultado->estatus === 2) {
                        echo json_encode([
                            'respuesta' => 0,
                            'accion' => 'ingresar',
                            'text' => 'Lo sentimos, su cuenta está suspendida. Por favor, póngase en contacto con el administrador.'
                        ]);
                        exit;
                    }

                    if ((int)$resultado->estatus === 1) {
        
                        $_SESSION["id"] = $resultado->cedula;

                        $id_persona = $_SESSION["id"]; 
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
                                        'fecha' => $_POST['fecha'],
                                        'tasa' => $_POST['tasa'],
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
                                echo json_encode([
                                    'respuesta' => 0,
                                    'accion' => 'ingresar',
                                    'text' => 'Su nivel de acceso no está definido.'
                                ]);
                                exit;
                            }
                    }

                } else if($resultado === "10"){
                        echo json_encode([
                            'respuesta' => 0,
                            'accion' => 'ingresar',
                            'text' => 'Error en Credenciales ERROR#10'
                        ]);

                } else {
                        echo json_encode([
                            'respuesta' => 0,
                            'accion' => 'ingresar',
                            'text' => 'Cédula y/o Clave inválida.'
                        ]);
                }
           // ----------------------------------------------------------    
        } else { /* CEDULA NO NUMERICA | ELIMINAR  */
            echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => 'La cédula no es válida. Debe contener solo números']);
            exit; 
        }
    } else{
        echo json_encode(['respuesta' => 0, 'accion' => 'ingresar', 'text' => 'DATOS VACIOS']);
        exit; 
    }
        
// ------------------
} else if (isset($_POST['registrar'])) { /*|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| REGISTRO CLIENTE */
    if ( !empty($_POST['nombre']) && !empty($_POST['apellido']) && !empty($_POST['cedula']) && !empty($_POST['telefono']) && !empty($_POST['correo']) && !empty($_POST['tipo_documento']) && !empty($_POST['clave'])) {
    
        $nombre = $_POST['nombre'];  $apellido  = $_POST['apellido']; $cedulaR = $_POST['cedula'];
        $telefono  = $_POST['telefono']; $correoR = $_POST['correo']; $tipoDocumento = $_POST['tipo_documento'];
        $claveRegistro = $_POST['clave'];

        // Validar tipo_documento
        if (!validarTipoDocumento($tipoDocumento)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => 'El tipo de documento no es válido']);
            exit;
        }

        if (ctype_digit($cedulaR) && filter_var($correoR, FILTER_VALIDATE_EMAIL) ) {
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

            if ($resultado['respuesta'] == 1) {
                require_once 'modelo/CORREObienvenida.php';
                $envio = enviarBienvenida($correoR);
            }

            echo json_encode($resultado);
            exit;
        }else{
            echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => 'Formatos invalidos cedula y correo']);
            exit; 
        }    
    }else{
        echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => 'DATOS VACIOS']);
        exit; 
    }
// -------------
} else if (isset($_POST['validarclave'])) {

    if(!empty($_POST['cedula'])&&!empty($_POST['tipo_documentos'])){

        $cedulaClave = $_POST['cedula'];
        $documentoClave = $_POST['tipo_documentos'];
     
      // Validar tipo_documento
        if (!validarTipoDocumento($documentoClave)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'validarclave', 'text' => 'El tipo de documento no es válido']);
            exit;
        }   

            if (ctype_digit($cedulaClave)) {
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
                    }
            }else{
                echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => 'Formatos invalidos cedula']);
                exit; 
            } 
    }else{
        echo json_encode(['respuesta' => 0, 'accion' => 'validarclave', 'text' => 'DATOS VACIOS']);
        exit;
    }
 // ------------------
} else if(isset($_POST['cedula'])){ /* |||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| VERIFICAR CEDULA  */
    
    if (!empty($_POST['cedula']) ) {   /*  VACIOS   | VERIFICAR CEDULA   */
        $cedulaValidar = $_POST['cedula'];
        
        if (isset($cedulaValidar) && ctype_digit($cedulaValidar)) {
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
            echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => 'La cédula no es válida. Debe contener solo números']);
            exit; 
        }

     }else{ /* DATOS VACIOS | VERIFICAR CEDULA  */
        echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => 'Datos Vacios']);
        exit; 
     }

} else if(isset($_POST['correo'])){ /* |||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| VERIFICAR COREREO  */

     if (!empty($_POST['correo']) ) {   /*  VACIOS   | VERIFICAR CORREO   */
        $correo = trim($_POST['correo']);

        if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $datosLogin = [
                'operacion' => 'verificarCorreo',
                'datos' => [
                    'correo' => $correo
                ] 
            ];

            $resultado = $objlogin->procesarLogin(json_encode($datosLogin));
            echo json_encode($resultado);
            exit; 
        } else {
            echo json_encode(['respuesta' => 0, 'accion' => 'verificarcorreo', 'text' => 'Correo inválido']);
            exit; 
        }

     }else{ /* DATOS VACIOS | VERIFICAR CEDULA  */
        echo json_encode(['respuesta' => 0, 'accion' => 'verificarcorreo', 'text' => 'Datos Vacios']);
        exit; 
     }
//--------------------------------
} else if (isset($_POST['cerrarolvido'])) {    /*||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| CERRAR OLVIDO*/  
    session_destroy();
    header('Location: ?pagina=login');
    exit;
    
// ------------------
} else if (isset($_POST['cerrar'])) { /*||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||| CERRAR SESSION*/
    
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