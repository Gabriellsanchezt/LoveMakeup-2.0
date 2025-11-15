<?php  

use LoveMakeup\Proyecto\Modelo\catalogo_datos;

session_start();
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

if (isset($_POST['actualizar'])) {
     $datosCliente = [
        'operacion' => 'actualizar',
        'datos' => [
            'id_persona' => $_SESSION["id"],
            'nombre' => ucfirst(strtolower($_POST['nombre'])),
            'apellido' => ucfirst(strtolower($_POST['apellido'])),
            'cedula' => $_POST['cedula'],
            'correo' => strtolower($_POST['correo']),
            'telefono' => $_POST['telefono'],
            'tipo_documento' => $_POST['tipo_documento'],
            'cedula_actual' => $_SESSION["id"],
            'correo_actual' => $_SESSION["correo"]
        ]
    ];

    $nombre_actual = $_SESSION["nombre"];
    $apellido_actual = $_SESSION["apellido"];
    $telefono_actual = $_SESSION["telefono"];
  $documento_actual = $_SESSION["documento"];
   

    $datos = $datosCliente['datos'];

    $hayCambios = (
        $nombre_actual !== $datos['nombre'] ||
        $apellido_actual !== $datos['apellido'] ||
        $telefono_actual !== $datos['telefono'] ||
        $datos['cedula_actual'] !== $datos['cedula'] ||
           $documento_actual !== $datos['tipo_documento'] ||
        strtolower($datos['correo_actual']) !== strtolower($datos['correo']) // Comparación case-insensitive
    );

    if (!$hayCambios) {
        $res = [
            'respuesta' => 0,
            'accion' => 'actualizar',
            'text' => 'No se realizaron cambios en los datos.'
        ];
        echo json_encode($res);
        exit;
    }

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
      
} else if (isset($_POST['actualizardireccion'])) {
    
    $datosCliente = [
        'operacion' => 'actualizardireccion',
        'datos' => [
            'direccion_envio' => $_POST['direccion_envio'],
            'sucursal_envio' => $_POST['sucursal_envio'],
            'id_direccion' => $_POST['id_direccion'],
            'id_metodoentrega' => $_POST['id_metodoentrega']
        ]
    ];

   $resultado = $objdatos->procesarCliente(json_encode($datosCliente));
   echo json_encode($resultado);

   
      
} else if (isset($_POST['incluir'])) {

    $sucursal = !empty($_POST['sucursal_envio']) ? $_POST['sucursal_envio'] : "no aplica";

    $datosCliente = [
        'operacion' => 'incluir',
        'datos' => [
            'id_metodoentrega' => $_POST['id_metodoentrega'],
            'cedula' => $_SESSION["id"],
            'direccion_envio' => $_POST['direccion_envio'],
            'sucursal_envio' => $sucursal
        ]
    ];

   $resultado = $objdatos->procesarCliente(json_encode($datosCliente));
   echo json_encode($resultado);
   
} else if(isset($_POST['eliminar'])){
    
    $datosCliente = [
        'operacion' => 'eliminar',
        'datos' => [
            'id_usuario' => $_SESSION['id_usuario'],
             'cedula' => $_POST['persona']
        ]
    ];

      $resultado = $objdatos->procesarCliente(json_encode($datosCliente));
      echo json_encode($resultado);

      session_destroy();
    

} else if(isset($_POST['actualizarclave'])){
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
