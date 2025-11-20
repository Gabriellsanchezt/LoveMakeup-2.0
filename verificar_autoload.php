<?php
/**
 * Script de verificación del autoload de Composer
 * Ejecutar este script para verificar que el autoload está configurado correctamente
 * 
 * Uso: php verificar_autoload.php
 * O acceder desde el navegador: http://tu-dominio.com/verificar_autoload.php
 */

// Cargar bootstrap
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación del Autoload - LoveMakeup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            color: #4CAF50;
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #f44336;
            background: #ffebee;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #2196F3;
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            color: #ff9800;
            background: #fff3e0;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        ul {
            list-style-type: none;
            padding-left: 0;
        }
        li {
            padding: 5px 0;
        }
        .check {
            color: #4CAF50;
            font-weight: bold;
        }
        .cross {
            color: #f44336;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación del Autoload de Composer</h1>
        
        <?php
        $errores = [];
        $advertencias = [];
        $exitos = [];
        
        // 1. Verificar que el autoload existe
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            $exitos[] = "El archivo vendor/autoload.php existe";
        } else {
            $errores[] = "El archivo vendor/autoload.php NO existe";
        }
        
        // 2. Verificar que Composer está instalado
        if (class_exists('Composer\Autoload\ClassLoader')) {
            $exitos[] = "La clase ClassLoader de Composer está disponible";
        } else {
            $errores[] = "La clase ClassLoader de Composer NO está disponible";
        }
        
        // 3. Verificar namespaces configurados
        $namespaces = [
            'LoveMakeup\\Proyecto\\Modelo\\' => 'modelo/',
            'LoveMakeup\\Proyecto\\Controlador\\' => 'controlador/',
            'LoveMakeup\\Proyecto\\Config\\' => 'config/'
        ];
        
        foreach ($namespaces as $namespace => $directorio) {
            $directorioCompleto = __DIR__ . '/' . $directorio;
            if (is_dir($directorioCompleto)) {
                $exitos[] = "El directorio para el namespace '$namespace' existe: $directorio";
            } else {
                $errores[] = "El directorio para el namespace '$namespace' NO existe: $directorio";
            }
        }
        
        // 4. Probar cargar una clase de ejemplo
        try {
            if (class_exists('LoveMakeup\Proyecto\Config\Conexion')) {
                $exitos[] = "La clase Conexion se puede cargar correctamente";
            } else {
                $advertencias[] = "La clase Conexion no se pudo cargar (puede ser normal si no existe)";
            }
        } catch (Exception $e) {
            $errores[] = "Error al intentar cargar la clase Conexion: " . $e->getMessage();
        }
        
        // 5. Verificar archivo composer.json
        $composerJson = __DIR__ . '/composer.json';
        if (file_exists($composerJson)) {
            $composerData = json_decode(file_get_contents($composerJson), true);
            if (isset($composerData['autoload']['psr-4'])) {
                $exitos[] = "El archivo composer.json tiene configuración PSR-4";
            } else {
                $advertencias[] = "El archivo composer.json no tiene configuración PSR-4";
            }
        } else {
            $errores[] = "El archivo composer.json NO existe";
        }
        
        // 6. Verificar permisos del directorio vendor
        $vendorDir = __DIR__ . '/vendor';
        if (is_dir($vendorDir)) {
            if (is_readable($vendorDir)) {
                $exitos[] = "El directorio vendor es legible";
            } else {
                $errores[] = "El directorio vendor NO es legible (problema de permisos)";
            }
        } else {
            $errores[] = "El directorio vendor NO existe";
        }
        
        // Mostrar resultados
        if (!empty($exitos)) {
            echo '<div class="success"><strong>✓ Verificaciones Exitosas:</strong><ul>';
            foreach ($exitos as $exito) {
                echo '<li><span class="check">✓</span> ' . htmlspecialchars($exito) . '</li>';
            }
            echo '</ul></div>';
        }
        
        if (!empty($advertencias)) {
            echo '<div class="warning"><strong>⚠ Advertencias:</strong><ul>';
            foreach ($advertencias as $advertencia) {
                echo '<li><span class="cross">⚠</span> ' . htmlspecialchars($advertencia) . '</li>';
            }
            echo '</ul></div>';
        }
        
        if (!empty($errores)) {
            echo '<div class="error"><strong>✗ Errores Encontrados:</strong><ul>';
            foreach ($errores as $error) {
                echo '<li><span class="cross">✗</span> ' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
            
            echo '<div class="info"><strong>💡 Soluciones Recomendadas:</strong><ul>';
            echo '<li>Ejecutar: <code>composer install</code> en el directorio raíz del proyecto</li>';
            echo '<li>Verificar que el directorio vendor tiene permisos de lectura</li>';
            echo '<li>Verificar que composer.json está correctamente configurado</li>';
            echo '<li>Ejecutar: <code>composer dump-autoload</code> para regenerar el autoload</li>';
            echo '</ul></div>';
        } else {
            echo '<div class="success"><strong>🎉 ¡Todo está correcto!</strong><p>El autoload de Composer está configurado correctamente.</p></div>';
        }
        
        // Información adicional
        echo '<div class="info"><strong>ℹ Información del Sistema:</strong><ul>';
        echo '<li>PHP Version: ' . PHP_VERSION . '</li>';
        echo '<li>Directorio raíz: ' . __DIR__ . '</li>';
        echo '<li>Ruta del autoload: ' . $autoloadPath . '</li>';
        echo '<li>Separador de directorios: ' . DIRECTORY_SEPARATOR . '</li>';
        echo '</ul></div>';
        ?>
        
        <p><small>Este archivo puede ser eliminado después de la verificación en producción por seguridad.</small></p>
    </div>
</body>
</html>

