<?php  
     // Iniciar sesión solo si no está ya iniciada
     if (session_status() === PHP_SESSION_NONE) {
         session_start();
     }
     if (empty($_SESSION["id"])){
       header("location:?pagina=login");
     } /*  Validacion URL  */
     if (!empty($_SESSION['id'])) {
        require_once 'verificarsession.php';
    } 
    require_once 'vista/error.php';

?>