<?php

namespace Tests\ConfigSelenium;

/**
 * Configuración para integración con TestLink
 */
class TestLinkConfig
{
  
    public const API_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
    
    /**
     * API Key de TestLink
     */
    public const API_KEY = '1a4d579d37e9a7f66a417c527ca09718';
    
    /**
     * ID del Plan de Pruebas en TestLink
     */
    public const TEST_PLAN_ID = '104';
    
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
    public const AUTO_SEND_TO_TESTLINK = true;
    
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

