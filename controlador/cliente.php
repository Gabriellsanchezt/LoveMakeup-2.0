<?php  

use LoveMakeup\Proyecto\Modelo\Cliente; 
use LoveMakeup\Proyecto\Modelo\Bitacora;

session_start();
if (empty($_SESSION["id"])){
  header("location:?pagina=login");
} /*  Validacion URL  */
if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
} 

    if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }/*  Validacion cliente  */

require_once 'permiso.php';
$objcliente = new Cliente();


$registro = $objcliente->consultar();
$pedidos = $objcliente->consultarPedidos();

  function cedulaModificable($cedula, $registro) {
        foreach ($registro as $usuario) {
            if (trim((string)$usuario['cedula']) === trim((string)$cedula)) {
                // Si está asociada a un usuario protegido
                if ($usuario['id_usuario'] == 1 || $usuario['id_usuario'] == 2) {
                    return false; // No se puede modificar
                }
                return true; // Existe y no es protegida
            }
        }
        return false; // No existe la cédula
    }

     /**
     * Valida que el tipo_documento sea válido
     */
    function validarTipoDocumento($tipo_documento) {
        $tipos_validos = ['V', 'E', 'J'];
        return in_array($tipo_documento, $tipos_validos, true);
    }

if(isset($_POST['actualizar'])){ /*|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||   MODIFICAR DATOS DEL CLIENTE     */
    if(!empty($_POST['cedula']) && !empty($_POST['correo']) && !empty($_POST['estatus']) && !empty($_POST['cedulaactual']) && !empty($_POST['tipo_documento']) && !empty($_POST['correoactual']) ){
       
        $Cedula=$_POST['cedula']; $Correo=strtolower($_POST['correo']); $Estatus=$_POST['estatus'];
        $CedulaActual=$_POST['cedulaactual']; $Documento=$_POST['tipo_documento']; $CorreoActual=$_POST['correoactual'];

        if (!validarTipoDocumento($Documento)) {  // Validar tipo_documento
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El tipo de documento no es válido']);
            exit;
        }

       if (!in_array($Estatus, [1, 2])) {
           echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El estatus no es válido']);
            exit;
        }

        if (cedulaModificable($CedulaActual, $registro)) { // Validar si la cedula actual si existe en la BD
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
    
                if ($resultado['respuesta'] == 1) {
                    $bitacora = [
                        'id_persona' => $_SESSION["id"],
                        'accion' => 'Modificación de cliente',
                        'descripcion' => 'Se modificó el cliente con ID: ' . $datosCliente['datos']['cedula_actual'] . 
                                    ' Cédula: ' . $datosCliente['datos']['cedula'] . 
                                    ' Correo: ' . $datosCliente['datos']['correo']
                    ];
                    $bitacoraObj = new Bitacora();
                    $bitacoraObj->registrarOperacion($bitacora['accion'], 'cliente', $bitacora);
                } // Bitacora

             echo json_encode($resultado);
        } else {  /// si la cedula actual no existia o esta protegida
             echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'Cedula no encontrada O protegida']);
             exit;
        }
    } else{ // datos vacios
         echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'DATOS VACIOS']);
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