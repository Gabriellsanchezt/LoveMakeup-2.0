<?php
/**
 * Bootstrap file para cargar el autoload de Composer
 * Este archivo debe ser incluido al inicio de cualquier script que use clases con namespace
 * 
 * @version 1.0
 * @author LoveMakeup C.A.
 */

// Prevenir acceso directo si es necesario (opcional)
if (!defined('BOOTSTRAP_LOADED')) {
    define('BOOTSTRAP_LOADED', true);
}

// Cargar el autoload de Composer usando ruta absoluta
$autoloadPath = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Normalizar la ruta para diferentes sistemas operativos
$autoloadPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $autoloadPath);

if (!file_exists($autoloadPath)) {
    // Si no existe en la ruta esperada, intentar buscar en el directorio padre
    $autoloadPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    $autoloadPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $autoloadPath);
    
    if (!file_exists($autoloadPath)) {
        // Log del error para debugging en producción
        $errorMsg = 'Error: No se pudo encontrar vendor/autoload.php en ' . __DIR__ . ' ni en ' . dirname(__DIR__);
        error_log($errorMsg);
        
        // En producción, mostrar mensaje genérico al usuario
        if (!defined('APP_DEBUG') || !APP_DEBUG) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error de Configuración</title></head><body><h1>Error de Configuración</h1><p>El sistema no está configurado correctamente. Por favor, contacte al administrador.</p></body></html>');
        } else {
            // En desarrollo, mostrar mensaje detallado
            http_response_code(500);
            die('Error de configuración: Autoload de Composer no encontrado.<br>Rutas probadas:<br>1. ' . __DIR__ . '/vendor/autoload.php<br>2. ' . dirname(__DIR__) . '/vendor/autoload.php<br><br>Por favor, ejecute "composer install" en el directorio raíz del proyecto.');
        }
    }
}

// Cargar el autoload
require_once $autoloadPath;

// Verificar que el autoload se cargó correctamente
if (!class_exists('Composer\Autoload\ClassLoader')) {
    $errorMsg = 'Error: El autoload de Composer no se cargó correctamente';
    error_log($errorMsg);
    
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error de Configuración</title></head><body><h1>Error de Configuración</h1><p>El sistema no está configurado correctamente. Por favor, contacte al administrador.</p></body></html>');
    } else {
        die('Error: El autoload de Composer no se cargó correctamente. Verifique que composer install se ejecutó correctamente.');
    }
}

