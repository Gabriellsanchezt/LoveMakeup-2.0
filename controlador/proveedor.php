<?php

use LoveMakeup\Proyecto\Modelo\Proveedor;
use LoveMakeup\Proyecto\Modelo\Bitacora;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CAPA 1: VALIDACIÓN DE SESIÓN ACTIVA

if (empty($_SESSION['id'])) {
    header('Location:?pagina=login');
    exit;
}

if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
}

// CAPA 2: VALIDACIÓN DE PERMISOS

require_once 'permiso.php';
$obj = new Proveedor();

// Fijar el rol para bitácora
$rolText = 'Administrador';

// FUNCIONES DE VALIDACIÓN DEL CONTROLADOR

/**
 * CAPA 5: Valida que el tipo de documento sea válido usando expresión regular
 */
function validarTipoDocumento($tipo_documento) {
    $tipos_validos = ['V', 'J', 'E', 'G'];
    return in_array($tipo_documento, $tipos_validos, true);
}

/**
 * CAPA 3: Valida que el ID del proveedor sea válido y exista en la base de datos
 * Esta es una validación de clave foránea/integridad referencial
 */
function validarIdProveedor($id_proveedor, $proveedores) {
    // Validación básica de tipo
    if (empty($id_proveedor) || !is_numeric($id_proveedor)) {
        return false;
    }
    $id_proveedor = (int)$id_proveedor;
    
    // Validación de existencia en BD (clave foránea lógica)
    foreach ($proveedores as $proveedor) {
        if ($proveedor['id_proveedor'] == $id_proveedor && $proveedor['estatus'] == 1) {
            return true;
        }
    }
    return false;
}

/**
 * CAPA 5: Función de sanitización contra SQL Injection
 * Lista negra de palabras y símbolos comunes en SQL Injection
 * NOTA: Se usa diferente validación según el tipo de campo
 */
function validarEntradaSQL($input, $tipoCampo = 'tecnico') {
    // Para nombres propios y correos, solo verificamos símbolos peligrosos
    if ($tipoCampo === 'nombre' || $tipoCampo === 'correo' || $tipoCampo === 'direccion') {
        $simbolosPeligrosos = ['--', ';', '#', '/*', '*/', '@@', "'", '"', '\\'];
        foreach ($simbolosPeligrosos as $simbolo) {
            if (strpos($input, $simbolo) !== false) {
                return false;
            }
        }
        return true;
    }
    
    // Para campos técnicos (documento, teléfono), usamos lista negra completa
    $blacklist = [
        'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER',
        'CREATE', 'RENAME', 'REPLACE', 'UNION', 'JOIN', 'WHERE', 'HAVING',
        'FROM', 'TABLE', 'DATABASE', 'SCHEMA', 'GRANT', 'REVOKE',
        '--', ';', '#', '/*', '*/', '@@', '@', 'CHAR', 'CAST', 'CONVERT',
        'EXEC', 'EXECUTE', 'xp_', 'sp_'
    ];
    
    $inputUpper = strtoupper($input);
    
    foreach ($blacklist as $prohibida) {
        if (strpos($inputUpper, $prohibida) !== false) {
            return false;
        }
    }
    return true;
}

// Fijamos el rol en "Administrador"
$rolText = 'Administrador';

// 0) Registrar acceso al módulo (GET sin AJAX ni operaciones)
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && !isset($_POST['consultar_proveedor'])
) {
    $obj->registrarBitacora(json_encode([
        'id_persona'  => $_SESSION['id'],
        'accion'      => 'Acceso a Proveedores',
        'descripcion' => "$rolText accedió al módulo Proveedores"
    ]));
}

// 1) AJAX JSON: Consultar, Registrar, Actualizar, Eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['generar'])
) {
    header('Content-Type: application/json');

    // a) Consultar proveedor para edición
    if (isset($_POST['consultar_proveedor'])) {
        // CAPA 1: Sesión ya validada arriba
        // CAPA 2: Permisos implícitos en el flujo GET
        
        // CAPA 3: Validar clave foránea (id_proveedor)
        $proveedores = $obj->consultar();
        if (!validarIdProveedor($_POST['id_proveedor'], $proveedores)) {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'El proveedor seleccionado no es válido']);
            exit;
        }
        
        echo json_encode(
            $obj->consultarPorId((int)$_POST['id_proveedor'])
        );
        exit;
    }

    // b) Registrar nuevo proveedor
    if (isset($_POST['registrar'])) {
        // CAPA 1: Sesión activa (ya validada arriba)
        
        // CAPA 2: Validación explícita de permisos
        if (!tieneAcceso(9, 2)) {  // 9 = módulo proveedor, 2 = registrar
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        
        // CAPA 3: Claves foráneas (no aplica en registro)
        
        // CAPA 4: Validación de campos vacíos
        if (empty($_POST['tipo_documento']) || empty($_POST['numero_documento']) || 
            empty($_POST['nombre']) || empty($_POST['correo']) || 
            empty($_POST['telefono']) || empty($_POST['direccion'])) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Todos los campos son obligatorios']);
            exit;
        }
        
        // CAPA 5: Sanitización y Expresiones Regulares
        
        // Sanitización contra SQL Injection (selectiva por tipo de campo)
        // Campos que pueden contener palabras comunes (nombres, correos, direcciones)
        // solo se validan con símbolos peligrosos, no con lista negra de palabras
        if (!validarEntradaSQL($_POST['tipo_documento'], 'tecnico')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => "Entrada inválida detectada en el campo: Tipo_documento"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['numero_documento'], 'tecnico')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => "Entrada inválida detectada en el campo: Numero_documento"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['nombre'], 'nombre')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => "Entrada inválida detectada en el campo: Nombre"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['correo'], 'correo')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => "Entrada inválida detectada en el campo: Correo"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['telefono'], 'tecnico')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => "Entrada inválida detectada en el campo: Telefono"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['direccion'], 'direccion')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => "Entrada inválida detectada en el campo: Direccion"]);
            exit;
        }
        
        // Validación con expresiones regulares (CAPA 5)
        $tipo_documento = strtoupper(trim($_POST['tipo_documento']));
        $numero_documento = trim($_POST['numero_documento']);
        $nombre = ucfirst(strtolower(trim($_POST['nombre'])));
        $correo = strtolower(trim($_POST['correo']));
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        
        // Validar tipo de documento (expresión regular)
        if (!preg_match('/^[VJEG]{1}$/', $tipo_documento)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Tipo de documento inválido']);
            exit;
        }
        
        // Validar número de documento (solo números, 7-9 dígitos)
        if (!preg_match('/^[0-9]{7,9}$/', $numero_documento)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Número de documento inválido. Debe contener 7-9 dígitos numéricos']);
            exit;
        }
        
        // Validar nombre (letras, espacios, máximo 30 caracteres)
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,30}$/', $nombre)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Nombre inválido. Solo letras y espacios, 3-30 caracteres']);
            exit;
        }
        
        // Validar correo electrónico
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 60) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Correo electrónico inválido']);
            exit;
        }
        
        // Validar teléfono (formato: 0414-0000000 o similar)
        if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $telefono)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Teléfono inválido. Formato esperado: 0414-0000000']);
            exit;
        }
        
        // Validar dirección (máximo 70 caracteres, permitir caracteres básicos)
        if (strlen($direccion) < 5 || strlen($direccion) > 70) {
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'Dirección inválida. Debe tener entre 5 y 70 caracteres']);
            exit;
        }
        
        // Preparar datos para el modelo
        $d = [
            'numero_documento' => $numero_documento,
            'tipo_documento'   => $tipo_documento,
            'nombre'           => $nombre,
            'correo'           => $correo,
            'telefono'         => $telefono,
            'direccion'        => $direccion
        ];
        
        // Ejecutar operación en el modelo
        $res = $obj->procesarProveedor(
            json_encode(['operacion'=>'registrar','datos'=>$d])
        );
        
        // Registrar en bitácora si fue exitoso
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Incluir Proveedor',
                'descripcion' => "$rolText registró proveedor {$d['nombre']}"
            ]));
        }
        
        echo json_encode($res);
        exit;
    }

    // c) Actualizar proveedor existente
    if (isset($_POST['actualizar'])) {
        // ========================================
        // CAPA 1: Sesión activa (ya validada arriba)
        // ========================================
        
        // ========================================
        // CAPA 2: Validación explícita de permisos
        // ========================================
        if (!tieneAcceso(9, 3)) {  // 9 = módulo proveedor, 3 = actualizar
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }

        // ========================================
        // CAPA 3: Validación de clave foránea (id_proveedor)
        // ========================================
        $proveedores = $obj->consultar();
        if (!validarIdProveedor($_POST['id_proveedor'], $proveedores)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'El proveedor seleccionado no es válido']);
            exit;
        }
        
        // CAPA 4: Validación de campos vacíos

        if (empty($_POST['id_proveedor']) || empty($_POST['tipo_documento']) || empty($_POST['numero_documento']) || 
            empty($_POST['nombre']) || empty($_POST['correo']) || 
            empty($_POST['telefono']) || empty($_POST['direccion'])) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Todos los campos son obligatorios']);
            exit;
        }
        
        // CAPA 5: Sanitización y Expresiones Regulares
        
        // Sanitización contra SQL Injection (selectiva por tipo de campo)
        if (!validarEntradaSQL($_POST['id_proveedor'], 'tecnico')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => "Entrada inválida detectada en el campo: Id_proveedor"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['tipo_documento'], 'tecnico')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => "Entrada inválida detectada en el campo: Tipo_documento"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['numero_documento'], 'tecnico')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => "Entrada inválida detectada en el campo: Numero_documento"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['nombre'], 'nombre')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => "Entrada inválida detectada en el campo: Nombre"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['correo'], 'correo')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => "Entrada inválida detectada en el campo: Correo"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['telefono'], 'tecnico')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => "Entrada inválida detectada en el campo: Telefono"]);
            exit;
        }
        if (!validarEntradaSQL($_POST['direccion'], 'direccion')) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => "Entrada inválida detectada en el campo: Direccion"]);
            exit;
        }
        
        // Validación con expresiones regulares (CAPA 5)
        $id_proveedor = (int)$_POST['id_proveedor'];
        $tipo_documento = strtoupper(trim($_POST['tipo_documento']));
        $numero_documento = trim($_POST['numero_documento']);
        $nombre = ucfirst(strtolower(trim($_POST['nombre'])));
        $correo = strtolower(trim($_POST['correo']));
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        
        // Validar ID del proveedor (numérico positivo)
        if ($id_proveedor <= 0) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'ID de proveedor inválido']);
            exit;
        }
        
        // Validar tipo de documento (expresión regular)
        if (!preg_match('/^[VJEG]{1}$/', $tipo_documento)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Tipo de documento inválido']);
            exit;
        }
        
        // Validar número de documento (solo números, 7-9 dígitos)
        if (!preg_match('/^[0-9]{7,9}$/', $numero_documento)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Número de documento inválido. Debe contener 7-9 dígitos numéricos']);
            exit;
        }
        
        // Validar nombre (letras, espacios, máximo 30 caracteres)
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,30}$/', $nombre)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Nombre inválido. Solo letras y espacios, 3-30 caracteres']);
            exit;
        }
        
        // Validar correo electrónico
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) < 5 || strlen($correo) > 60) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Correo electrónico inválido']);
            exit;
        }
        
        // Validar teléfono (formato: 0414-0000000 o similar)
        if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $telefono)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Teléfono inválido. Formato esperado: 0414-0000000']);
            exit;
        }
        
        // Validar dirección (máximo 70 caracteres)
        if (strlen($direccion) < 5 || strlen($direccion) > 70) {
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Dirección inválida. Debe tener entre 5 y 70 caracteres']);
            exit;
        }
        
        // Preparar datos para el modelo
        $d = [
            'id_proveedor'     => $id_proveedor,
            'numero_documento' => $numero_documento,
            'tipo_documento'   => $tipo_documento,
            'nombre'           => $nombre,
            'correo'           => $correo,
            'telefono'         => $telefono,
            'direccion'        => $direccion
        ];
        
        // Obtener nombre actual para bitácora
        $old = $obj->consultarPorId($id_proveedor);
        
        // Ejecutar operación en el modelo
        $res = $obj->procesarProveedor(
            json_encode(['operacion'=>'actualizar','datos'=>$d])
        );
        
        // Registrar en bitácora si fue exitoso
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Actualizar Proveedor',
                'descripcion' => "$rolText actualizó proveedor {$old['nombre']}"
            ]));
        }
        
        echo json_encode($res);
        exit;
    }

    // d) Eliminar (desactivar) proveedor
    if (isset($_POST['eliminar'])) {
        // CAPA 1: Sesión activa (ya validada arriba)
        
        // CAPA 2: Validación explícita de permisos
        if (!tieneAcceso(9, 4)) {  // 9 = módulo proveedor, 4 = eliminar
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        
        // CAPA 3: Validación de clave foránea (id_proveedor)
        $proveedores = $obj->consultar();
        if (!validarIdProveedor($_POST['id_proveedor'], $proveedores)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'El proveedor seleccionado no es válido']);
            exit;
        }
        
        // CAPA 4: Validación de campo vacío
        if (empty($_POST['id_proveedor'])) {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'ID de proveedor requerido']);
            exit;
        }
        
        // CAPA 5: Sanitización y validación numérica
        if (!is_numeric($_POST['id_proveedor'])) {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'ID de proveedor debe ser numérico']);
            exit;
        }
        
        $id   = (int)$_POST['id_proveedor'];
        
        // Validación adicional con expresión regular
        if (!preg_match('/^[0-9]+$/', (string)$id)) {
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'ID de proveedor inválido']);
            exit;
        }
        
        $prov = $obj->consultarPorId($id);
        $nombre = $prov['nombre'] ?? "ID $id";
        
        // Ejecutar operación en el modelo
        $res = $obj->procesarProveedor(
            json_encode(['operacion'=>'eliminar','datos'=>['id_proveedor'=>$id]])
        );
        
        // Registrar en bitácora si fue exitoso
        if ($res['respuesta'] == 1) {
            $obj->registrarBitacora(json_encode([
                'id_persona'  => $_SESSION['id'],
                'accion'      => 'Eliminar Proveedor',
                'descripcion' => "$rolText eliminó proveedor $nombre"
            ]));
        }
        
        echo json_encode($res);
        exit;
    }
} else  if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(9, 1)) {
        $bitacora = [
            'id_persona' => $_SESSION["id"],
            'accion' => 'Acceso a Módulo',
            'descripcion' => 'módulo de Proveedor'
        ];
        $bitacoraObj = new Bitacora();
        $bitacoraObj->registrarOperacion($bitacora['accion'], 'proveedor', $bitacora);

       $registro = $obj->consultar();
        $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'proveedor';
        require_once 'vista/proveedor.php';
} else {
        require_once 'vista/seguridad/privilegio.php';

} 