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
}

$objdatos = new Catalogo_datos();

  $entrega = $objdatos->obtenerEntrega();

  $direccion = $objdatos->consultardireccion();

 function existeMetodoEntrega($id_metodo, $entregas) {
    foreach ($entregas as $entrega) {
        if (isset($entrega['id_entrega']) && (int)$entrega['id_entrega'] == (int)$id_metodo) {
            return true; // Existe
        }

    }
    return false; // No existe
}

//Valida que el tipo_documento sea válido
function validarTipoDocumento($tipo_documento) {
    $tipos_validos = ['V', 'E'];
    return in_array($tipo_documento, $tipos_validos, true);
}

if (isset($_POST['actualizar'])) {

    if(!empty($_POST['nombre']) &&!empty($_POST['apellido']) && !empty($_POST['cedula'])&&!empty($_POST['correo']) && !empty($_POST['telefono']) && !empty($_POST['tipo_documento'])){
        
        $nombre =  ucfirst(strtolower($_POST['nombre'])); $apellido = ucfirst(strtolower($_POST['apellido'])); $cedula = $_POST['cedula']; $correo = strtolower($_POST['correo']);  $telefono = $_POST['telefono']; $documento = $_POST['tipo_documento'];
    
        // Validar tipo_documento
        if (!validarTipoDocumento($documento)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El tipo de documento no es válido']);
            exit;
        }

        if (ctype_digit($cedula) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            
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
                    'cedula_actual' => $_SESSION["id"],
                    'correo_actual' => $_SESSION["correo"]
                ]
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
            } else {
                echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'La cédula o correo no es válido']);
                exit; 
            }
    } else{
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'La cédula o correo no es válido']);
        exit; 
    }    

} else if (isset($_POST['actualizardireccion'])) {
    
 if(!empty($_POST['direccion_envio']) && !empty($_POST['direccion_envio']) && !empty($_POST['id_direccion']) && !empty($_POST['id_metodoentrega'])){

        $id_metodo = $_POST['id_metodoentrega'];    $direccion = $_POST['direccion_envio']; 
        $sucursal = $_POST['sucursal_envio'];       $id_direccion = $_POST['id_direccion'];
           
        if (existeMetodoEntrega($id_metodo, $entrega)) {
                $datosCliente = [
                    'operacion' => 'actualizardireccion',
                    'datos' => [
                        'direccion_envio' => $direccion,
                        'sucursal_envio' => $sucursal,
                        'id_direccion' => $id_direccion,
                        'id_metodoentrega' => $id_metodo
                    ]
                ];

            $resultado = $objdatos->procesarCliente(json_encode($datosCliente));
            echo json_encode($resultado);
        } else {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizardireccion', 'text' => 'El método de entrega no existe.']);
            exit; 
        }

    }else{
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizardireccion', 'text' => 'datos vacios']);
        exit; 
    }  
      
} else if (isset($_POST['incluir'])) { // |||||||||||||||||||||||||||||||||||||||||||||||||||| Agregar dirreccion

    if(!empty($_POST['id_metodoentrega']) && !empty($_POST['direccion_envio'])){
        $id_metodo = $_POST['id_metodoentrega'];     $direccion = $_POST['direccion_envio']; 
        $sucursal = !empty($_POST['sucursal_envio']) ? $_POST['sucursal_envio'] : "no aplica";
           
        if (existeMetodoEntrega($id_metodo, $entrega)) {
            $datosCliente = [
                'operacion' => 'incluir',
                    'datos' => [
                        'id_metodoentrega' => $id_metodo,
                        'cedula' => $_SESSION["id"],
                        'direccion_envio' => $direccion,
                        'sucursal_envio' => $sucursal
                    ]
            ];

            $resultado = $objdatos->procesarCliente(json_encode($datosCliente));
            echo json_encode($resultado);
        } else {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'El método de entrega no existe.']);
            exit; 
        }

    }else{
        echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'text' => 'datos vacios']);
        exit; 
    }
   
   
} else if(isset($_POST['eliminar'])){ // ||||||||||||||||||||||||||||||||||||||||||||||||||||||| ELIMINAR CLIENTE 
    if(!empty($_POST['persona'])){
        $persona = $_POST['persona']; 
    
        if (ctype_digit($persona)) {
            if($persona === $_SESSION['id']){
                $datosCliente = [
                        'operacion' => 'eliminar',
                        'datos' => [
                            'id_usuario' => $_SESSION['id_usuario'],
                            'cedula' => $persona
                        ]
                    ];

                    $resultado = $objdatos->procesarCliente(json_encode($datosCliente));
                    echo json_encode($resultado);

                    session_destroy();
                    exit;
            } else{
                echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'La datos no encontrados']);
                exit; 
            }
        } else {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'el formato no es valido']);
            exit; 
        }

    } else{
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'datos vacios']);
        exit; 
    }
    
 
} else if(isset($_POST['actualizarclave'])){ //||||||||||||||||||||||||||||||||||||||||||||||||||||| ACTUALIZAR CLAVE

    if(!empty($_POST['clave'])&&!empty($_POST['clavenueva'])){ 

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
    }else{ // datos vacios
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'Datos Vacios']);
        exit;
    }
    
} if ($sesion_activa) {
     if($_SESSION["nivel_rol"] == 1) { 
      require_once('vista/tienda/catalogo_datos.php');
    } else{
        header('Location: ?pagina=catalogo');
    }   
} else {
   header('Location: ?pagina=catalogo');
}

?>
