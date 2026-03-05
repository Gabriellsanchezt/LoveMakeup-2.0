<?php  

use LoveMakeup\Proyecto\Modelo\Bitacora;

// Manejo de solicitudes AJAX - debe ejecutarse antes de cualquier salida
if (isset($_POST['detalles']) || isset($_POST['limpiar']) || isset($_POST['eliminar_registro']) || isset($_POST['cargar_mas'])) {
    // Iniciar sesión solo si no está ya iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validar sesión para operaciones AJAX
    if (empty($_SESSION["id"])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Sesión no válida']);
        exit;
    }
    
    // Incluir dependencias necesarias
    require_once 'verificarsession.php';
    require_once 'permiso.php';
    
    // Validar que no sea cliente
    if (isset($_SESSION["nivel_rol"]) && $_SESSION["nivel_rol"] == 1) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }
    
    // Desactivar la salida de errores para respuestas AJAX limpias
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Configurar headers para respuesta JSON limpia
    header('Content-Type: application/json; charset=utf-8');
    
    $objBitacora = new Bitacora();
    
    // Obtener detalles de un registro específico
    if(isset($_POST['detalles'])) {
        try {
            $id_bitacora = (int)$_POST['detalles'];
            if ($id_bitacora <= 0) {
                echo json_encode(['error' => 'ID de bitácora inválido']);
                exit;
            }
            
            $registro = $objBitacora->obtenerRegistro($id_bitacora);
            
            // Verificar si hay error en la respuesta
            if (is_array($registro) && isset($registro['error'])) {
                echo json_encode($registro);
                exit;
            }
            
            if ($registro === false || empty($registro)) {
                echo json_encode(['error' => 'Registro no encontrado']);
                exit;
            }
            
            echo json_encode($registro);
        } catch (\Exception $e) {
            echo json_encode(['error' => 'Error al obtener el registro: ' . $e->getMessage()]);
        }
        exit;
    }

    // Limpiar bitácora (eliminar todos los registros)
    if(isset($_POST['limpiar'])) {
        try {
            // Verificar permisos para limpiar
            if (!tieneAcceso(15, 4)) {
                echo json_encode(['success' => false, 'message' => 'No tiene permisos para esta acción']);
                exit;
            }
            
            $resultado = $objBitacora->limpiarBitacora();
            echo json_encode($resultado);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Cargar más registros (paginación)
    if(isset($_POST['cargar_mas'])) {
        try {
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            $limite = isset($_POST['limite']) ? (int)$_POST['limite'] : 100;
            
            $registros = $objBitacora->consultar($limite, $offset);
            $total = $objBitacora->contarTotal();
            
            echo json_encode([
                'success' => true,
                'registros' => $registros,
                'total' => $total,
                'offset' => $offset,
                'limite' => $limite,
                'tiene_mas' => ($offset + $limite) < $total
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Eliminar registro específico
    if(isset($_POST['eliminar_registro'])) {
        try {
            // Verificar permisos para eliminar
            if (!tieneAcceso(15, 4)) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'No tiene permisos para esta acción']);
                exit;
            }
            
            $id_bitacora = (int)$_POST['id_bitacora'];
            if ($id_bitacora <= 0) {
                echo json_encode(['respuesta' => 0, 'mensaje' => 'ID de bitácora inválido']);
                exit;
            }
            
            $objBitacora->set_Idbitacora($id_bitacora);
            $resultado = $objBitacora->eliminar();
            echo json_encode($resultado);
        } catch (\Exception $e) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar sesión activa
if (empty($_SESSION["id"])){
    header("location:?pagina=login");
    exit();
}

// Verificar expiración de sesión
if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
}

// Validar que no sea cliente
if (isset($_SESSION["nivel_rol"]) && $_SESSION["nivel_rol"] == 1) {
    header("Location: ?pagina=catalogo");
    exit();
}

if (!isset($_SESSION['limite_bitacora'])) {
    $_SESSION['limite_bitacora'] = 100;
}
//--------
if (isset($_POST['ver_mas'])) {
    $_SESSION['limite_bitacora'] += 100;
    header("location:?pagina=bitacora");
    exit;
}
// Cargar sistema de permisos
require_once 'permiso.php';

// Instanciar objeto Bitácora (independiente, solo para uso del módulo)
$objBitacora = new Bitacora();
 $registro = $objBitacora->consultar($_SESSION['limite_bitacora']);
 $total_registros = $objBitacora->contarTotal(); 
  
// Función global para registrar en bitácora desde cualquier módulo
// Esta función es opcional y otros módulos pueden llamarla si desean
// El módulo de bitácora no depende de que otros módulos la usen
function registrarEnBitacora($accion, $modulo, $detalle = '') {
    global $objBitacora;
    if (!isset($objBitacora)) {
        // Si no existe, crear una instancia temporal solo para registrar
        try {
            require_once __DIR__ . '/../modelo/bitacora.php';
            $bitacoraTemp = new \LoveMakeup\Proyecto\Modelo\Bitacora();
            return $bitacoraTemp->registrarOperacion($accion, $modulo, $detalle);
        } catch (\Exception $e) {
            error_log("Error al crear instancia de bitácora: " . $e->getMessage());
            return false;
        }
    }
    return $objBitacora->registrarOperacion($accion, $modulo, $detalle);
}

// Verificar permisos y mostrar vista
// Módulo 15 = Bitácora según la base de datos
if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(15, 1)) {
    // Registrar acceso al módulo de bitácora (solo en carga normal, no en AJAX)
    // Esto es independiente de otros módulos
    if (!isset($_POST['detalles']) && !isset($_POST['limpiar']) && !isset($_POST['eliminar_registro']) && !isset($_POST['cargar_mas'])) {
        try {
            $objBitacora->registrarOperacion('ACCESO A MÓDULO', 'Bitácora', 'Usuario accedió al módulo de Bitácora');
        } catch (\Exception $e) {
            // Si falla el registro, no afectar la visualización
            error_log("Error al registrar acceso a bitácora: " . $e->getMessage());
        }
    }
    $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'bitacora';
    require_once 'vista/seguridad/bitacora.php';
} else {
    require_once 'vista/seguridad/privilegio.php';
}
?>