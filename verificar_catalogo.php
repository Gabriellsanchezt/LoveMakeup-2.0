<?php
/**
 * Script de verificación para diagnosticar el problema con la clase Catalogo
 * Sube este archivo a la raíz de tu proyecto en el hosting y accede a él desde el navegador
 */

echo "<h2>Diagnóstico de la Clase Catalogo</h2>";
echo "<pre>";

// 1. Verificar que el autoloader existe
echo "1. Verificando autoloader...\n";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "   ✓ vendor/autoload.php existe\n";
    require_once $autoloadPath;
    echo "   ✓ Autoloader cargado\n";
} else {
    echo "   ✗ ERROR: vendor/autoload.php NO existe\n";
    echo "   Ruta buscada: " . $autoloadPath . "\n";
}

// 2. Verificar que el archivo Catalogo.php existe
echo "\n2. Verificando archivo Catalogo.php...\n";
$catalogoPath1 = __DIR__ . '/modelo/Catalogo.php';
$catalogoPath2 = __DIR__ . '/modelo/catalogo.php';

if (file_exists($catalogoPath1)) {
    echo "   ✓ modelo/Catalogo.php existe (con C mayúscula)\n";
    echo "   Ruta: " . $catalogoPath1 . "\n";
} else {
    echo "   ✗ modelo/Catalogo.php NO existe (con C mayúscula)\n";
}

if (file_exists($catalogoPath2)) {
    echo "   ⚠ modelo/catalogo.php existe (con c minúscula) - Esto puede causar problemas\n";
    echo "   Ruta: " . $catalogoPath2 . "\n";
} else {
    echo "   ✓ modelo/catalogo.php NO existe (correcto)\n";
}

// 3. Verificar que la clase se puede cargar
echo "\n3. Verificando si la clase se puede cargar...\n";
if (class_exists('LoveMakeup\Proyecto\Modelo\Catalogo')) {
    echo "   ✓ La clase LoveMakeup\\Proyecto\\Modelo\\Catalogo existe\n";
} else {
    echo "   ✗ ERROR: La clase LoveMakeup\\Proyecto\\Modelo\\Catalogo NO se encuentra\n";
    
    // Intentar cargar manualmente
    echo "\n   Intentando cargar manualmente...\n";
    if (file_exists($catalogoPath1)) {
        require_once $catalogoPath1;
        if (class_exists('LoveMakeup\Proyecto\Modelo\Catalogo')) {
            echo "   ✓ Clase cargada manualmente con éxito\n";
        } else {
            echo "   ✗ ERROR: La clase no se pudo cargar incluso manualmente\n";
        }
    } elseif (file_exists($catalogoPath2)) {
        require_once $catalogoPath2;
        if (class_exists('LoveMakeup\Proyecto\Modelo\Catalogo')) {
            echo "   ✓ Clase cargada manualmente desde catalogo.php (minúscula)\n";
            echo "   ⚠ ADVERTENCIA: El archivo debería llamarse Catalogo.php (mayúscula)\n";
        }
    }
}

// 4. Verificar dependencias
echo "\n4. Verificando dependencias...\n";
$dependencias = [
    'LoveMakeup\Proyecto\Modelo\Categoria' => __DIR__ . '/modelo/categoria.php',
    'LoveMakeup\Proyecto\Modelo\Producto' => __DIR__ . '/modelo/producto.php',
    'LoveMakeup\Proyecto\Config\Conexion' => __DIR__ . '/config/conexion.php',
];

foreach ($dependencias as $clase => $archivo) {
    if (file_exists($archivo)) {
        echo "   ✓ " . basename($archivo) . " existe\n";
    } else {
        echo "   ✗ " . basename($archivo) . " NO existe\n";
    }
    
    if (class_exists($clase)) {
        echo "      ✓ Clase $clase cargada\n";
    } else {
        echo "      ✗ Clase $clase NO cargada\n";
    }
}

// 5. Verificar estructura de directorios
echo "\n5. Verificando estructura de directorios...\n";
$directorios = [
    'modelo',
    'controlador',
    'config',
    'vendor',
];

foreach ($directorios as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "   ✓ Directorio $dir existe\n";
    } else {
        echo "   ✗ Directorio $dir NO existe\n";
    }
}

// 6. Intentar instanciar la clase
echo "\n6. Intentando instanciar la clase...\n";
try {
    if (class_exists('LoveMakeup\Proyecto\Modelo\Catalogo')) {
        $catalogo = new \LoveMakeup\Proyecto\Modelo\Catalogo();
        echo "   ✓ Clase instanciada correctamente\n";
    } else {
        echo "   ✗ No se puede instanciar: la clase no existe\n";
    }
} catch (Exception $e) {
    echo "   ✗ ERROR al instanciar: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
}

echo "\n</pre>";
echo "<h3>Recomendaciones:</h3>";
echo "<ul>";
echo "<li>Si el archivo se llama 'catalogo.php' (minúscula), renómbralo a 'Catalogo.php' (mayúscula)</li>";
echo "<li>Si el autoloader no existe, ejecuta 'composer dump-autoload' en el servidor</li>";
echo "<li>Si el autoloader existe pero no funciona, sube toda la carpeta vendor/ nuevamente</li>";
echo "<li>Asegúrate de que todos los archivos estén subidos correctamente</li>";
echo "</ul>";
?>

