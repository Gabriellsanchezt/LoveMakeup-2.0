<?php  
use LoveMakeup\Proyecto\Modelo\Cliente; 
use LoveMakeup\Proyecto\Modelo\Bitacora;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
} 

require_once 'permiso.php';
$objcliente = new Cliente();


$registro = $objcliente->consultar();
$pedidos = $objcliente->consultarPedidos();

    function cedulaModificable($cedula, $registro) {
        foreach ($registro as $usuario) {
            if (trim((string)$usuario['cedula']) === trim((string)$cedula)) {
            
                if ($usuario['id_usuario'] == 1 || $usuario['id_usuario'] == 2) {
                    return false; // No se puede modificar
                }
                return true; // Existe 
            }
        }
        return false; 
    }

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

    function validarTipoDocumento($tipo_documento) {
        $tipos_validos = ['V', 'E', 'J'];
        return in_array($tipo_documento, $tipos_validos, true);
    }

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

if(isset($_POST['actualizar'])){ /*|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||   MODIFICAR DATOS DEL CLIENTE     */

    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */ 

        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(10, 'editar')) {/* V2 */ 
       
            if(!empty($_POST['cedula']) && !empty($_POST['correo']) && !empty($_POST['estatus']) && !empty($_POST['cedulaactual']) && !empty($_POST['tipo_documento']) && !empty($_POST['correoactual']) ){
             /* V3 */ 

                $Cedula=$_POST['cedula'];           $Correo=strtolower($_POST['correo']);         $Estatus=$_POST['estatus'];
                $CedulaActual=$_POST['cedulaactual'];     $Documento=$_POST['tipo_documento'];      $CorreoActual=$_POST['correoactual'];
        
                $campos = [
                    'Cedula' => $Cedula,
                    'Estatus' => $Estatus,
                    'CedulaActual' => $CedulaActual,
                    'Documento' => $Documento
                ];
                /// Sanitización de Entradas
                    foreach ($campos as $nombre => $valor) {  /* V4 */ 
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0400 - Entrada inválida detectada en el campo: $nombre"]);
                            exit;
                        }
                    }
                
                //// Validar Datos  V5
                if (!preg_match('/^[0-9]{7,8}$/', $Cedula)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Cedula inválida"]);
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

                        if (cedulaModificable($CedulaActual, $registro)) { // Validar si la cedula actual si existe en la BD

                            if (validarCorreoActual($registro, $CorreoActual)) { // Validar si la Correo actual si existe en la BD
                              
                                // datos para modificador los datos
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


                            } else { /// si la Correo actual no existia o esta protegida
                                echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0530 - Correo no encontrada O protegida']);
                                exit;
                            }
                            
                        } else {  /// si la cedula actual no existia o esta protegida
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0530 - Cedula no encontrada O protegida']);
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

} else if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(10, 'ver')) {
         $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Cliente'
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'cliente', $bitacora);
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'cliente';
        require_once 'vista/cliente.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

}
 
?>        