<?php

use LoveMakeup\Proyecto\Modelo\Categoria;
use LoveMakeup\Proyecto\Modelo\Bitacora;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['id'])) {
    header('Location:?pagina=login');
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
$Cat = new Categoria();

// Fijamos el rol en "Administrador"
$rolText = 'Administrador';

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN |||||||||||||||||||||||||||||*/

/**
 * Detecta intentos de inyección SQL en un string
 * Para nombres propios, solo verificamos símbolos peligrosos
 */
function validarEntradaSQL($input, $tipoCampo = 'nombre') {
    // Para nombres propios, solo verificamos símbolos peligrosos
    if ($tipoCampo === 'nombre' || $tipoCampo === 'direccion') {
        $simbolosPeligrosos = ['--', ';', '#', '/*', '*/', '@@', "'", '"', '\\'];
        foreach ($simbolosPeligrosos as $simbolo) {
            if (strpos($input, $simbolo) !== false) {
                return false;
            }
        }
        return true;
    }
    
    // Para campos técnicos, usamos lista negra completa
    $blacklist = [
        'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER',
        'CREATE', 'EXEC', 'EXECUTE', 'UNION', 'WHERE', 'FROM', 'INTO', 'VALUES',
        'OR ', 'AND ', 'NOT ', 'NULL', 'LIKE', 'BETWEEN', 'HAVING', 'GROUP BY',
        'ORDER BY', 'JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN', 'OUTER JOIN',
        'DATABASE', 'TABLE', 'VIEW', 'INDEX', 'PROCEDURE', 'FUNCTION', 'TRIGGER',
        'CURSOR', 'FETCH', 'OPEN', 'CLOSE', 'DEALLOCATE', 'SET', 'RETURN',
        'RAISE', 'ERROR', 'EXCEPTION', 'TRY', 'CATCH', 'THROW', 'WHILE', 'LOOP',
        'BEGIN', 'END', 'COMMIT', 'ROLLBACK', 'TRANSACTION', 'GRANT', 'REVOKE',
        'DENY', 'WAITFOR', 'DELAY', 'BENCHMARK', 'SLEEP', 'LOAD_FILE', 'INTO OUTFILE',
        'INTO DUMPFILE', 'INFORMATION_SCHEMA', 'SYS.', 'SYSTEM_USER', 'CURRENT_USER',
        'SESSION_USER', 'DBMS_', 'UTL_', 'JAVA', 'XML', 'HTTP', 'FTP', 'SMTP',
        'XP_', 'SP_', 'XPCMD', 'CMDEXEC', 'OLE', 'AUTOMATION', 'OBJECT', 'ACTIVE',
        'SCRIPT', 'SHELL', 'COMMAND', 'EXECUTE IMMEDIATE', 'DYNAMIC', 'SQL', 'INJECT',
        '--', '/*', '*/', '#', ';', ':', '@', '=', '!', '+', '|', '&', '^', '~', '<', '>', '/', '%'
    ];
    
    $input_upper = strtoupper($input);
    foreach ($blacklist as $palabra) {
        if (strpos($input_upper, $palabra) !== false) {
            return false;
        }
    }
    return true;
}

/**
 * Valida que el id_categoria sea válido y exista en la base de datos
 */
function validarIdCategoria($id_categoria) {
    if (empty($id_categoria) || !is_numeric($id_categoria)) {
        return false;
    }
    $id_categoria = (int)$id_categoria;
    
    // Usar la conexión global para verificar existencia
    global $Cat;
    $db = $Cat->getConex1();
    $stmt = $db->prepare("SELECT COUNT(*) FROM categoria WHERE id_categoria = :id");
    $stmt->execute(['id' => $id_categoria]);
    $existe = $stmt->fetchColumn();
    
    return $existe > 0;
}

// 0) GET → acceso + bitácora
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && !isset($_POST['consultar_categoria'])
) {
    $Cat->registrarBitacora(json_encode([
        'id_persona'  => $_SESSION['id'],
        'accion'      => 'Acceso a Categorías',
        'descripcion' => "$rolText accedió al módulo Categoría"
    ]));
}

// 1) Registrar categoría
if (isset($_POST['registrar'])) {
    // ========================================
    // CAPA 1: Sesión activa (ya validada arriba)
    // ========================================
    
    // ========================================
    // CAPA 2: Validación explícita de permisos
    // ========================================
    if (!tieneAcceso(8, 2)) {  // 8 = módulo categoria, 2 = registrar
        echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'No tiene permisos para realizar esta acción']);
        exit;
    }
    
    // ========================================
    // CAPA 4: Validación de campos vacíos
    // ========================================
    $nombre_raw = trim($_POST['nombre']);
    
    if (empty($nombre_raw)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'El nombre de la categoría es obligatorio']);
        exit;
    }
    
    // ========================================
    // CAPA 5: Sanitización y Expresiones Regulares
    // ========================================
    
    // Sanitización contra SQL Injection
    if (!validarEntradaSQL($nombre_raw, 'nombre')) {
        echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Entrada inválida detectada en el campo: Nombre']);
        exit;
    }
    
    // Validación con expresiones regulares
    $nombre = ucfirst(strtolower($nombre_raw));
    
    // Validar nombre (letras y espacios, 3-50 caracteres)
    if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,50}$/', $nombre)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Nombre inválido. Solo letras y espacios, 3-50 caracteres']);
        exit;
    }

    $datos = ['nombre' => $nombre];
    $res   = $Cat->procesarCategoria(
        json_encode(['operacion'=>'incluir','datos'=>$datos])
    );
    if ($res['respuesta']==1) {
        $Cat->registrarBitacora(json_encode([
            'id_persona'=>$_SESSION['id'],
            'accion'    =>'Incluir Categoría',
            'descripcion'=>"Registró categoría \"{$datos['nombre']}\""
        ]));
    }
    echo json_encode($res);
    exit;
}

// 2) Modificar categoría
if (isset($_POST['modificar'])) {
    // ========================================
    // CAPA 1: Sesión activa (ya validada arriba)
    // ========================================
    
    $id_categoria = (int) $_POST['id_categoria'];
    
    // ========================================
    // CAPA 2: Validación explícita de permisos
    // ========================================
    if (!tieneAcceso(8, 3)) {  // 8 = módulo categoria, 3 = modificar
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
        exit;
    }
    
    // ========================================
    // CAPA 3: Validación de clave foránea (ID de categoría)
    // ========================================
    // Validar id_categoria primero (debe existir en la base de datos)
    if (!validarIdCategoria($id_categoria)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'La categoría seleccionada no es válida']);
        exit;
    }
    
    // ========================================
    // CAPA 4: Validación de campos vacíos
    // ========================================
    $nombre_raw = trim($_POST['nombre']);
    
    if (empty($nombre_raw)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El nombre de la categoría es obligatorio']);
        exit;
    }
    
    // ========================================
    // CAPA 5: Sanitización y Expresiones Regulares
    // ========================================
    
    // Sanitización contra SQL Injection
    if (!validarEntradaSQL($nombre_raw, 'nombre')) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Entrada inválida detectada en el campo: Nombre']);
        exit;
    }
    
    // Validación con expresiones regulares
    $nombre = ucfirst(strtolower($nombre_raw));
    
    // Validar nombre (letras y espacios, 3-50 caracteres)
    if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,50}$/', $nombre)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Nombre inválido. Solo letras y espacios, 3-50 caracteres']);
        exit;
    }

    $datos = [
        'id_categoria' => $id_categoria,
        'nombre'       => $nombre
    ];
    $res = $Cat->procesarCategoria(
        json_encode(['operacion'=>'actualizar','datos'=>$datos])
    );
    if ($res['respuesta']==1) {
        $Cat->registrarBitacora(json_encode([
            'id_persona'=>$_SESSION['id'],
            'accion'    =>'Actualizar Categoría',
            'descripcion'=>"Actualizó categoría ID {$datos['id_categoria']} → \"{$datos['nombre']}\""
        ]));
    }
    echo json_encode($res);
    exit;
}

// 3) Eliminar categoría
if (isset($_POST['eliminar'])) {
    // ========================================
    // CAPA 1: Sesión activa (ya validada arriba)
    // ========================================
    
    $id = (int) $_POST['id_categoria'];
    
    // ========================================
    // CAPA 2: Validación explícita de permisos
    // ========================================
    if (!tieneAcceso(8, 4)) {  // 8 = módulo categoria, 4 = eliminar
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
        exit;
    }
    
    // ========================================
    // CAPA 3: Validación de clave foránea (ID de categoría)
    // ========================================
    // Validar id_categoria (debe existir en la base de datos)
    if (!validarIdCategoria($id)) {
        echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'La categoría seleccionada no es válida']);
        exit;
    }
    
    // ========================================
    // CAPA 4: Campos vacíos (YA VALIDADO EN CAPA 3 - ID es requerido)
    // ========================================
    
    // ========================================
    // CAPA 5: Sanitización (ID ya validado como entero)
    // ========================================

    // obtener nombre antes de eliminar
    try {
        $db = $Cat->getConex1();
        $stmt = $db->prepare("SELECT nombre FROM categoria WHERE id_categoria=:id");
        $stmt->execute(['id'=>$id]);
        $nombre = $stmt->fetchColumn() ?: "ID $id";
        $db = null;
    } catch (\PDOException $e) {
        $nombre = "ID $id";
    }

    $res = $Cat->procesarCategoria(
        json_encode(['operacion'=>'eliminar','datos'=>['id_categoria'=>$id]])
    );
    if ($res['respuesta']==1) {
        $Cat->registrarBitacora(json_encode([
            'id_persona'=>$_SESSION['id'],
            'accion'    =>'Eliminar Categoría',
            'descripcion'=>"Eliminó categoría \"{$nombre}\""
        ]));
    }
    echo json_encode($res);
    exit;
}

// Consulta para mostrar la vista
if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(8, 1)) {
    $bitacora = [
        'id_persona' => $_SESSION["id"],
        'accion' => 'Acceso a Módulo',
        'descripcion' => 'módulo de Categoria'
    ];
    $bitacoraObj = new Bitacora();
    $bitacoraObj->registrarOperacion($bitacora['accion'], 'categoria', $bitacora);
    
    // Consultar todas las categorías (activas e inactivas para la validación)
    $categorias = $Cat->consultar();
    $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'categoria';
    require_once 'vista/categoria.php';
} else {
    require_once 'vista/seguridad/privilegio.php';
}