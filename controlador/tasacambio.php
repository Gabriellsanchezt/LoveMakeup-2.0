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
    // Validar y sanitizar datos
    $fecha = isset($_POST['fecha']) ? filter_var($_POST['fecha'], FILTER_SANITIZE_STRING) : '';
    $tasa = isset($_POST['tasa']) ? filter_var($_POST['tasa'], FILTER_VALIDATE_FLOAT) : false;
    $fuente = isset($_POST['fuente']) ? filter_var($_POST['fuente'], FILTER_SANITIZE_STRING) : 'Manualmente';
    
    // Validaciones
    if (empty($fecha)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => 'La fecha es requerida']);
        exit;
    }
    
    if ($tasa === false || $tasa <= 0) {
        echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => 'La tasa debe ser un número válido mayor a 0']);
        exit;
    }
    
    // Validar formato de fecha
    $fechaValidada = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fecha) {
        echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => 'Formato de fecha inválido']);
        exit;
    }
    
    $datosTasa = [
        'operacion' => 'modificar',
        'datos' => [
            'fecha' => $fecha,
            'tasa' => $tasa,
            'fuente' => $fuente
        ]
    ]; 

    $resultado = $objtasa->procesarTasa(json_encode($datosTasa));
    echo json_encode($resultado);

} else if(isset($_POST['sincronizar'])){
    // Validar y sanitizar datos
    $fecha = isset($_POST['fecha']) ? filter_var($_POST['fecha'], FILTER_SANITIZE_STRING) : '';
    $tasa = isset($_POST['tasa']) ? filter_var($_POST['tasa'], FILTER_VALIDATE_FLOAT) : false;
    $fuente = isset($_POST['fuente']) ? filter_var($_POST['fuente'], FILTER_SANITIZE_STRING) : 'Via Internet';
    
    // Validaciones
    if(empty($tasa) || $tasa === false){
        echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'Tasa no encontrada o inválida']);
        exit;
    } 
    
    if($tasa <= 0){
        echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'Error: La tasa debe ser mayor a 0']);
        exit;
    }
    
    if (empty($fecha)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'La fecha es requerida']);
        exit;
    }
    
    // Validar formato de fecha
    $fechaValidada = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fecha) {
        echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'Formato de fecha inválido']);
        exit;
    }
    
    $datosTasa = [
        'operacion' => 'sincronizar',
        'datos' => [
            'fecha' => $fecha,
            'tasa' => $tasa,
            'fuente' => $fuente
        ]
    ]; 

    $resultado = $objtasa->procesarTasa(json_encode($datosTasa));
    echo json_encode($resultado);
    
} else if(isset($_POST['obtener_tasa_actual']) || (isset($_GET['obtener_tasa_actual']) && $_GET['obtener_tasa_actual'] == '1')) {
    // Endpoint para obtener la tasa actual desde la base de datos
    header('Content-Type: application/json; charset=utf-8');
    try {
        $tasa = $objtasa->obtenerTasaActual();
        if ($tasa && isset($tasa['tasa_bs'])) {
            echo json_encode([
                'respuesta' => 1,
                'tasa' => floatval($tasa['tasa_bs']),
                'fecha' => $tasa['fecha'],
                'fuente' => $tasa['fuente'] ?? 'Base de datos'
            ]);
        } else {
            echo json_encode([
                'respuesta' => 0,
                'mensaje' => 'No se encontró una tasa de cambio en la base de datos'
            ]);
        }
    } catch (\Exception $e) {
        echo json_encode([
            'respuesta' => 0,
            'mensaje' => 'Error al obtener la tasa de cambio: ' . $e->getMessage()
        ]);
    }
    exit;
    
} else if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(14, 'ver')) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'tasacambio';
    require_once 'vista/tasacambio.php'; // Asegúrate de tener esta vista
} else {
    require_once 'vista/seguridad/privilegio.php';
}

