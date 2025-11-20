<?php  

use LoveMakeup\Proyecto\Modelo\Catalogo;
use LoveMakeup\Proyecto\Modelo\ListaDeseo;

// Asegurar que el autoloader esté cargado
if (!class_exists('LoveMakeup\Proyecto\Modelo\Catalogo')) {
    // Fallback: cargar manualmente si el autoloader falla
    $catalogoFile = __DIR__ . '/../modelo/Catalogo.php';
    if (file_exists($catalogoFile)) {
        require_once $catalogoFile;
    } else {
        // Intentar con minúscula (por si acaso)
        $catalogoFile = __DIR__ . '/../modelo/catalogo.php';
        if (file_exists($catalogoFile)) {
            require_once $catalogoFile;
        } else {
            die('Error: No se pudo encontrar la clase Catalogo. Verifique que el archivo modelo/Catalogo.php existe.');
        }
    }
}

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

$catalogo = new Catalogo();

$categorias = $catalogo->obtenerCategorias();
$resultadoT = $catalogo->consultaTasaUltima();

 if (isset($_GET['categoria'])) {
    // Si no hay búsqueda, pero sí categoría
    $registro = $catalogo->obtenerPorCategoria($_GET['categoria']);
} else {
    // Si no hay ni búsqueda ni categoría, muestra todo
    $registro = $catalogo->obtenerProductosMasVendidos();
}



$idsProductosFavoritos = [];

if ($sesion_activa) {
    $objListaDeseo = new ListaDeseo();
    $lista = $objListaDeseo->obtenerListaDeseo($_SESSION['id']);
   $idsProductosFavoritos = array_column($lista, 'id_producto');
}


 if (isset($_POST['cerrar'])) {
    
    session_destroy(); // Se cierra la sesión
    header('Location: ?pagina=catalogo');
    exit;
} else {
    // Si todo es correcto, carga la vista de catálogo
     require_once('vista/tienda/'.$pagina.'.php');
    exit;
}   
   


// Aquí se puede cargar otras vistas si no es 'catalogo'

?>
