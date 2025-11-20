<?php
    // Cargar bootstrap que maneja el autoload de manera segura
    require_once __DIR__ . '/bootstrap.php';

    // Iniciar sesión para validar acceso (si no está ya iniciada)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // ============================================
    // CONFIGURACIÓN DE RUTAS Y PERMISOS
    // Previene acceso directo mediante edición de URLs
    // ============================================
    
    $configRutas = [
        // Rutas públicas - No requieren autenticación
        'publicas' => [
            'login',
            'registrocliente',
            'olvidoclave',
            'aviso_legal',
            'catalogo',
            'catalogo_producto',
            'catalogo_consejo',
            'catalogo_contacto',
            'error'
        ],
        
        // Rutas que requieren autenticación (cualquier usuario logueado - nivel 1, 2 o 3)
        'autenticadas' => [
            'catalogo_datos',
            'catalogo_pedido',
            'catalogo_favorito',
            'listadeseo',
            'carrito',
            'vercarrito',
            'pedidoweb',
            'Pedidoconfirmar',
            'Pedidoentrega',
            'Pedidopago',
            'pedidoweb_tracking',
            'reserva_cliente'
        ],
        
        // Rutas que requieren nivel 2 o 3 (Asesora de Venta o Administrador)
        // Nivel 1 (Cliente) será redirigido automáticamente
        'administrativas' => [
            'home',
            'entrada',
            'salida',
            'producto',
            'categoria',
            'marca',
            'proveedor',
            'cliente',
            'usuario',
            'tipousuario',
            'metodoentrega',
            'metodopago',
            'tasacambio',
            'delivery',
            'reserva',
            'reporte',
            'notificacion',
            'bitacora',
            'VentaWeb',
            'datos',
            'conexion'
        ],
        
        // Rutas especiales que requieren nivel 3 (Solo Administrador)
        'solo_admin' => [
            'bitacora',
            'usuario',
            'tipousuario'
        ]
    ];
    
    // Obtener la página solicitada
    $pagina = "catalogo";
        if (!empty($_GET['pagina'])){
        $pagina = trim($_GET['pagina']);
        // Sanitizar para prevenir path traversal
        $pagina = preg_replace('/[^a-zA-Z0-9_-]/', '', $pagina);
        }

    // Validar que el archivo del controlador exista
    if (!is_file("controlador/".$pagina.".php")){
        require_once("controlador/error.php");
        exit;
    }
    
    // ============================================
    // VALIDACIÓN DE ACCESO POR SEGURIDAD
    // ============================================
    
    $rutaPublica = in_array($pagina, $configRutas['publicas']);
    $rutaAutenticada = in_array($pagina, $configRutas['autenticadas']);
    $rutaAdministrativa = in_array($pagina, $configRutas['administrativas']);
    $rutaSoloAdmin = in_array($pagina, $configRutas['solo_admin']);
    
    $usuarioLogueado = !empty($_SESSION['id']);
    $nivelRol = isset($_SESSION['nivel_rol']) ? (int)$_SESSION['nivel_rol'] : 0;
    
    // 1. Si el usuario está logueado pero intenta acceder a login, redirigir según su rol
    // EXCEPCIÓN: Permitir acceso a login si viene un POST de cerrar sesión
    if ($pagina === 'login' && $usuarioLogueado && !isset($_POST['cerrar']) && !isset($_POST['cerrarolvido'])) {
        if ($nivelRol == 1) {
            header("Location: ?pagina=catalogo");
        } else {
            header("Location: ?pagina=home");
        }
        exit;
    }
    
    // 2. Si la ruta es pública, permitir acceso sin autenticación
    if ($rutaPublica) {
        // Permitir acceso, continuar
    }
    // 3. Si la ruta es administrativa, validar nivel de acceso
    elseif ($rutaAdministrativa) {
        // Requiere autenticación
        if (!$usuarioLogueado) {
            header("Location: ?pagina=login");
            exit;
        }
        
        // Clientes (nivel 1) no pueden acceder a rutas administrativas
        if ($nivelRol == 1) {
            header("Location: ?pagina=catalogo");
            exit;
        }
        
        // Validar rutas solo para administradores (nivel 3)
        if ($rutaSoloAdmin && $nivelRol != 3) {
            header("Location: ?pagina=home");
            exit;
        }
    }
    // 4. Si la ruta requiere autenticación (cualquier usuario logueado)
    elseif ($rutaAutenticada) {
        if (!$usuarioLogueado) {
            header("Location: ?pagina=login");
            exit;
        }
    }
    // 5. Por defecto, si la ruta no está en ninguna categoría, requiere autenticación (más seguro)
    else {
        if (!$usuarioLogueado) {
            header("Location: ?pagina=login");
            exit;
        }
    }
    
    // Si todo está correcto, cargar el controlador
    require_once("controlador/".$pagina.".php");

?>
