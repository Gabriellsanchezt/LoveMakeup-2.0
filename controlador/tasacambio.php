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
if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(14, 'ver')) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'tasacambio';
    require_once 'vista/tasacambio.php'; // Asegúrate de tener esta vista
} else {
    require_once 'vista/seguridad/privilegio.php';
}

