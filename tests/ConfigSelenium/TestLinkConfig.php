<?php

namespace Tests\ConfigSelenium;

/**
 * Configuración para integración con TestLink
 */
class TestLinkConfig
{
    /**
     * URL de la API de TestLink
     * Ejemplo: 'http://testlink.example.com/lib/api/xmlrpc/v1/xmlrpc.php'
     */
    public const API_URL = '';
    
    /**
     * API Key de TestLink
     */
    public const API_KEY = '';
    
    /**
     * ID del Plan de Pruebas en TestLink
     */
    public const TEST_PLAN_ID = '';
    
    /**
     * Nombre del Build por defecto
     */
    public const DEFAULT_BUILD_NAME = 'Automated Build';
    
    /**
     * Plataforma por defecto
     */
    public const DEFAULT_PLATFORM = 'Web';
    
    /**
     * Habilitar envío automático a TestLink
     */
    public const AUTO_SEND_TO_TESTLINK = false;
    
    /**
     * Verificar configuración
     */
    public static function isConfigured(): bool
    {
        return !empty(self::API_URL) && !empty(self::API_KEY);
    }
    
    /**
     * Obtener configuración para conexión a TestLink
     */
    public static function getConfig(): array
    {
        return [
            'api_url' => self::API_URL,
            'api_key' => self::API_KEY,
            'test_plan_id' => self::TEST_PLAN_ID,
            'default_build_name' => self::DEFAULT_BUILD_NAME,
            'default_platform' => self::DEFAULT_PLATFORM
        ];
    }
}

