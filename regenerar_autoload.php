<?php
/**
 * Script para regenerar el autoload de Composer en producción
 * 
 * USO:
 * 1. Subir este archivo al servidor
 * 2. Acceder desde el navegador: http://tu-dominio.com/regenerar_autoload.php
 * 3. O ejecutar por línea de comandos: php regenerar_autoload.php
 * 
 * ⚠️ IMPORTANTE: Eliminar este archivo después de usarlo por seguridad
 */

// Verificar que composer está disponible
$composerPath = __DIR__ . '/composer.json';
$vendorAutoload = __DIR__ . '/vendor/autoload.php';

if (!file_exists($composerPath)) {
    die("ERROR: composer.json no encontrado en " . __DIR__);
}

// Verificar si composer está instalado
$composerCommand = 'composer';
$output = [];
$returnVar = 0;
exec("which composer 2>&1", $output, $returnVar);

if ($returnVar !== 0) {
    // Intentar con ruta completa común
    $composerCommand = '/usr/local/bin/composer';
    if (!file_exists($composerCommand)) {
        $composerCommand = '/usr/bin/composer';
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerar Autoload - LoveMakeup</title>
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
            border-bottom: 3px solid #2196F3;
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
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px 10px 0;
        }
        .btn:hover {
            background: #1976D2;
        }
        .btn-danger {
            background: #f44336;
        }
        .btn-danger:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Regenerar Autoload de Composer</h1>
        
        <?php
        $errores = [];
        $exitos = [];
        $comandos = [];
        
        // Verificar estructura
        echo '<div class="info"><strong>ℹ Verificando estructura...</strong><ul>';
        
        if (file_exists($composerPath)) {
            echo '<li>✓ composer.json encontrado</li>';
            $composerData = json_decode(file_get_contents($composerPath), true);
            if (isset($composerData['autoload']['psr-4'])) {
                echo '<li>✓ Configuración PSR-4 encontrada</li>';
                foreach ($composerData['autoload']['psr-4'] as $namespace => $dir) {
                    $dirPath = __DIR__ . '/' . $dir;
                    if (is_dir($dirPath)) {
                        echo '<li>✓ Directorio para ' . htmlspecialchars($namespace) . ' existe: ' . htmlspecialchars($dir) . '</li>';
                    } else {
                        echo '<li>✗ Directorio para ' . htmlspecialchars($namespace) . ' NO existe: ' . htmlspecialchars($dir) . '</li>';
                        $errores[] = "Directorio no encontrado: $dir";
                    }
                }
            }
        } else {
            $errores[] = "composer.json no encontrado";
        }
        
        echo '</ul></div>';
        
        // Si hay un parámetro de acción
        if (isset($_GET['action']) && $_GET['action'] === 'regenerate') {
            echo '<div class="info"><strong>🔄 Ejecutando comandos...</strong></div>';
            
            // Comando 1: composer dump-autoload
            $comando1 = "cd " . escapeshellarg(__DIR__) . " && composer dump-autoload --optimize 2>&1";
            echo '<div class="info"><strong>Comando 1:</strong> <code>composer dump-autoload --optimize</code></div>';
            exec($comando1, $output1, $returnVar1);
            
            echo '<pre>';
            echo htmlspecialchars(implode("\n", $output1));
            echo '</pre>';
            
            if ($returnVar1 === 0) {
                $exitos[] = "Autoload regenerado exitosamente";
                echo '<div class="success">✓ Autoload regenerado correctamente</div>';
            } else {
                $errores[] = "Error al regenerar autoload";
                echo '<div class="error">✗ Error al ejecutar composer dump-autoload</div>';
            }
            
            // Verificar resultado
            if (file_exists($vendorAutoload)) {
                echo '<div class="success">✓ vendor/autoload.php existe</div>';
                
                // Intentar cargar y verificar
                require_once $vendorAutoload;
                if (class_exists('LoveMakeup\Proyecto\Modelo\Catalogo')) {
                    echo '<div class="success">✓ La clase Catalogo se puede cargar correctamente</div>';
                } else {
                    echo '<div class="error">✗ La clase Catalogo NO se puede cargar</div>';
                }
            } else {
                echo '<div class="error">✗ vendor/autoload.php NO existe. Necesitas ejecutar: composer install</div>';
            }
        } else {
            // Mostrar instrucciones
            echo '<div class="warning">';
            echo '<strong>⚠️ Instrucciones:</strong><br><br>';
            echo 'Este script regenerará el autoload de Composer. Asegúrate de que:<br>';
            echo '<ul>';
            echo '<li>Composer esté instalado en el servidor</li>';
            echo '<li>El directorio vendor exista (si no, ejecuta primero: composer install)</li>';
            echo '<li>Tengas permisos de escritura en el directorio vendor</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<strong>📋 Comandos que se ejecutarán:</strong><br>';
            echo '<pre>cd ' . htmlspecialchars(__DIR__) . "\n";
            echo 'composer dump-autoload --optimize</pre>';
            echo '</div>';
            
            if (empty($errores)) {
                echo '<a href="?action=regenerate" class="btn">🔄 Regenerar Autoload</a>';
            } else {
                echo '<div class="error">';
                echo '<strong>Errores encontrados:</strong><ul>';
                foreach ($errores as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul></div>';
            }
        }
        
        // Información adicional
        echo '<div class="info">';
        echo '<strong>ℹ Información del Sistema:</strong><ul>';
        echo '<li>Directorio: ' . __DIR__ . '</li>';
        echo '<li>PHP Version: ' . PHP_VERSION . '</li>';
        echo '<li>Composer path: ' . (file_exists($composerPath) ? 'Encontrado' : 'No encontrado') . '</li>';
        echo '<li>vendor/autoload.php: ' . (file_exists($vendorAutoload) ? 'Existe' : 'No existe') . '</li>';
        echo '</ul></div>';
        
        echo '<div class="warning">';
        echo '<strong>⚠️ IMPORTANTE:</strong> Elimina este archivo después de usarlo por seguridad.';
        echo '</div>';
        ?>
    </div>
</body>
</html>

