<?php  
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





// Verificación de privilegios
if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(11, 'ver')) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'delivery';
    require_once 'vista/delivery.php'; // Asegúrate de tener esta vista
} else {
    require_once 'vista/seguridad/privilegio.php';
}

