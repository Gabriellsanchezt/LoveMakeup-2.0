<?php

use LoveMakeup\Proyecto\Modelo\Producto;
use LoveMakeup\Proyecto\Modelo\Bitacora;

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

/* FUNCIONES DE VALIDACIÓN Y SANITIZACIÓN CONTRA INYECCIÓN SQL */

function detectarInyeccionSQL($valor) {
    if (empty($valor)) return false;

    $valor_lower = strtolower($valor);
    $patrones_peligrosos = [
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bselect\b.*\bfrom\b)/i',
        '/(\binsert\b.*\binto\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(\bcreate\b.*\btable\b)/i',
        '/(\balter\b.*\btable\b)/i',
        '/(\bexec\b|\bexecute\b)/i',
        '/(\bsp_\w+)/i',
        '/(\bxp_\w+)/i',
        '/(--|\#|\/\*|\*\/)/',
        '/(\bor\b.*\b1\s*=\s*1\b)/i',
        '/(\band\b.*\b1\s*=\s*1\b)/i',
        '/(\bor\b.*\b1\s*=\s*0\b)/i',
        '/(\band\b.*\b1\s*=\s*0\b)/i',
        '/(\bwaitfor\b.*\bdelay\b)/i'
    ];

    foreach ($patrones_peligrosos as $patron) {
        if (preg_match($patron, $valor_lower)) {
            return true;
        }
    }

    return false;
}

function sanitizarEntero($valor, $min = null, $max = null) {
    if (!is_numeric($valor)) return null;
    $valor = (int)$valor;
    if ($min !== null && $valor < $min) return null;
    if ($max !== null && $valor > $max) return null;
    return $valor;
}

function sanitizarString($valor, $maxLength = 255) {
    if (empty($valor)) return '';
    if (detectarInyeccionSQL($valor)) return '';
    $valor = trim($valor);
    $caracteres_peligrosos = [';', '--', '/*', '*/', '<', '>', '"', "'", '`'];
    foreach ($caracteres_peligrosos as $char) {
        $valor = str_replace($char, '', $valor);
    }
    if (strlen($valor) > $maxLength) $valor = substr($valor, 0, $maxLength);
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}
    


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
        'nombre'         => ucfirst(strtolower(sanitizarString($_POST['nombre'], 100))),
        'descripcion'    => sanitizarString($_POST['descripcion'], 500),
        'id_marca'       => sanitizarEntero($_POST['marca'], 1),
        'cantidad_mayor' => sanitizarEntero($_POST['cantidad_mayor'], 0),
        'precio_mayor'   => sanitizarEntero($_POST['precio_mayor'], 0),
        'precio_detal'   => sanitizarEntero($_POST['precio_detal'], 0),
        'stock_maximo'   => sanitizarEntero($_POST['stock_maximo'], 0),
        'stock_minimo'   => sanitizarEntero($_POST['stock_minimo'], 0),
        'id_categoria'   => sanitizarEntero($_POST['categoria'], 1),
        'imagenes'       => $imagenes
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
   $datosProducto = [
    'operacion' => 'actualizar',
    'datos' => [
        'id_producto'    => sanitizarEntero($_POST['id_producto'], 1),
        'nombre'         => ucfirst(strtolower(sanitizarString($_POST['nombre'], 100))),
        'descripcion'    => sanitizarString($_POST['descripcion'], 500),
        'id_marca'       => sanitizarEntero($_POST['marca'], 1),
        'cantidad_mayor' => sanitizarEntero($_POST['cantidad_mayor'], 0),
        'precio_mayor'   => sanitizarEntero($_POST['precio_mayor'], 0),
        'precio_detal'   => sanitizarEntero($_POST['precio_detal'], 0),
        'stock_maximo'   => sanitizarEntero($_POST['stock_maximo'], 0),
        'stock_minimo'   => sanitizarEntero($_POST['stock_minimo'], 0),
        'id_categoria'   => sanitizarEntero($_POST['categoria'], 1),
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
                'id_producto' => sanitizarEntero($_POST['id_producto'], 1)
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
        'id_producto'   => sanitizarEntero($_POST['id_producto'], 1),
        'estatus_actual'=> sanitizarEntero($_POST['estatus_actual'], 0, 2)
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