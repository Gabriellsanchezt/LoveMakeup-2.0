<?php  
use LoveMakeup\Proyecto\Modelo\Usuario;
use LoveMakeup\Proyecto\Modelo\Bitacora;
// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
}
//---- 
require_once 'permiso.php';
$objusuario = new Usuario();
//-----
if (!isset($_SESSION['limite_usuario'])) {
    $_SESSION['limite_usuario'] = 100;
}
//--------
if (isset($_POST['ver_mas'])) {
    $_SESSION['limite_usuario'] += 100;
    header("location:?pagina=usuario");
    exit;
}
//---
$rol = $objusuario->obtenerRol();
$roll = $objusuario->obtenerRol();
//----
$registro = $objusuario->consultar($_SESSION['limite_usuario']);
$total_registros = $objusuario->contarTotal(); 
//--
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
//---- FUNCIONES DE SELECT
    // Valida que el id_rol sea válido y exista en la base de datos
    function validarIdRol($id_rol, $roles) {
        if (empty($id_rol) || !is_numeric($id_rol)) {
            return false;
        }
        $id_rol = (int)$id_rol;
        foreach ($roles as $rol) {
            if ($rol['id_rol'] == $id_rol && $rol['estatus'] >= 1 && $rol['id_rol'] > 1) {
                return true;
            }
        }
        return false;
    }
//----
    // Valida que el nivel corresponda al id_rol seleccionado
    function validarNivel($id_rol, $nivel, $roles) {
        if (empty($nivel) || !is_numeric($nivel)) {
            return false;
        }
        $nivel = (int)$nivel;
        foreach ($roles as $rol) {
            if ($rol['id_rol'] == $id_rol && $rol['nivel'] == $nivel) {
                return true;
            }
        }
        return false;
    }
//---
    // Valida que el tipo_documento sea válido
    function validarTipoDocumento($tipo_documento) {
        $tipos_validos = ['V', 'E'];
        return in_array($tipo_documento, $tipos_validos, true);
    }
//---   
    // Valida que el estatus sea válido
    function validarEstatus($estatus) {
        if (empty($estatus) || !is_numeric($estatus)) {
            return false;
        }
        $estatus = (int)$estatus;
        $estatus_validos = [1, 2, 3];
        return in_array($estatus, $estatus_validos, true);
    }
//---
    // Obtiene el nivel correspondiente a un id_rol
    function obtenerNivelPorRol($id_rol, $roles) {
        foreach ($roles as $rol) {
            if ($rol['id_rol'] == $id_rol) {
                return $rol['nivel'];
            }
        }
        return null;
    }
//---
if (isset($_POST['registrar'])) { //---------------------------------- REGISTRAR USUARIO 
//----    
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(16, 2)) { /* V2 */ 

            if (!empty($_POST['nombre']) && !empty($_POST['apellido']) && !empty($_POST['cedula']) && !empty($_POST['telefono']) 
                && !empty($_POST['correo']) && !empty($_POST['id_rol']) && !empty($_POST['clave'])) {  /* V3 */

                $nombre = ucfirst(strtolower($_POST['nombre'])); $apellido = ucfirst(strtolower($_POST['apellido'])); $cedula =  $_POST['cedula'];
                $documento = $_POST['tipo_documento']; $telefono = $_POST['telefono']; $correo = strtolower($_POST['correo']); $clave = $_POST['clave'];
                $id_rol = (int)$_POST['id_rol']; 
  
                $campos = [
                    'Nombre' => $nombre,
                    'Apellido' => $apellido,      
                    'Cedula' => $cedula, 
                    'Documento' => $documento, 
                    'Telefono' => $telefono,
                    'Clave' => $clave,
                    'Id_rol' => $id_rol 
                ];
                     /// Sanitización de Entradas
                    foreach ($campos as $nombree => $valor) {  /* V4 */ 
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "Entrada inválida detectada en el campo: $nombree"]);
                            exit;
                        }
                    } 
                        //// Validar Datos  V5
                        if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "#0510 - Cedula inválida"]);
                            exit;
                        }
                       
                        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 200) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "#0510 - Correo inválido."]);
                            exit;
                        }
                        
                        if (!preg_match('/^[A-Za-z]{1}$/', $documento)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "#0510 - Documento inválido."]);
                            exit;
                        }
                    
                        if (!preg_match('/^[0-9]{1,3}$/', $id_rol)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "#0510 - rol inválido"]);
                            exit;
                        }
                        
                        if (!preg_match('/^[A-Za-z0-9\.\$\#\*\/]{8,16}$/', $clave)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "#0510 - Clave inválida"]);
                            exit;
                        }
                        
                        if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $telefono)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "#0510 - Teléfono inválido"]);
                            exit;
                        }
                        
                        if (!preg_match('/^[A-Za-z]{3,20}$/', $nombre)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "#0510 - Nombre inválido"]);
                            exit;
                        }
                        
                        if (!preg_match('/^[A-Za-z]{3,20}$/', $apellido)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => "#0510 - Apellido inválido"]);
                            exit;
                        }

                        if (!validarTipoDocumento($_POST['tipo_documento'])) {   // Validar tipo_documento
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'El tipo de documento no es válido']);
                            exit;
                        }

                        // Validar y corregir nivel según el id_rol (por seguridad, ignoramos el nivel enviado y usamos el del rol)
                        $nivel_valido = obtenerNivelPorRol($_POST['id_rol'], $rol);
                        if ($nivel_valido === null) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'No se pudo obtener el nivel del rol']);
                            exit;
                        }

                        //VALIDACION EXISTENTE
                        // USUARIO EXISTE
                        $datosUsuario = ['operacion' => 'verificar','datos' => ['cedula' => $cedula]  ];
        
                        $resultadoVerificacion = $objusuario->procesarUsuario(json_encode($datosUsuario));
                        if ($resultadoVerificacion['respuesta'] == 1) {
                             echo json_encode([ 'respuesta' => 0, 'accion' => 'incluir', 'text' => '530 - La cédula ya está registrada' ]);
                              exit; 
                        }

                        // ROL EXISTE
                        $datosUsuario = ['operacion' => 'verificarrol','datos' => ['id_rol' => $id_rol]  ];
        
                        $resultadoVerificacion1 = $objusuario->procesarUsuario(json_encode($datosUsuario));
                        if ($resultadoVerificacion1['respuesta'] == 0) {
                             echo json_encode([ 'respuesta' => 0, 'accion' => 'actualizar', 'text' => '530 - ROL no existente' ]);
                              exit; 
                        }

                            $datosUsuario = [
                                'operacion' => 'registrar',
                                    'datos' => [
                                        'nombre' => $nombre,
                                        'apellido' => $apellido,
                                        'cedula' => $cedula,
                                        'tipo_documento' => $documento,
                                        'telefono' => $telefono,
                                        'correo' => $correo,
                                        'clave' => $clave,
                                        'id_rol' => $id_rol, 
                                        'nivel' => $nivel_valido
                                    ]
                            ];
            
                            $resultadoRegistro = $objusuario->procesarUsuario(json_encode($datosUsuario));
                        
                                if ($resultadoRegistro['respuesta'] == 1) {
                                    $bitacora = [
                                        'id_persona' => $_SESSION["id"],
                                        'accion' => 'Modificación de usuario',
                                        'descripcion' => 'Se Registrado el usuario: ' . 
                                                    ' Cédula: ' . $datosUsuario['datos']['cedula'] . 
                                                    ' Correo: ' . $datosUsuario['datos']['correo']
                                    ];
                                    $bitacoraObj = new Bitacora();
                                    $bitacoraObj->registrarOperacion($bitacora['accion'], 'usuario', $bitacora);
                                }

                            echo json_encode($resultadoRegistro); /// RESULTADO DE LA REGISTRO
                            exit;

            } else{  /* V3 datos vacios */
                echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'Datos Vacios']);
                exit; 
            }
        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'No Tiene Permiso para realizar esta operacion']);
            exit;
        }      
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'Session no encontrada']);
        exit;
    } 
//---
} else if(isset($_POST['actualizar'])){ //---------------------------- ACTUALIZAR DATOS DEL USUARIO
//---
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(16, 3)) { /* V2 */ 

            if (!empty($_POST['id_persona']) && !empty($_POST['cedula']) && !empty($_POST['correo']) && !empty($_POST['id_rol']) &&
            !empty($_POST['estatus']) && !empty($_POST['cedulaactual']) && !empty($_POST['correoactual']) && !empty($_POST['rol_actual']) &&
            !empty($_POST['tipo_documento'])) { /* V3 VACIOS  */

            $id_persona = $_POST['id_persona'];   $cedula = $_POST['cedula']; $correo = strtolower($_POST['correo']);
            $id_rol = (int)$_POST['id_rol'];  $estatus  = (int)$_POST['estatus'];  $cedula_actual = $_POST['cedulaactual'];
            $correo_actual = strtolower($_POST['correoactual']);  $rol_actual = (int)$_POST['rol_actual']; $tipo_documento = $_POST['tipo_documento'];
    
            $campos = [
                'Id_persona' => $id_persona,
                'Cedula' => $cedula,      
                'Id_rol' => $id_rol,
                'Estatus' => $estatus,
                'Cedula_actual' => $cedula_actual,
                'rol_actual' => $rol_actual,
                'tipo_documento' => $tipo_documento
            ];
            /// Sanitización de Entradas
                foreach ($campos as $nombre => $valor) {  /* V4 */ 
                    if (!validarEntradaSQL($valor)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0400 - Entrada inválida detectada en el campo: $nombre"]);
                        exit;
                    }
                }

                    //// Validar Datos  V5
                    if (!preg_match('/^[0-9]{1,8}$/', $id_persona)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar-', 'text' => "#0510 - (E) inválida"]);
                        exit;
                    }

                    if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Cedula inválida"]);
                        exit;
                    }

                    if (!preg_match('/^[0-9]{7,8}$/', $cedula_actual)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Cedula (A) inválida"]);
                        exit;
                    }
                    
                    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 200) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Correo inválido."]);
                        exit;
                    }

                    if (!filter_var($correo_actual, FILTER_VALIDATE_EMAIL) || strlen($correo_actual) < 5 || strlen($correo_actual) > 200) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Correo (A) inválido."]);
                        exit;
                    }
                    
                    if (!preg_match('/^[0-9]{1}$/', $estatus)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Estatus inválido."]);
                        exit;
                    }
                    
                    if (!preg_match('/^[A-Za-z]{1}$/', $tipo_documento)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - Documento inválido."]);
                        exit;
                    }
                
                    if (!preg_match('/^[0-9]{1,6}$/', $id_rol)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - rol inválido"]);
                        exit;
                    }

                    if (!preg_match('/^[0-9]{1,6}$/', $rol_actual)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => "#0510 - rol inválido"]);
                        exit;
                    }
                 
                        if (!validarTipoDocumento($_POST['tipo_documento'])) {    // Validar tipo_documento
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0520 -El tipo de documento no es válido']);
                            exit;
                        }

                        if (!validarEstatus($_POST['estatus'])) {    // Validar estatus
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0520 - El estatus seleccionado no es válido']);
                            exit;
                        }

                        // Validar y corregir nivel según el id_rol (por seguridad, ignoramos el nivel enviado y usamos el del rol)
                       /* $nivel_valido = obtenerNivelPorRol($id_rol, $roll);
                        
                        if ($nivel_valido === null) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0520 -No se pudo obtener el nivel del rol']);
                            exit;
                        }*/

                        //VALIDACION EXISTENTE
                        // USUARIO EXISTE
                        $datosUsuario = ['operacion' => 'verificar','datos' => ['cedula' => $cedula_actual]  ];
        
                        $resultadoVerificacion = $objusuario->procesarUsuario(json_encode($datosUsuario));
                        if ($resultadoVerificacion['respuesta'] == 0) {
                             echo json_encode([ 'respuesta' => 0, 'accion' => 'actualizar', 'text' => '530 - Cedula no existente' ]);
                              exit; 
                        }
                        
                        // ROL EXISTE
                        $datosUsuario = ['operacion' => 'verificarrol','datos' => ['id_rol' => $id_rol]  ];
        
                        $resultadoVerificacion1 = $objusuario->procesarUsuario(json_encode($datosUsuario));
                        if ($resultadoVerificacion1['respuesta'] == 0) {
                             echo json_encode([ 'respuesta' => 0, 'accion' => 'actualizar', 'text' => '530 - ROL no existente' ]);
                              exit; 
                        } 
                            // ENVIO AL MODULO
                            $datosUsuario = [
                                'operacion' => 'actualizar',
                                    'datos' => [
                                        'id_persona' => $id_persona,
                                        'cedula' => $cedula,
                                        'correo' => $correo,
                                        'id_rol' => $id_rol,
                                        'estatus' => $estatus,
                                        'cedula_actual' => $cedula_actual,
                                        'correo_actual' => $correo_actual,
                                        'rol_actual' => $rol_actual,
                                        'tipo_documento' => $tipo_documento,
                                        //'nivel' => $nivel_valido
                                    ]
                            ]; 
                    
                                if($datosUsuario['datos']['id_persona'] == 2) { 
                                    if($datosUsuario['datos']['id_rol'] != 4) {
                                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No puedes cambiar el Rol del usuario administrador']);
                                        exit;
                                    }
                                    if($datosUsuario['datos']['estatus'] != 1) {
                                        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No puedes cambiar el estatus del usuario administrador']);
                                        exit;
                                    }
                                }
                    
                                $resultado = $objusuario->procesarUsuario(json_encode($datosUsuario));
                            
                                    if ($resultado['respuesta'] == 1) {
                                        $bitacora = [
                                            'id_persona' => $_SESSION["id"],
                                            'accion' => 'Modificación de usuario',
                                            'descripcion' => 'Se modificó el usuario con ID: ' . $datosUsuario['datos']['id_persona'] . 
                                                        ' Cédula: ' . $datosUsuario['datos']['cedula'] . 
                                                        ' Correo: ' . $datosUsuario['datos']['correo']
                                        ];
                                        $bitacoraObj = new Bitacora();
                                        $bitacoraObj->registrarOperacion($bitacora['accion'], 'usuario', $bitacora);
                                    }
                    
                                echo json_encode($resultado); /// RESULTADO DE LA ACTUALIZACION
                                exit;
                            //FIN DEL ENVIO    

            } else{  /* V3 datos vacios */
                echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0300 - Datos Vacios']);
                exit; 
            }
        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0200 - No Tiene Permiso para realizar esta operacion']);
            exit;
        }      
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => '#0100 - Session no encontrada']);
        exit;
    } 
//---------
} else if(isset($_POST['eliminar'])){ //--------------------------------- ELIMINAR USUARIO
//----------    
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(16, 4)) { /* V2 */ 

            if (!empty($_POST['eliminar'] && !empty($_POST['id_usuario'])) ) { /* V3 VACIOS  */
                $cedula = $_POST['eliminar'];  $id_usuario = $_POST['id_usuario'];
                
                $campos = [
                    'Cedula' => $cedula,
                    'id_usuario' => $id_usuario
                ];
                /// Sanitización de Entradas
                    foreach ($campos as $nombre => $valor) {  /* V4 */ 
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "#0400 - Entrada inválida detectada en el campo: $nombre"]);
                            exit;
                        }
                    }
                
                     //// Validar Datos  V5
                    if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
                        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => "#0510 - Cedula inválida"]);
                        exit;
                    }

                    if (ctype_digit($cedula)) {
                
                        if ($cedula == $_SESSION['id']) { /* NO ELIMINARSE ASI MISMO | ELIMINAR  */
                            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'No puedes eliminarte a ti mismo']);
                            exit;
                        }

                        if($id_usuario == 2 || $id_usuario == 1){
                            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'Usuario restringido, no se puede elimimar']);
                            exit;
                        }
                        
                        //VALIDACION EXISTENTE
                        $datosUsuario = ['operacion' => 'verificar','datos' => ['cedula' => $cedula]  ];
        
                        $resultadoVerificacion = $objusuario->procesarUsuario(json_encode($datosUsuario));
                        if ($resultadoVerificacion['respuesta'] == 0) {
                             echo json_encode([ 'respuesta' => 0, 'accion' => 'eliminar', 'text' => '530 - Cedula no existente' ]);
                              exit; 
                        } 
                            // ENVIO AL METODO -----------
                                $datosUsuario = [
                                    'operacion' => 'eliminar',
                                    'datos' => [
                                        'cedula' => $cedula
                                    ] 
                                ];
        
                                $resultado = $objusuario->procesarUsuario(json_encode($datosUsuario));
        
                                if ($resultado['respuesta'] == 1) {
                                    $bitacora = [
                                        'id_persona' => $_SESSION["id"],
                                        'accion' => 'Eliminación de usuario',
                                        'descripcion' => 'Se eliminó el usuario con ID: ' . $cedula
                                    ];
                                    $bitacoraObj = new Bitacora();
                                    $bitacoraObj->registrarOperacion($bitacora['accion'], 'usuario', $bitacora);
                                }
        
                                echo json_encode($resultado); /* RESPUESTA | ELIMINAR  */
                                exit;
                            //FIN ENVIO ------------   
        
                    } else { /* CEDULA NO NUMERICA | ELIMINAR  */
                        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'La cédula no es válida. Debe contener solo números']);
                        exit; 
                    }

            } else{  /* DATOS VACIOS | ELIMINAR  */
                echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'Datos Vacios']);
                exit; 
            }

        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'No Tiene Permiso para realizar esta operacion']);
            exit;
        }      
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'Session no encontrada']);
        exit;
    } 
//-----------    
} else if(isset($_POST['cedula'])){ //-------------------------------------------- VERIFICAR CEDULA
//----------
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if (!empty($_POST['cedula']) ) {   /* V2 VACIOS  */

            $cedulaValidar = $_POST['cedula'];
            // Validar V3
            if (!preg_match('/^[0-9]{7,8}$/', $cedulaValidar)) {
                echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => "#0310 - Formato inválida"]);
                exit;
            }
            
            if (ctype_digit($cedulaValidar)) {
                $datosUsuario = [
                    'operacion' => 'verificar',
                        'datos' => [
                            'cedula' => $cedulaValidar
                        ] 
                ];

                $resultado = $objusuario->procesarUsuario(json_encode($datosUsuario));
                echo json_encode($resultado);
                exit;
            } else {
                echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => '#0320 - La cédula no es válida. Debe contener solo números']);
                exit; 
            }

        }else{ /* V2 DATOS VACIOS */
            echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => '#0200 - Datos Vacios']);
            exit; 
        }
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'verificar', 'text' => '#0100 - Session no encontrada']);
        exit;
    } 
//------------    
} else if(isset($_POST['correo'])){ //--------------------------- VERIFICAR CORREO
//------------
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if (!empty($_POST['correo']) ) {   /* V2 */

            $correo = strtolower($_POST['correo']);
                //validar | V3
                if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 200) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'verificarcorreo', 'text' => "#0310 - Correo inválido."]);
                    exit;
                }
        
                $datosUsuario = [
                    'operacion' => 'verificarCorreo',
                    'datos' => [
                        'correo' => $correo
                    ] 
                ];
                
                $resultado = $objusuario->procesarUsuario(json_encode($datosUsuario));
                echo json_encode($resultado);
                exit; 
          
        }else{ /*2  DATOS VACIOS */
            echo json_encode(['respuesta' => 0, 'accion' => 'verificarcorreo', 'text' => '#0200 - Datos Vacios']);
            exit; 
        }
    } else{ /* 1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'verificarcorreo', 'text' => '#0100 - Session no encontrada']);
        exit;
    }
//---------------    
} else  if(isset($_POST['rol'])){ //-------------------------------------- VERIFICAR ROL
//---------------
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if (!empty($_POST['rol']) ) {  /* V2 */ 

            $rolValidar = $_POST['rol'];

            if (!preg_match('/^[0-9]{1,3}$/', $rolValidar)) {
                echo json_encode(['respuesta' => 0, 'accion' => 'verifirol', 'text' => "#0310 - Rol inválida Formato"]);
                exit;
            }
                if (ctype_digit($rolValidar)) {
                        $datosUsuario = [
                            'operacion' => 'verificarrol',
                            'datos' => [
                                'id_rol' =>  $rolValidar
                            ] 
                        ];

                        $resultado = $objusuario->procesarUsuario(json_encode($datosUsuario));
                        echo json_encode($resultado);
                        exit; 
                } else {  /* FORMATO NO VALIDO | VERIFICAR ROL  */
                    echo json_encode(['respuesta' => 0, 'accion' => 'verifirol', 'text' => '#0320 - Formato inválido']);
                    exit; 
                }
        }else{ /* DATOS VACIOS | VERIFICAR ROL  */
            echo json_encode(['respuesta' => 0, 'accion' => 'verifirol', 'text' => '#0200 - Datos Vacios']);
            exit; 
        }
    } else{ /* 1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'verifirol', 'text' => '#0100 - Session no encontrada']);
        exit;
    } 
//------    
} else{ //----------------------- VISTA
//------- 
    if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(16, 1)) {
            $bitacora = [
                'id_persona' => $_SESSION["id"],
                'accion' => 'Acceso a Módulo',
                'descripcion' => 'módulo de Usuario'
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bitacora['accion'], 'Usuario', $bitacora);

        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'usuario';
        require_once 'vista/usuario.php';
    } else{
        require_once 'vista/seguridad/privilegio.php';
    }
//--------             
} 
?>