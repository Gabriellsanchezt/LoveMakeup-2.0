<?php

namespace Tests\ConfigSelenium;

/**
 * Configuración para pruebas Selenium
 */
class SeleniumConfig
{
    /**
     * URL base de la aplicación
     */
    public const BASE_URL = 'http://localhost/LoveMakeup/LoveMakeup-2.0/';
    
    /**
     * URL del Selenium Server (WebDriver)
     */
    public const SELENIUM_SERVER_URL = 'http://localhost:4444';
    
    /**
     * Configuración del navegador
     * Navegador predeterminado: Microsoft Edge
     */
    public const BROWSER = 'edge'; // edge, chrome, firefox
    
    /**
     * Tiempo de espera implícita en segundos
     */
    public const IMPLICIT_WAIT = 10;
    
    /**
     * Tiempo de espera para elementos en segundos
     */
    public const EXPLICIT_WAIT = 15;
    
    /**
     * Tiempo de espera para carga de página en segundos
     */
    public const PAGE_LOAD_TIMEOUT = 30;
    
    /**
     * Tamaño de ventana del navegador
     */
    public const WINDOW_WIDTH = 1920;
    public const WINDOW_HEIGHT = 1080;
    
    /**
     * Modo headless (sin interfaz gráfica)
     */
    public const HEADLESS = false;
    
    /**
     * Ruta para reportes de TestLink
     */
    public const TESTLINK_REPORT_PATH = __DIR__ . '/../reports/testlink/';
    
    /**
     * Credenciales de prueba para diferentes roles
     */
    public const TEST_CREDENTIALS = [
        'cliente' => [
            'tipo_documento' => 'V',
            'cedula' => '12345678',
            'clave' => 'cliente123'
        ],
        'asesora' => [
            'tipo_documento' => 'V',
            'cedula' => '87654321',
            'clave' => 'asesora123'
        ],
        'admin' => [
            'tipo_documento' => 'V',
            'cedula' => '11223344',
            'clave' => 'admin123'
        ]
    ];
    
    /**
     * Obtener opciones del navegador Chrome
     */
    public static function getChromeOptions(): array
    {
        $options = [
            '--start-maximized',
            '--disable-infobars',
            '--disable-extensions',
            '--no-sandbox',
            '--disable-dev-shm-usage'
        ];
        
        if (self::HEADLESS) {
            $options[] = '--headless';
        }
        
        return $options;
    }
    
    /**
     * Obtener opciones del navegador Firefox
     */
    public static function getFirefoxOptions(): array
    {
        $options = [];
        
        if (self::HEADLESS) {
            $options['moz:firefoxOptions'] = ['args' => ['-headless']];
        }
        
        return $options;
    }
    
    /**
     * Obtener opciones del navegador Microsoft Edge
     */
    public static function getEdgeOptions(): array
    {
        $options = [
            '--start-maximized',
            '--disable-infobars',
            '--disable-extensions',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ];
        
        if (self::HEADLESS) {
            $options[] = '--headless';
        }
        
        return $options;
    }
}

