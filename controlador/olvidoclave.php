<?php  

use LoveMakeup\Proyecto\Modelo\Olvidoclave;

     // Iniciar sesión solo si no está ya iniciada
    if (session_status() === PHP_SESSION_NONE) {
         session_start();
    }
    
    if (empty($_SESSION["iduser"])){
       header("location:?pagina=login");
    } /*  Validacion URL  */
    
    if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
    } 
  
  $objolvido = new Olvidoclave();
  
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
  
  if (isset($_POST['cerrarolvido'])) {    
      session_destroy(); 
      header('Location: ?pagina=login');
      exit;

} else  if (isset($_POST['validar'])) {    
    if (isset($_SESSION['iduser']) && !empty($_SESSION['iduser'])) { /* V1 */
        if (!empty($_POST['correo'])) { 
               
                $correo = strtolower($_POST['correo']);    $correodato = $_SESSION['correos'];
                   
                //validar datos
                if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 200) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'validar', 'text' => "#0310 - Correo inválido."]);
                    exit;
                }

                if ($correo === $correodato) {
                    
                    $codigo_recuperacion = rand(100000, 999999);
                    $_SESSION['codigo_recuperacion'] = $codigo_recuperacion;
                    
                    // Enviar correo con el código
                    require_once 'modelo/enviarcorreo.php'; 
                    
                    enviarCodigoRecuperacion($correo, $codigo_recuperacion);
                    
                    echo json_encode(['respuesta' => 1, 'accion' => 'validar']);
                    exit;
                } else {
                    echo json_encode(['respuesta' => 0, 'accion' => 'validar', 'text' => 'El correo no encuentra en su registro.']);
                    exit;
                }

        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'validar', 'text' => '#0200 - Datos Vacios']);
            exit;
        }      
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'validar', 'text' => '#0100 - Session no encontrada']);
        exit;
    }            

}else if (isset($_POST['validarcodigo'])) {  
    if (isset($_SESSION['iduser']) && !empty($_SESSION['iduser'])) { /* V1 */
        if (!empty($_POST['codigo'])) { /* V2 */

            $codigo_ingresado = $_POST['codigo'];
            $codigo_guardado = isset($_SESSION['codigo_recuperacion']) ? $_SESSION['codigo_recuperacion'] : null;

            $campos = [
                'Codigo' => $codigo_ingresado
            ];
                 /// Sanitización de Entradas
                foreach ($campos as $nombre => $valor) {  /* V4 */ 
                    if (!validarEntradaSQL($valor)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'validarcodigo', 'text' => "#0300 - Entrada inválida detectada en el campo: $nombre"]);
                        exit;
                    }
                } 
                    //// Validar Datos  V5
                    if (!preg_match('/^[0-9]{6}$/', $codigo_ingresado)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'validarcodigo', 'text' => "#0410 - Formato inválido"]);
                        exit;
                    }

                        if ($codigo_guardado && $codigo_ingresado == $codigo_guardado) {
                            $res = array('respuesta' => 1, 'accion' => 'validarcodigo');
                        } else {
                            $res = array('respuesta' => 0, 'accion' => 'validarcodigo', 'text' => 'Código incorrecto.');
                        }

                        echo json_encode($res);

        } else{  /* V2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'validarcodigo', 'text' => '#0200 - Datos Vacios']);
            exit;
        }      
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'validarcodigo', 'text' => '#0100 - Session no encontrada']);
        exit;
    }  

} else if (isset($_POST['btnReenviar'])) {

    if (isset($_SESSION['iduser']) && !empty($_SESSION['iduser'])) { /* V1 */

        $correo = $_SESSION['correos'];

        if ($correo) {
            $codigo_recuperacion = rand(100000, 999999);
            $_SESSION['codigo_recuperacion'] = $codigo_recuperacion;

            require_once 'modelo/enviarcorreo.php';
            enviarCodigoRecuperacion($correo, $codigo_recuperacion);

            $res = array('respuesta' => 1, 'accion' => 'reenviar');
        } else {
            $res = array('respuesta' => 0, 'accion' => 'reenviar', 'text' => 'al obtener el correo');
        }
        echo json_encode($res);
        exit;

    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'reenviar', 'text' => '#0100 - Session no encontrada']);
        exit;
    } 

} else if(isset($_POST['validarclave'])){

    if (isset($_SESSION['iduser']) && !empty($_SESSION['iduser'])) { /* V1 */
        if (!empty($_POST['clavenueva'])) { /* V2 */
            $clavenueva = $_POST['clavenueva'];

            $campos = [
                'Clave' => $clavenueva
            ];
                 /// Sanitización de Entradas
                foreach ($campos as $nombre => $valor) {  /* V3 */ 
                    if (!validarEntradaSQL($valor)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0300 - Entrada inválida detectada en el campo: $nombre"]);
                        exit;
                    }
                } 
                    //// Validar Datos  V4
                    if (!preg_match('/^[A-Za-z0-9\.\$\#\*\/]{8,16}$/', $clavenueva)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0410 - Clave inválida"]);
                        exit;
                    }

                    $datosOlvido = [
                        'operacion' => 'actualizar',
                        'datos' => [
                            'cedula' => $_SESSION["cedula"],
                            'clave' => $clavenueva
                            
                        ]
                    ]; 

                    $resultado = $objolvido->procesarOlvido(json_encode($datosOlvido));
                    echo json_encode($resultado);

        } else{  /* V2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0200 - Datos Vacios']);
            exit;
        }      
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0100 - Session no encontrada']);
        exit;
    }  
    
} else{
    require_once 'vista/seguridad/olvidoclave.php';
}

?>