<?php

use LoveMakeup\Proyecto\Modelo\Delivery;
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
$obj = new Delivery();

// Obtener lista de deliveries para validaciones
$deliveries = $obj->consultar();

// Fijamos el rol en "Administrador"
$rolText = 'Administrador';

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN DE SELECT |||||||||||||||||||||||||||||*/

/**
 * Valida que el tipo de vehículo sea válido
 */
function validarTipo($tipo) {
    if (empty($tipo)) {
        return false;
    }
    $tipos_validos = ['Carro', 'Moto', 'Bicicleta'];
    return in_array($tipo, $tipos_validos, true);
}

/**
 * Valida que el estatus sea válido
 */
function validarEstatus($estatus) {
    if (empty($estatus) || !is_numeric($estatus)) {
        return false;
    }
    $estatus = (int)$estatus;
    $estatus_validos = [1, 2];
    return in_array($estatus, $estatus_validos, true);
}

/**
 * Valida que el id_delivery sea válido y exista en la base de datos
 */
function validarIdDelivery($id_delivery, $deliveries) {
    if (empty($id_delivery) || !is_numeric($id_delivery)) {
        return false;
    }
    $id_delivery = (int)$id_delivery;
    foreach ($deliveries as $delivery) {
        if ($delivery['id_delivery'] == $id_delivery && $delivery['estatus'] != 0) {
            return true;
        }
    }
    return false;
}

/*||||||||||||||||||||||||||||||| FUNCIONES DE VALIDACIÓN Y SANITIZACIÓN |||||||||||||||||||||||||||||*/

/**
 * Detecta intentos de inyección SQL en un string
 * Para nombres y direcciones, solo verificamos símbolos peligrosos
 */
function validarEntradaSQL($input, $tipoCampo = 'nombre') {
    // Para nombres propios y direcciones, solo verificamos símbolos peligrosos
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

// 0) Registrar acceso al módulo (GET sin AJAX ni operaciones)
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && !isset($_POST['consultar_delivery'])
) {
    $obj->registrarBitacora(json_encode([
        'id_persona'  => $_SESSION['id'],
        'accion'      => 'Acceso a Delivery',
        'descripcion' => "$rolText accedió al módulo Delivery"
    ]));
}

// 1) AJAX JSON: Consultar, Registrar, Actualizar, Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['generar'])
) {
    header('Content-Type: application/json');

    // a) Consultar delivery para edición
    if (isset($_POST['consultar_delivery'])) {
        echo json_encode(
            $obj->consultarPorId((int)$_POST['id_delivery'])
        );
        exit;
    }

    // b) Registrar nuevo delivery
    if (isset($_POST['registrar'])) {
        // ========================================
        // CAPA 1: Sesión activa (ya validada arriba)
        // ========================================
        
        // ========================================
        // CAPA 2: Validación explícita de permisos
        // ========================================
        if (!tieneAcceso(11, 2)) {  // 11 = módulo delivery, 2 = registrar
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        
        // ========================================
        // CAPA 3: Claves foráneas (NO APLICA - no hay FKs)
        // ========================================
        
        // ========================================
        // CAPA 4: Validación de campos vacíos
        // ========================================
        // Aplicar trim() a todos los campos antes de validar
        $nombre_raw = trim($_POST['nombre']);
        $tipo_raw = trim($_POST['tipo']);
        $contacto_raw = trim($_POST['contacto']);
        $estatus_raw = trim($_POST['estatus']);
        
        if (empty($nombre_raw) || empty($tipo_raw) || 
            empty($contacto_raw) || empty($estatus_raw)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Todos los campos son obligatorios']);
            exit;
        }
        
        // ========================================
        // CAPA 5: Sanitización y Expresiones Regulares
        // ========================================
        
        // Sanitización contra SQL Injection (selectiva por tipo de campo)
        if (!validarEntradaSQL($nombre_raw, 'nombre')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Entrada inválida detectada en el campo: Nombre']);
            exit;
        }
        if (!validarEntradaSQL($contacto_raw, 'nombre')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Entrada inválida detectada en el campo: Contacto']);
            exit;
        }
        
        // Validación con expresiones regulares
        $nombre = ucfirst(strtolower($nombre_raw));
        $tipo = ucfirst(strtolower($tipo_raw));
        $contacto = $contacto_raw;
        $estatus = (int)($estatus_raw ?? 0);
        
        // Validar nombre (letras y espacios, 3-50 caracteres)
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,50}$/', $nombre)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Nombre inválido. Solo letras y espacios, 3-50 caracteres']);
            exit;
        }
        
        // Validar tipo (Carro, Moto, Bicicleta)
        if (!validarTipo($tipo)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'El tipo de vehículo seleccionado no es válido']);
            exit;
        }
        
        // Validar contacto (formato 0414-0000000: 4 dígitos, guion, 7 dígitos)
        if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $contacto)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Contacto inválido. El formato debe ser 0414-0000000']);
            exit;
        }
        
        // Validar estatus
        if (!validarEstatus($estatus)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'El estatus seleccionado no es válido']);
            exit;
        }

        $d = [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'contacto' => $contacto,
            'estatus' => $estatus
        ];
        $res = $obj->procesarDelivery(
            json_encode(['operacion'=>'registrar','datos'=>$d])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Incluir Delivery',
                'descripcion' => "$rolText registró delivery {$d['nombre']}"
            ]));
        }
        echo json_encode($res);
        exit;
    }

    // c) Actualizar delivery existente
    if (isset($_POST['actualizar'])) {
        // ========================================
        // CAPA 1: Sesión activa (ya validada arriba)
        // ========================================
        
        // ========================================
        // CAPA 2: Validación explícita de permisos
        // ========================================
        if (!tieneAcceso(11, 3)) {  // 11 = módulo delivery, 3 = actualizar
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        
        $id_delivery = (int)($_POST['id_delivery'] ?? 0);
        
        // ========================================
        // CAPA 3: Validación de clave foránea (ID de delivery)
        // ========================================
        // Validar id_delivery primero (debe existir en la base de datos)
        if (!validarIdDelivery($id_delivery, $deliveries)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El delivery seleccionado no es válido']);
            exit;
        }
        
        // ========================================
        // CAPA 4: Validación de campos vacíos
        // ========================================
        // Aplicar trim() a todos los campos antes de validar
        $nombre_raw = trim($_POST['nombre']);
        $tipo_raw = trim($_POST['tipo']);
        $contacto_raw = trim($_POST['contacto']);
        $estatus_raw = trim($_POST['estatus']);
        
        if (empty($nombre_raw) || empty($tipo_raw) || 
            empty($contacto_raw) || empty($estatus_raw)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Todos los campos son obligatorios']);
            exit;
        }
        
        // ========================================
        // CAPA 5: Sanitización y Expresiones Regulares
        // ========================================
        
        // Sanitización contra SQL Injection (selectiva por tipo de campo)
        if (!validarEntradaSQL($nombre_raw, 'nombre')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Entrada inválida detectada en el campo: Nombre']);
            exit;
        }
        if (!validarEntradaSQL($contacto_raw, 'nombre')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Entrada inválida detectada en el campo: Contacto']);
            exit;
        }
        
        // Validación con expresiones regulares
        $tipo = ucfirst(strtolower($tipo_raw));
        $estatus = (int)($estatus_raw ?? 0);
        
        // Validar nombre (letras y espacios, 3-50 caracteres)
        $nombre = ucfirst(strtolower($nombre_raw));
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,50}$/', $nombre)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Nombre inválido. Solo letras y espacios, 3-50 caracteres']);
            exit;
        }
        
        // Validar tipo (Carro, Moto, Bicicleta)
        if (!validarTipo($tipo)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El tipo de vehículo seleccionado no es válido']);
            exit;
        }
        
        // Validar contacto (formato 0414-0000000: 4 dígitos, guion, 7 dígitos)
        $contacto = $contacto_raw;
        if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $contacto)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Contacto inválido. El formato debe ser 0414-0000000']);
            exit;
        }
        
        // Validar estatus
        if (!validarEstatus($estatus)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El estatus seleccionado no es válido']);
            exit;
        }

        $d = [
            'id_delivery' => $id_delivery,
            'nombre' => $nombre,
            'tipo' => $tipo,
            'contacto' => $contacto,
            'estatus' => $estatus
        ];
        // Obtener nombre actual para bitácora
        $old = $obj->consultarPorId($id_delivery);
        $res = $obj->procesarDelivery(
            json_encode(['operacion'=>'actualizar','datos'=>$d])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Actualizar Delivery',
                'descripcion' => "$rolText actualizó delivery {$old['nombre']}"
            ]));
        }
        echo json_encode($res);
        exit;
    }

    // d) Eliminar (desactivar) delivery
    if (isset($_POST['eliminar'])) {
        // ========================================
        // CAPA 1: Sesión activa (ya validada arriba)
        // ========================================
        
        // ========================================
        // CAPA 2: Validación explícita de permisos
        // ========================================
        if (!tieneAcceso(11, 4)) {  // 11 = módulo delivery, 4 = eliminar
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        
        $id = (int)($_POST['id_delivery'] ?? 0);
        
        // ========================================
        // CAPA 3: Validación de clave foránea (ID de delivery)
        // ========================================
        // Validar id_delivery (debe existir en la base de datos)
        if (!validarIdDelivery($id, $deliveries)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'El delivery seleccionado no es válido']);
            exit;
        }
        
        // ========================================
        // CAPA 4: Campos vacíos (YA VALIDADO EN CAPA 3 - ID es requerido)
        // ========================================
        
        // ========================================
        // CAPA 5: Sanitización (ID ya validado como entero)
        // ========================================

        $delivery = $obj->consultarPorId($id);
        $nombre = $delivery['nombre'] ?? "ID $id";

        $res = $obj->procesarDelivery(
            json_encode(['operacion'=>'eliminar','datos'=>['id_delivery'=>$id]])
        );
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Eliminar Delivery',
                'descripcion' => "$rolText eliminó delivery $nombre"
            ]));
        }
        echo json_encode($res);
        exit;
    }
    
    // e) Cambiar estatus delivery
    if (isset($_POST['cambiarEstatus'])) {
        // ========================================
        // CAPA 1: Sesión activa (ya validada arriba)
        // ========================================
        
        // ========================================
        // CAPA 2: Validación explícita de permisos
        // ========================================
        if (!tieneAcceso(11, 5)) {  // 11 = módulo delivery, 5 = cambiar estatus
            echo json_encode(['respuesta' => 0, 'accion' => 'cambiarEstatus', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        
        $id = (int)($_POST['id_delivery'] ?? 0);
        $estatus = (int)($_POST['estatus'] ?? 0);
        
        // ========================================
        // CAPA 3: Validación de clave foránea (ID de delivery)
        // ========================================
        // Validar id_delivery (debe existir en la base de datos)
        if (!validarIdDelivery($id, $deliveries)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'cambiarEstatus', 'mensaje' => 'El delivery seleccionado no es válido']);
            exit;
        }
        
        // ========================================
        // CAPA 4: Campos vacíos (YA VALIDADO - ID y estatus son requeridos)
        // ========================================
        
        // ========================================
        // CAPA 5: Sanitización y Validación
        // ========================================
        // Validar estatus
        if (!validarEstatus($estatus)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'cambiarEstatus', 'mensaje' => 'El estatus seleccionado no es válido']);
            exit;
        }

        $delivery = $obj->consultarPorId($id);
        $nombre = $delivery['nombre'] ?? "ID $id";

        $res = $obj->procesarDelivery(
            json_encode(['operacion'=>'cambiarEstatus','datos'=>['id_delivery'=>$id, 'estatus'=>$estatus]])
        );
        if ($res['respuesta'] == 1) {
            $accionEstatus = $estatus == 1 ? 'activó' : 'inactivó';
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Cambiar Estatus Delivery',
                'descripcion' => "$rolText $accionEstatus delivery $nombre"
            ]));
        }
        echo json_encode($res);
        exit;
    }
} else  if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(11, 1)) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Delivery'
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'delivery', $bitacora);

       $registro = $obj->consultar();
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'delivery';
        require_once 'vista/delivery.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

}