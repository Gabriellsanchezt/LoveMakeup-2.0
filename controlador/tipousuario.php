<?php
// controlador/tipousuario.php
use LoveMakeup\Proyecto\Modelo\TipoUsuario;
use LoveMakeup\Proyecto\Modelo\Bitacora;
//-----------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//----------------
if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
}
//------------------
require_once 'permiso.php';
$objRol = new TipoUsuario();
//---------------------------
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
//--------------------
if (isset($_POST['registrar'])) { //-------------------------------------------------- [ REGISTRAR ROL ]
//-------------------
     if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { // Validacion 1
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(17, 2)) { // Validacion 2
            if(!empty($_POST['nombreRol']) && !empty($_POST['nivelRol'])){ // Validacion 3

                $nombre = $_POST['nombreRol']; $nivel = $_POST['nivelRol'];
                
                $campos = [
                    'nombre' => $nombre,
                    'nivel' => $nivel
                ];
                     /// Sanitización de Entradas
                    foreach ($campos as $nombree => $valor) {  // Validacion  4
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "Entrada inválida detectada en el campo: $nombree"]);
                            exit;
                        }
                    } 

                    if (!preg_match('/^[A-Za-z]{3,20}$/', $nombre)) { // Validacion 5
                            echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0510 - Nombre inválido"]);
                            exit;
                    }
                    
                    if (!preg_match('/^[2-3]{1}$/', $nivel)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "#0510 - nivel inválido"]);
                            exit;
                    }

                    $datosRol = [
                        'operacion' => 'registrar',
                        'datos' => [
                            'nombre' =>  $nombre,
                            'nivel' =>  $nivel
                        ] 
                    ];

                    $resultado = $objRol->procesarRol(json_encode($datosRol));

                    if ($resultado['respuesta'] == 1) { // Validacion de Registro Bitacora
                        $bitacora = [
                            'id_persona' => $_SESSION["id"],
                            'accion' => 'Registrat tipo usuario',
                            'descripcion' => 'Se Registrado el tipo usuario: ' . 
                                             ' Cédula: ' . $datosRol['datos']['nombre'] . 
                                            ' Correo: ' . $datosRol['datos']['nivel']
                        ];
                        $bitacoraObj = new Bitacora();
                        $bitacoraObj->registrarOperacion($bitacora['accion'], 'tipousuario', $bitacora);
                    }

                    echo json_encode($resultado);
                    exit; 

            } else { // Validacion 3 - Error 
                echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "Datos Vacios"]);
                exit;
            }
        } else{ // Validacion 2 - Error 
            echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => 'No Tiene Permiso para realizar esta operacion']);
            exit;
        }      
    } else{ // Validacion 1 - Error 
        echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => 'Session no encontrada']);
        exit;
    } 
//---------------
} else if(isset($_POST['modificar'])){ //----------------------------------------------------- [BUSCAR PERMISOS Y IR A LA VISTA]
//---------------
     if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { // Validacion 1
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(17, 5)) { // Validacion 2
            if (!empty($_POST['modificar']) && !empty($_POST['RolNombre'])) {   /* VACIOS   | VER LOS PERMISOS  */

                $id_rol = $_POST['modificar']; $nombre_usuario = $_POST['RolNombre']; $usuario = $_SESSION['id_usuario'];

                $campos = [
                    'id_rol' => $id_rol
                ];
                     /// Sanitización de Entradas
                    foreach ($campos as $nombree => $valor) {  // Validacion  4
                        if (!validarEntradaSQL($valor)) {
                            /*
                            echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => "Entrada inválida detectada en el campo: $nombree"]);
                            exit;
                            */
                            header("location:?pagina=tipousuario");
                            exit;
                        }
                    } 

                    if (!preg_match('/^[0-9]{1,6}$/', $id_rol)) {  // Validacion 5
                           // echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - rol inválido"]);
                            //exit;
                             header("location:?pagina=tipousuario");
                            exit;
                    }

                
                    if ($id_rol == 4 || $id_rol == 1) {
                        header("location:?pagina=tipousuario");
                        exit;
                    }

                    $modificar = $objRol->buscar($id_rol);
                    if ($modificar) {
                        // Si hay datos, obtenemos el nivel y cargamos la vista
                        $nivel_usuario = $objRol->obtenerNivelPorId($usuario);
                        require_once("vista/seguridad/permiso.php");
                    } else {
                    // no se encontro datos 
                    header("location:?pagina=tipousuario");
                    exit;
                    }
                

            } else{  /* DATOS VACIOS | VER LOS PERMISOS  */
                header("location:?pagina=tipousuario");
                exit;
            }  
        } else{ // Validacion 2 - Error 
            //echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => 'No Tiene Permiso para realizar esta operacion']);
            //exit;
            header("location:?pagina=tipousuario");
                exit;
        }      
    } else{ // Validacion 1 - Error 
        //echo json_encode(['respuesta' => 0, 'accion' => 'registrar', 'text' => 'Session no encontrada']);
        //exit;
        header("location:?pagina=tipousuario");
                exit;
    }
///---------       
} else if (isset($_POST['actualizar_permisos'])) { //-----------------------------------------------[ ACTUALIZAR PERMISOS ]
 //---------
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { // Validacion 1
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(17, 3)) { // Validacion 2 - Permisos
            if (!empty($_POST['permiso']) && !empty($_POST['permiso_id'])) {  // Validacion 3    

            // Permisos enviados desde la vista
            $permisosRecibidos = $_POST['permiso'] ?? [];      // switches activos
            $permisosId = $_POST['permiso_id'] ?? [];          // id_permiso_rol existentes
            
            // VALIDAR $permisosRecibidos
            foreach ($permisosRecibidos as $modulo_id => $permisosModulo) {

                if (!preg_match('/^[0-9]+$/', $modulo_id)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => "#0511 - módulo inválido"]);
                    exit;
                }
                foreach ($permisosModulo as $id_permiso => $valor) {
                    // id_permiso debe ser numérico
                    if (!preg_match('/^[0-9]+$/', $id_permiso)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => "#0512 - Nro permiso inválido"]);
                        exit;
                    }
                   
                }
            }
            // VALIDAR $permisosId
            foreach ($permisosId as $modulo_id => $permisosModulo) {

                if (!preg_match('/^[0-9]+$/', $modulo_id)) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => "#0514 - módulo inválido en permiso_Nro"]);
                    exit;
                }

                foreach ($permisosModulo as $id_permiso => $id_permiso_rol) {

                    if (!preg_match('/^[0-9]+$/', $id_permiso)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => "#0515 - NRO permiso inválido en permiso"]);
                        exit;
                    }

                    if (!preg_match('/^[0-9]+$/', $id_permiso_rol)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => "#0516 - Nro permiso rol inválido"]);
                        exit;
                    }
                }
            }

            $listaPermisos = [];
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

                $datosPermiso = [
                    'operacion' => 'actualizar_permisos',
                    'datos' => $listaPermisos
                ];

                // Procesar actualización
                $resultado = $objRol->procesarRol(json_encode($datosPermiso));
                echo json_encode($resultado);
                exit;

            } else { // Validacion 3 - Error 
                echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => "Datos Vacios"]);
                exit;
            }    
        } else{ // Validacion 2 - Error 
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => 'No Tiene Permiso para realizar esta operacion']);
            exit;
        }      
    } else{ // Validacion 1 - Error 
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => 'Session no encontrada']);
        exit;
    } 
//-------    
}else if(isset($_POST['actualizar'])){ //------------------------------------------------ [ ACTUALIZAR DATOS ]
 //---------
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { // Validacion 1
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(17, 3)) { // Validacion 2   
            if(!empty($_POST['id_rol']) && !empty($_POST['nombre']) && !empty($_POST['nivel']) && !empty($_POST['nivel_actual'])){  // Validacion 3

                $id_rol = $_POST['id_rol'];  $nombre = $_POST['nombre'];  $nivel = $_POST['nivel']; $nivel_actual = $_POST['nivel_actual'];
                
                $campos = [
                    'nombre' => $nombre,
                    'nivel' => $nivel,
                    'id_rol' => $id_rol,
                    'nivel_actual' => $nivel_actual
                ];
                     /// Sanitización de Entradas
                    foreach ($campos as $nombree => $valor) {  // Validacion  4
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "Entrada inválida detectada en el campo: $nombree"]);
                            exit;
                        }
                    } 

                    if (!preg_match('/^[A-Za-z]{3,20}$/', $nombre)) { // Validacion 5
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Nombre inválido"]);
                            exit;
                    }
                    
                    if (!preg_match('/^[2-3]{1}$/', $nivel)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - nivel inválido"]);
                            exit;
                    }

                    if (!preg_match('/^[2-3]{1}$/', $nivel_actual)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - nivel Actual inválido"]);
                            exit;
                    }

                    if (!preg_match('/^[0-9]{1,6}$/', $id_rol)) {  // Validacion 5
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - rol inválido"]);
                            exit;
                    }

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

   
            } else { // Validacion 3 - Error 
                echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "Datos Vacios"]);
                exit;
            }
        } else{ // Validacion 2 - Error 
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No Tiene Permiso para realizar esta operacion']);
            exit;
        }      
    } else{ // Validacion 1 - Error 
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'Session no encontrada']);
        exit;
    } 
//---------
} else if(isset($_POST['eliminar'])){ //-------------------------------------------------- [ ELIMINAR ]
//---------
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { // Validacion 1
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(17, 4)) { // Validacion 2
            if(!empty($_POST['id_rol'])){ // Validacion 3

            $id_rol = $_POST['id_rol'];

            $campos = [
                    'id_rol' => $id_rol
                ];
                     /// Sanitización de Entradas
                    foreach ($campos as $nombree => $valor) {  // Validacion  4
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "Entrada inválida detectada en el campo: $nombree"]);
                            exit;
                        }
                    } 
                    
                    if (!preg_match('/^[0-9]{1,6}$/', $id_rol)) {  // Validacion 5
                            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "#0510 - nivel inválido"]);
                            exit;
                    }

                    if($id_rol == 1 || $id_rol == 2 || $id_rol == 3 || $id_rol == 4 ){
                        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "Tipo de usuario restringidos, no se pueden eliminar"]);
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

            } else { // Validacion 3 - Error 
                echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "Datos Vacios"]);
                exit;
            }
        } else{ // Validacion 2 - Error 
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'No Tiene Permiso para realizar esta operacion']);
            exit;
        }      
    } else{ // Validacion 1 - Error 
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'Session no encontrada']);
        exit;
    } 
//------
} else if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(17, 1)) { //----------------------------- [ VISTA ]
//------      
        $registro = $objRol->consultar();
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'tipousuario';

        require_once 'vista/tipousuario.php';
//-------        
} else {
        require_once 'vista/seguridad/privilegio.php';

} 