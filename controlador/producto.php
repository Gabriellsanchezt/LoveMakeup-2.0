<?php

use LoveMakeup\Proyecto\Modelo\Producto;
use LoveMakeup\Proyecto\Modelo\Bitacora;

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
$objproducto = new Producto();

$registro = $objproducto->consultar();
$categoria = $objproducto->obtenerCategoria();
$marca = $objproducto->obtenerMarca();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'obtenerImagenes') {
        $id_producto = $_POST['id_producto'];
        $imagenes = $objproducto->obtenerImagenes($id_producto);
        echo json_encode(['respuesta' => 1, 'imagenes' => $imagenes]);
        exit;
    }
    if (isset($_POST['registrar'])) {
        if (!empty($_POST['nombre']) && !empty($_POST['descripcion']) && !empty($_POST['marca']) && !empty($_POST['cantidad_mayor']) && !empty($_POST['precio_mayor']) && !empty($_POST['precio_detal']) && !empty($_POST['stock_maximo']) && !empty($_POST['stock_minimo']) && !empty($_POST['categoria'])) {
            $rutaImagen = 'assets/img/logo.PNG';
            $imagenes = [];

if (isset($_FILES['imagenarchivo'])) {
    foreach ($_FILES['imagenarchivo']['name'] as $indice => $nombreArchivo) {
        if ($_FILES['imagenarchivo']['error'][$indice] == 0) {
            $rutaTemporal = $_FILES['imagenarchivo']['tmp_name'][$indice];
            $rutaDestino = 'assets/img/Imgproductos/' . $nombreArchivo;
            move_uploaded_file($rutaTemporal, $rutaDestino);
            $imagenes[] = $rutaDestino;
        }
    }
}
            if (!empty($imagenes)) {
                $rutaImagen = $imagenes[0]; // Usar la primera imagen como principal
            }

            $datosProducto = [
    'operacion' => 'registrar',
    'datos' => [
        'nombre' => ucfirst(strtolower($_POST['nombre'])),
        'descripcion' => $_POST['descripcion'],
        'id_marca' => $_POST['marca'],
        'cantidad_mayor' => $_POST['cantidad_mayor'],
        'precio_mayor' => $_POST['precio_mayor'],
        'precio_detal' => $_POST['precio_detal'],
        'stock_maximo' => $_POST['stock_maximo'],
        'stock_minimo' => $_POST['stock_minimo'],
        'id_categoria' => $_POST['categoria'],
        'imagenes' => $imagenes
    ]
];

            $resultadoRegistro = $objproducto->procesarProducto(json_encode($datosProducto));

            if ($resultadoRegistro['respuesta'] == 1) {
                $bitacora = [
                    'id_persona' => $_SESSION["id"],
                    'accion' => 'Registro de producto',
                    'descripcion' => 'Se registró el producto: ' . $datosProducto['datos']['nombre'] . ' ' . 
                                    $datosProducto['datos']['id_marca']
                ];
                $bitacoraObj = new Bitacora();
                $bitacoraObj->registrarOperacion($bitacora['accion'], 'producto', $bitacora);
            }

            echo json_encode($resultadoRegistro);
        }
    } else if(isset($_POST['actualizar'])) {
       $imagenes = [];
       $imagenesReemplazos = [];

    
    if (!empty($_POST['imagenesEliminadas'])) {
        $imagenesEliminar = json_decode($_POST['imagenesEliminadas'], true);
        $objproducto->eliminarImagenes($imagenesEliminar);
    }

    $mapReemplazos = [];
if (!empty($_POST['imagenesReemplazadas'])) {
    $tmp = json_decode($_POST['imagenesReemplazadas'], true);
    if (is_array($tmp)) {
        foreach ($tmp as $r) {
            if (!empty($r['id_imagen']) && !empty($r['nombre'])) {
                // clave por nombre de archivo
                $mapReemplazos[$r['nombre']] = $r['id_imagen'];
            }
        }
    }
}

    if (!empty($_POST['imagenesExistentes'])) {
        $imagenesExistentes = json_decode($_POST['imagenesExistentes'], true);
        foreach ($imagenesExistentes as $img) {
            $imagenes[] = [
                'id_imagen' => $img['id_imagen'],
                'url_imagen' => $img['url_imagen']
            ];
        }
    }

    if (isset($_FILES['imagenarchivo'])) {
    foreach ($_FILES['imagenarchivo']['name'] as $indice => $nombreArchivo) {
        if ($_FILES['imagenarchivo']['error'][$indice] === 0) {
            $rutaTemporal = $_FILES['imagenarchivo']['tmp_name'][$indice];
            $nuevoNombre  = uniqid('img_') . "_" . basename($nombreArchivo);
            $rutaDestino  = 'assets/img/Imgproductos/' . $nuevoNombre;

            move_uploaded_file($rutaTemporal, $rutaDestino);

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

    // 4️⃣ Preparar datos del producto
   $datosProducto = [
    'operacion' => 'actualizar',
    'datos' => [
        'id_producto'    => $_POST['id_producto'],
        'nombre'         => ucfirst(strtolower($_POST['nombre'])),
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
                'descripcion' => 'Se modificó el producto: ' . $datosProducto['datos']['nombre'] . ' ' . 
                                $datosProducto['datos']['id_marca']
            ];
            $bitacoraObj = new Bitacora();
            $bitacoraObj->registrarOperacion($bitacora['accion'], 'producto', $bitacora);
        }

        echo json_encode($resultado);

    } else if(isset($_POST['eliminar'])) {
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
    } else if(isset($_POST['accion']) && $_POST['accion'] == 'cambiarEstatus') {
        $datosProducto = [
            'operacion' => 'cambiarEstatus',
            'datos' => [
                'id_producto' => $_POST['id_producto'],
                'estatus_actual' => $_POST['estatus_actual']
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
    }
} else if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(3, 'ver')) {
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