<?php

use LoveMakeup\Proyecto\Modelo\Producto;
use LoveMakeup\Proyecto\Modelo\Bitacora;


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (empty($_SESSION["id"])) {
    header("location:?pagina=login");
    exit;
}

require_once 'permiso.php';

if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
} 


if ($_SESSION["nivel_rol"] == 1) {
    header("Location: ?pagina=catalogo");
    exit();
}


require_once 'permiso.php';


$objproducto = new Producto();


$registro = $objproducto->consultar();
$categoria = $objproducto->obtenerCategoria();
$marca = $objproducto->obtenerMarca();

// --- MANEJO DE PETICIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Obtener Imágenes (AJAX)
    if (isset($_POST['accion']) && $_POST['accion'] === 'obtenerImagenes') {
        if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] < 2 || !tieneAcceso(6, 1)) { // requiere nivel >=2 y permiso 1 (ver)
            echo json_encode(['respuesta' => 0, 'accion' => 'obtenerImagenes', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        $id_producto = $_POST['id_producto'];
        $imagenes = $objproducto->obtenerImagenes($id_producto);
        echo json_encode(['respuesta' => 1, 'imagenes' => $imagenes]);
        exit;
    }

    // 2. Registrar Producto
    if (isset($_POST['registrar'])) {
        if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] < 2 || !tieneAcceso(6, 2)) { // requiere nivel >=2 y permiso 2
            echo json_encode(['respuesta' => 0, 'accion' => 'incluir', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        // Validación básica de campos requeridos
        if (!empty($_POST['nombre']) && !empty($_POST['descripcion']) && !empty($_POST['marca']) && 
            !empty($_POST['cantidad_mayor']) && !empty($_POST['precio_mayor']) && !empty($_POST['precio_detal']) && 
            !empty($_POST['stock_maximo']) && !empty($_POST['stock_minimo']) && !empty($_POST['categoria'])) {
            
            $imagenes = [];

            // Manejo de subida de archivos 
            if (isset($_FILES['imagenarchivo'])) {
                foreach ($_FILES['imagenarchivo']['name'] as $indice => $nombreArchivo) {
                    if ($_FILES['imagenarchivo']['error'][$indice] == 0) {
                        $rutaTemporal = $_FILES['imagenarchivo']['tmp_name'][$indice];
                        // Generar nombre seguro para el archivo
                        $nuevoNombre = uniqid('img_') . "_" . basename($nombreArchivo);
                        $rutaDestino = 'assets/img/Imgproductos/' . $nuevoNombre;
                        
                        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
                            $imagenes[] = $rutaDestino;
                        }
                    }
                }
            }

            $datosProducto = [
                'operacion' => 'registrar',
                'datos' => [
                    'nombre'         => $_POST['nombre'],
                    'descripcion'    => $_POST['descripcion'],
                    'id_marca'       => $_POST['marca'],
                    'cantidad_mayor' => $_POST['cantidad_mayor'],
                    'precio_mayor'   => $_POST['precio_mayor'],
                    'precio_detal'   => $_POST['precio_detal'],
                    'stock_maximo'   => $_POST['stock_maximo'],
                    'stock_minimo'   => $_POST['stock_minimo'],
                    'id_categoria'   => $_POST['categoria'],
                    'imagenes'       => $imagenes
                ]
            ];

            $resultadoRegistro = $objproducto->procesarProducto(json_encode($datosProducto));

            // Bitácora
            if ($resultadoRegistro['respuesta'] == 1) {
                $bitacora = [
                    'id_persona' => $_SESSION["id"],
                    'accion' => 'Registro de producto',
                    'descripcion' => 'Se registró el producto: ' . $datosProducto['datos']['nombre']
                ];
                $bitacoraObj = new Bitacora();
                $bitacoraObj->registrarOperacion($bitacora['accion'], 'producto', $bitacora);
            }

            echo json_encode($resultadoRegistro);
        } else {
            echo json_encode(['respuesta' => 0, 'mensaje' => 'Faltan campos requeridos']);
        }
        exit;
    }

    // 3. Actualizar Producto
    else if (isset($_POST['actualizar'])) {
        if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] < 2 || !tieneAcceso(6, 3)) { // requiere nivel >=2 y permiso 3
            echo json_encode(['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        $imagenes = [];
        $imagenesReemplazos = [];

        // Eliminar imágenes marcadas
        if (!empty($_POST['imagenesEliminadas'])) {
            $imagenesEliminar = json_decode($_POST['imagenesEliminadas'], true);
            $objproducto->eliminarImagenes($imagenesEliminar);
        }

        // Mapeo de reemplazos
        $mapReemplazos = [];
        if (!empty($_POST['imagenesReemplazadas'])) {
            $tmp = json_decode($_POST['imagenesReemplazadas'], true);
            if (is_array($tmp)) {
                foreach ($tmp as $r) {
                    if (!empty($r['id_imagen']) && !empty($r['nombre'])) {
                        $mapReemplazos[$r['nombre']] = $r['id_imagen'];
                    }
                }
            }
        }

        // Imágenes existentes
        if (!empty($_POST['imagenesExistentes'])) {
            $imagenesExistentes = json_decode($_POST['imagenesExistentes'], true);
            foreach ($imagenesExistentes as $img) {
                $imagenes[] = [
                    'id_imagen' => $img['id_imagen'],
                    'url_imagen' => $img['url_imagen']
                ];
            }
        }

        // Subida de nuevas imágenes
        if (isset($_FILES['imagenarchivo'])) {
            foreach ($_FILES['imagenarchivo']['name'] as $indice => $nombreArchivo) {
                if ($_FILES['imagenarchivo']['error'][$indice] === 0) {
                    $rutaTemporal = $_FILES['imagenarchivo']['tmp_name'][$indice];
                    $nuevoNombre  = uniqid('img_') . "_" . basename($nombreArchivo);
                    $rutaDestino  = 'assets/img/Imgproductos/' . $nuevoNombre;

                    if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
                        // Si el nombre original está en reemplazos → UPDATE
                        if (isset($mapReemplazos[$nombreArchivo])) {
                            $imagenesReemplazos[] = [
                                'id_imagen'  => $mapReemplazos[$nombreArchivo],
                                'url_imagen' => $rutaDestino
                            ];
                        } else {
                            // Si no, es imagen nueva → INSERT
                            $imagenes[] = ['url_imagen' => $rutaDestino];
                        }
                    }
                }
            }
        }

        // Preparar datos para el Modelo
        $datosProducto = [
            'operacion' => 'actualizar',
            'datos' => [
                'id_producto'    => $_POST['id_producto'],
                'nombre'         => $_POST['nombre'],
                'descripcion'    => $_POST['descripcion'],
                'id_marca'       => $_POST['marca'],
                'cantidad_mayor' => $_POST['cantidad_mayor'],
                'precio_mayor'   => $_POST['precio_mayor'],
                'precio_detal'   => $_POST['precio_detal'],
                'stock_maximo'   => $_POST['stock_maximo'],
                'stock_minimo'   => $_POST['stock_minimo'],
                'id_categoria'   => $_POST['categoria'],
                'imagenes_nuevas'      => $imagenes,
                'imagenes_reemplazos'  => $imagenesReemplazos
            ]
        ];

        $resultado = $objproducto->procesarProducto(json_encode($datosProducto));

       
        if ($resultado['respuesta'] == 1) {
            $bitacora = [
                'id_persona' => $_SESSION["id"],
                'accion' => 'Modificación de producto',
                'descripcion' => 'Se modificó el producto: ' . $datosProducto['datos']['nombre']
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bitacora['accion'], 'producto', $bitacora);
        }

        echo json_encode($resultado);
        exit;
    }

    // 4. Eliminar Producto
    else if (isset($_POST['eliminar'])) {
        if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] < 2 || !tieneAcceso(6, 4)) { // requiere nivel >=2 y permiso 4
            echo json_encode(['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        $datosProducto = [
            'operacion' => 'eliminar',
            'datos' => [
                'id_producto' => $_POST['id_producto']
            ]
        ];

        $resultado = $objproducto->procesarProducto(json_encode($datosProducto));

        
        if ($resultado['respuesta'] == 1) {
            $bitacora = [
                'id_persona' => $_SESSION["id"],
                'accion' => 'Eliminación de producto',
                'descripcion' => 'Se eliminó el producto con ID: ' . $datosProducto['datos']['id_producto']
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bitacora['accion'], 'producto', $bitacora);
        }

        echo json_encode($resultado);
        exit;
    }

    // 5. Cambiar Estatus
    else if (isset($_POST['accion']) && $_POST['accion'] == 'cambiarEstatus') {
        if (!isset($_SESSION['nivel_rol']) || $_SESSION['nivel_rol'] < 2 || !tieneAcceso(6, 5)) { // requiere nivel >=2 y permiso 5
            echo json_encode(['respuesta' => 0, 'accion' => 'cambiarEstatus', 'mensaje' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
        $datosProducto = [
            'operacion' => 'cambiarEstatus',
            'datos' => [
                'id_producto'   => $_POST['id_producto'],
                'estatus_actual'=> $_POST['estatus_actual']
            ]
        ];

        $resultado = $objproducto->procesarProducto(json_encode($datosProducto));

       
        if ($resultado['respuesta'] == 1) {
            $bitacora = [
                'id_persona' => $_SESSION["id"],
                'accion' => 'Cambio de estatus de producto',
                'descripcion' => 'Se cambió el estatus del producto con ID: ' . $datosProducto['datos']['id_producto']
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bitacora['accion'], 'producto', $bitacora);
        }

        echo json_encode($resultado);
        exit;
    }
}

// --- CARGA DE VISTA ---
else if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(6, 1)) {
    $bitacora = [
        'id_persona' => $_SESSION["id"],
        'accion' => 'Acceso a Módulo',
        'descripcion' => 'módulo de Producto'
    ];
    $bitacoraObj = new Bitacora();
    $bitacoraObj->registrarOperacion($bitacora['accion'], 'producto', $bitacora);
    
    $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'producto';
    require_once 'vista/producto.php';
} else {
    require_once 'vista/seguridad/privilegio.php';
} 

?>