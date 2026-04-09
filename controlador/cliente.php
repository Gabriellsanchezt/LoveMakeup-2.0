<?php  
use LoveMakeup\Proyecto\Modelo\Cliente; 
use LoveMakeup\Proyecto\Modelo\Bitacora;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//--
if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
}
//-----
if (!isset($_SESSION['limite_cliente'])) {
    $_SESSION['limite_cliente'] = 100;
}
//--------
if (isset($_POST['ver_mas'])) {
    $_SESSION['limite_cliente'] += 100;
    header("location:?pagina=cliente");
    exit;
}
//---
require_once 'permiso.php'; 
$objcliente = new Cliente();
//---
$registro = $objcliente->consultar($_SESSION['limite_cliente']);
$total_registros = $objcliente->contarTotal();
$pedidos = $objcliente->consultarPedidos();
//----
    function validarCorreoActual(array $registro, string $correoActual): bool {
        foreach ($registro as $usuario) {
         
            if (strtolower($usuario['correo']) === strtolower($correoActual)) {
               
                if ($usuario['id_usuario'] == 1 || $usuario['id_usuario'] == 2) {
                    return false; 
                }
                return true; // Está registrado y permitido
            }
        }
   
        return false;
    }
//-----
    function validarTipoDocumento($tipo_documento) {
        $tipos_validos = ['V', 'E', 'J'];
        return in_array($tipo_documento, $tipos_validos, true);
    }
//-----
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
//-----
if(isset($_POST['actualizar'])){ //--------------------------------- ACTUALIZAR DATOS DEL CLIENTES
//-------
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { // Validacion 1
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(10, 3)) { // Validacion 2
            if(!empty($_POST['cedula']) && !empty($_POST['correo']) && !empty($_POST['estatus']) && !empty($_POST['cedulaactual']) && !empty($_POST['tipo_documento']) && !empty($_POST['correoactual']) ){
            // Validacion 3

                $Cedula=$_POST['cedula'];           $Correo=strtolower($_POST['correo']);         $Estatus=$_POST['estatus'];
                $CedulaActual=$_POST['cedulaactual'];     $Documento=$_POST['tipo_documento'];      $CorreoActual=$_POST['correoactual'];
        
                $campos = [
                    'Cedula' => $Cedula,
                    'Estatus' => $Estatus,
                    'CedulaActual' => $CedulaActual,
                    'Documento' => $Documento
                ];
                /// Sanitización de Entradas
                    foreach ($campos as $nombre => $valor) { // Validacion 4
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0400 - Entrada inválida detectada en el campo: $nombre"]);
                            exit;
                        }
                    }
                
                //// Validar Datos - Validacion 5
                if (!preg_match('/^[0-9]{7,8}$/', $Cedula)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Cedula inválida"]);
                    exit;
                }
                
                if (!filter_var($Correo, FILTER_VALIDATE_EMAIL) || strlen($Correo) < 5 || strlen($Correo) > 200) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Correo inválido."]);
                    exit;
                }
                
                if (!preg_match('/^[0-9]{1}$/', $Estatus)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Estatus inválido."]);
                    exit;
                }
                
                if (!preg_match('/^[A-Za-z]{1}$/', $Documento)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Documento inválido."]);
                    exit;
                }
                
                if (!preg_match('/^[0-9]{7,8}$/', $CedulaActual)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Cedula inválido."]);
                    exit;
                }
                
                if (!filter_var($CorreoActual, FILTER_VALIDATE_EMAIL) || strlen($CorreoActual) < 5 || strlen($CorreoActual) > 200) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Correo inválido."]);
                    exit;
                }
                
                    if (!validarTipoDocumento($Documento)) {  
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0520 - El tipo de documento no es válido']);
                        exit;
                    }
            
                    if (!in_array($Estatus, [1, 2])) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0520 - El estatus no es válido']);
                        exit;
                    }

                        //VALIDACION EXISTENTE
                        $datosCliente = ['operacion' => 'verificar','datos' => ['cedula' => $CedulaActual]  ];
        
                        $resultadoVerificacion =$objcliente->procesarCliente(json_encode($datosCliente));
                        if ($resultadoVerificacion['respuesta'] == 0) {
                             echo json_encode([ 'respuesta' => 0, 'accion' => 'actualizar', 'text' => '530 - Cedula no existente' ]);
                              exit; 
                        } 
                    
                            if (validarCorreoActual($registro, $CorreoActual)) { // Validar si la Correo actual si existe en la BD
                              
                                // Envio al Modulo
                                $datosCliente = [
                                    'operacion' => 'actualizar',
                                    'datos' => [
                                        'cedula' => $Cedula,
                                        'correo' => $Correo,
                                        'estatus' => $Estatus,
                                        'cedula_actual' => $CedulaActual,
                                        'tipo_documento' => $Documento,
                                        'correo_actual' => $CorreoActual
                                    ]
                                ];  
                    
                                $resultado = $objcliente->procesarCliente(json_encode($datosCliente)); // Resultado 
                        
                                    if ($resultado['respuesta'] == 1) {   // Bitacora
                                        $bitacora = [
                                            'id_persona' => $_SESSION["id"],
                                            'accion' => 'Modificación de cliente',
                                            'descripcion' => 'Se modificó el cliente con ID: ' . $datosCliente['datos']['cedula_actual'] . 
                                                        ' Cédula: ' . $datosCliente['datos']['cedula'] . 
                                                        ' Correo: ' . $datosCliente['datos']['correo']
                                        ];
                                        $bitacoraObj = new Bitacora();
                                        $bitacoraObj->registrarOperacion($bitacora['accion'], 'cliente', $bitacora);
                                    }
                    
                                echo json_encode($resultado); /// RESULTADO DE LA MODIFICACION
                                // Fin del envio modulo

                            } else { /// si la Correo actual no existia o esta protegida
                                echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0530 - Correo no encontrada O protegida']);
                                exit;
                            }

            } else{  /* 3 */ 
                echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0300 - Datos enviados estan vacios']);
                exit;
            }   
        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0200 - No Tiene Permiso para realizar esta operacion']);
            exit;
        }  
    } else{ /* 1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0100 - Session no encontrada']);
        exit;
    }
//-----------
} else if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(10, 1)) { //------------ VISTA
//-----------    
            $bitacora = [
                'id_persona' => $_SESSION["id"],
                'accion' => 'Acceso a Módulo',
                'descripcion' => 'módulo de Cliente'
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bitacora['accion'], 'cliente', $bitacora);
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'cliente';
        require_once 'vista/cliente.php';
//---------        
} else {
        require_once 'vista/seguridad/privilegio.php';

}
 
?>        