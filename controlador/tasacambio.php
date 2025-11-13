<?php
use LoveMakeup\Proyecto\Modelo\Tasacambio;   
session_start();

if (empty($_SESSION["id"])) {
    header("location:?pagina=login");
    exit;
}
if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
} 

if ($_SESSION["nivel_rol"] == 1) {
        header("Location: ?pagina=catalogo");
        exit();
    }/*  Validacion cliente  */

require_once 'permiso.php';

$objtasa = new Tasacambio();


$registro = $objtasa->consultar();

if(isset($_POST['modificar'])){
     $datosTasa = [
        'operacion' => 'modificar',
        'datos' => [
            'fecha' => $_POST['fecha'],
            'tasa' => $_POST['tasa'],
            'fuente' => $_POST['fuente']
        ]
    ]; 

    $resultado = $objtasa->procesarTasa(json_encode($datosTasa));
    echo json_encode($resultado);

} else if(isset($_POST['sincronizar'])){
    if(empty($_POST['tasa'])){
        echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => ' tasa no encontrada']);
        exit;
    } 
    $cambio = $_POST['tasa'];
    if($cambio === '0.00'){
     echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'Error Conexion o tasa no encontrada']);
     exit;
    }
        $datosTasa = [
            'operacion' => 'sincronizar',
            'datos' => [
                'fecha' => $_POST['fecha'],
                'tasa' => $_POST['tasa'],
                'fuente' => $_POST['fuente']
            ]
        ]; 

        $resultado = $objtasa->procesarTasa(json_encode($datosTasa));
        echo json_encode($resultado);
    
} else if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(14, 'ver')) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'tasacambio';
    require_once 'vista/tasacambio.php'; // Asegúrate de tener esta vista
} else {
    require_once 'vista/seguridad/privilegio.php';
}

