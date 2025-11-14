<?php  

use LoveMakeup\Proyecto\Modelo\Usuario;
use LoveMakeup\Proyecto\Modelo\Bitacora;

    session_start();
     if (empty($_SESSION["id"])){
      header("location:?pagina=login");
    }  /* Validacion URL  */

    if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
    }
    if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    } // Validacion cliente  

   require_once 'permiso.php';

    $objusuario = new Usuario();
    
    $rol = $objusuario->obtenerRol();
    $roll = $objusuario->obtenerRol();
    $registro = $objusuario->consultar();

    /*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/
    
    /**
     * Valida que el id_rol sea válido y exista en la base de datos
     */
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

    /**
     * Valida que el nivel corresponda al id_rol seleccionado
     */
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

    /**
     * Valida que el tipo_documento sea válido
     */
    function validarTipoDocumento($tipo_documento) {
        $tipos_validos = ['V', 'E'];
        return in_array($tipo_documento, $tipos_validos, true);
    }

    /**
     * Valida que el estatus sea válido
     */
    function validarEstatus($estatus) {
        if (empty($estatus) || !is_numeric($estatus)) {
            return false;
        }
        $estatus = (int)$estatus;
        $estatus_validos = [1, 2, 3];
        return in_array($estatus, $estatus_validos, true);
    }

    /**
     * Obtiene el nivel correspondiente a un id_rol
     */
    function obtenerNivelPorRol($id_rol, $roles) {
        foreach ($roles as $rol) {
            if ($rol['id_rol'] == $id_rol) {
                return $rol['nivel'];
            }
        }
        return null;
    }

    /**
     * Valida que los permisos sean válidos y pertenezcan al usuario correcto
     */
    function validarPermisos($permisosId, $objusuario) {
        $acciones_validas = ['ver', 'registrar', 'editar', 'eliminar', 'especial'];
        $modulos_validos = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
        
        if (empty($permisosId) || !is_array($permisosId)) {
            return ['valido' => false, 'mensaje' => 'No se recibieron permisos válidos'];
        }
        
        // Obtener la cédula del primer permiso consultando la base de datos
        $primer_id_permiso = null;
        foreach ($permisosId as $modulo_id => $accionesModulo) {
            foreach ($accionesModulo as $accion => $id_permiso) {
                if (!empty($id_permiso) && is_numeric($id_permiso)) {
                    $primer_id_permiso = (int)$id_permiso;
                    break 2;
                }
            }
        }
        
        if ($primer_id_permiso === null) {
            return ['valido' => false, 'mensaje' => 'No se pudo obtener un ID de permiso válido'];
        }
        
        // Obtener la cédula desde el permiso
        $conex = $objusuario->getConex2();
        try {
            $sql = "SELECT cedula FROM permiso WHERE id_permiso = :id_permiso LIMIT 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_permiso' => $primer_id_permiso]);
            $cedula_usuario = $stmt->fetchColumn();
            $conex = null;
            
            if (!$cedula_usuario) {
                return ['valido' => false, 'mensaje' => 'El permiso no existe en la base de datos'];
            }
        } catch (\PDOException $e) {
            if ($conex) $conex = null;
            return ['valido' => false, 'mensaje' => 'Error al validar permisos'];
        }
        
        // Obtener los permisos reales del usuario desde la base de datos
        $permisos_reales = $objusuario->buscar($cedula_usuario);
        $permisos_validos = [];
        
        foreach ($permisos_reales as $permiso_real) {
            $permisos_validos[$permiso_real['id_modulo']][$permiso_real['accion']] = $permiso_real['id_permiso'];
        }
        
        // Validar cada permiso recibido
        foreach ($permisosId as $modulo_id => $accionesModulo) {
            // Validar que el módulo sea válido
            if (!is_numeric($modulo_id) || !in_array((int)$modulo_id, $modulos_validos, true)) {
                return ['valido' => false, 'mensaje' => 'El módulo ' . $modulo_id . ' no es válido'];
            }
            
            foreach ($accionesModulo as $accion => $id_permiso) {
                // Validar que la acción sea válida
                if (!in_array($accion, $acciones_validas, true)) {
                    return ['valido' => false, 'mensaje' => 'La acción ' . $accion . ' no es válida'];
                }
                
                // Validar que el id_permiso sea numérico
                if (!is_numeric($id_permiso) || (int)$id_permiso <= 0) {
                    return ['valido' => false, 'mensaje' => 'El ID de permiso no es válido'];
                }
                
                // Validar que el permiso pertenezca al usuario y al módulo/acción correctos
                $modulo_id_int = (int)$modulo_id;
                if (!isset($permisos_validos[$modulo_id_int][$accion]) || 
                    $permisos_validos[$modulo_id_int][$accion] != (int)$id_permiso) {
                    return ['valido' => false, 'mensaje' => 'El permiso no pertenece al usuario o no es válido'];
                }
            }
        }
        
        return ['valido' => true];
    }

  if (!isset($_SESSION['registro_limite'])) {
                    $_SESSION['registro_limite'] = 100;
                }

   // Si se presionó el botón para cargar más
   if (isset($_POST['cargar_mas'])) {
         $_SESSION['registro_limite'] += 100;
    }

if (isset($_POST['registrar'])) { /* -------  */
    if (!empty($_POST['nombre']) && !empty($_POST['apellido']) && !empty($_POST['cedula']) && !empty($_POST['telefono']) && !empty($_POST['correo']) && !empty($_POST['id_rol']) && !empty($_POST['clave'])) {

        // Validar id_rol
        if (!validarIdRol($_POST['id_rol'], $rol)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'El rol seleccionado no es válido']);
            exit;
        }

        // Validar tipo_documento
        if (!validarTipoDocumento($_POST['tipo_documento'])) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'El tipo de documento no es válido']);
            exit;
        }

        // Validar y corregir nivel según el id_rol (por seguridad, ignoramos el nivel enviado y usamos el del rol)
        $nivel_valido = obtenerNivelPorRol($_POST['id_rol'], $rol);
        if ($nivel_valido === null) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'No se pudo obtener el nivel del rol']);
            exit;
        }

        $datosUsuario = [
            'operacion' => 'registrar',
            'datos' => [
                'nombre' => ucfirst(strtolower($_POST['nombre'])),
                'apellido' => ucfirst(strtolower($_POST['apellido'])),
                'cedula' => $_POST['cedula'],
                'tipo_documento' => $_POST['tipo_documento'],
                'telefono' => $_POST['telefono'],
                'correo' => strtolower($_POST['correo']),
                'clave' => $_POST['clave'],
                'id_rol' => (int)$_POST['id_rol'],
                'nivel' => $nivel_valido
            ]
        ];

        $resultadoRegistro = $objusuario->procesarUsuario(json_encode($datosUsuario));

        

        echo json_encode($resultadoRegistro);
    }
} else  if(isset($_POST['modificar'])){ /* -------  */
     $id_usuario = $_POST['modificar'];    
        
     if ($id_usuario == $_SESSION['id']) {
        header("location:?pagina=usuario");
        exit;
    }
     if ($id_usuario == 10200300) {
        header("location:?pagina=usuario");
        exit;
    }
       
        $modificar = $objusuario->buscar($id_usuario);
        $nivel_usuario = $objusuario->obtenerNivelPorId($id_usuario);
        
        $nombre_usuario = trim($_POST['permisonombre']);
        $apellido_usuario = trim($_POST['permisoapellido']);
        
        require_once ("vista/seguridad/permiso.php");
       
    }else if(isset($_POST['actualizar'])){ /* -------  */
    
    // Validar id_rol
    if (!validarIdRol($_POST['id_rol'], $roll)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El rol seleccionado no es válido']);
        exit;
    }

    // Validar tipo_documento
    if (!validarTipoDocumento($_POST['tipo_documento'])) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El tipo de documento no es válido']);
        exit;
    }

    // Validar estatus
    if (!validarEstatus($_POST['estatus'])) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El estatus seleccionado no es válido']);
        exit;
    }

    // Validar y corregir nivel según el id_rol (por seguridad, ignoramos el nivel enviado y usamos el del rol)
    $nivel_valido = obtenerNivelPorRol($_POST['id_rol'], $roll);
    if ($nivel_valido === null) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No se pudo obtener el nivel del rol']);
        exit;
    }

    $datosUsuario = [
        'operacion' => 'actualizar',
        'datos' => [
            'id_persona' => $_POST['id_persona'],
            'cedula' => $_POST['cedula'],
            'correo' => $_POST['correo'],
            'id_rol' => (int)$_POST['id_rol'],
            'estatus' => (int)$_POST['estatus'],
            'cedula_actual' => $_POST['cedulaactual'],
            'correo_actual' => $_POST['correoactual'],
            'rol_actual' => $_POST['rol_actual'],
            'tipo_documento' => $_POST['tipo_documento'],
            'nivel' => $nivel_valido
        ]
    ]; 

    if($datosUsuario['datos']['id_persona'] == 2) { 
        if($datosUsuario['datos']['id_rol'] != 2) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No puedes cambiar el Rol del usuario administrador']);
            exit;
        }
        if($datosUsuario['datos']['estatus'] != 1) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No puedes cambiar el estatus del usuario administrador']);
            exit;
        }
    }

    $resultado = $objusuario->procesarUsuario(json_encode($datosUsuario));
/*
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
*/
    echo json_encode($resultado);

} else if (isset($_POST['actualizar_permisos'])) { /* -------  */
    $permisosRecibidos = $_POST['permiso'] ?? [];
    $permisosId = $_POST['permiso_id'] ?? [];

    // Validar que los permisos sean válidos y no manipulados
    $validacion = validarPermisos($permisosId, $objusuario);
    if (!$validacion['valido']) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar_permisos', 'text' => $validacion['mensaje']]);
        exit;
    }

    $acciones = ['ver', 'registrar', 'editar', 'eliminar', 'especial'];
    $listaPermisos = [];

    foreach ($permisosId as $modulo_id => $accionesModulo) {
        foreach ($accionesModulo as $accion => $id_permiso) {
            $estado = isset($permisosRecibidos[$modulo_id][$accion]) ? 1 : 0;

            $listaPermisos[] = [
                'id_permiso' => (int)$id_permiso,
                'id_modulo' => (int)$modulo_id,
                'accion' => $accion,
                'estado' => $estado
            ];
        }
    }

    $datosPermiso = [
        'operacion' => 'actualizar_permisos',
        'datos' => $listaPermisos
    ];
   
    $resultado = $objusuario->procesarUsuario(json_encode($datosPermiso));

    if ($resultado['respuesta'] == 1) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Modificar Permiso',
            'descripcion' => 'Se Modifico los permisos del usuario con ID: '
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'usuario', $bitacora);
    }

    echo json_encode($resultado);

} else if(isset($_POST['eliminar'])){ /* -------  */
    $datosUsuario = [
        'operacion' => 'eliminar',
        'datos' => [
            'cedula' => $_POST['eliminar']
        ] 
    ];

    if ($datosUsuario['datos']['cedula'] == 10200300) {
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'No se puede eliminar al usuario administrador']);
        exit;
    } 
    
    if ($datosUsuario['datos']['cedula'] == $_SESSION['id']) {
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'No puedes eliminarte a ti mismo']);
        exit;
    }

    $resultado = $objusuario->procesarUsuario(json_encode($datosUsuario));
/*
    if ($resultado['respuesta'] == 1) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Eliminación de usuario',
            'descripcion' => 'Se eliminó el usuario con ID: ' . $datosUsuario['datos']['id_persona']
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'usuario', $bitacora);
    }
*/
    echo json_encode($resultado);

} else {
      $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'usuario';
        require_once 'vista/usuario.php';  
        
        
} 
 

?>