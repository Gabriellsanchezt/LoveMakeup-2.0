# Configuración Selenium y TestLink para Windows

Esta carpeta contiene las clases de configuración y utilidades para pruebas automatizadas con Selenium e integración con TestLink.

## Archivos

- **SeleniumConfig.php**: Configuración de Selenium (URLs, navegador, timeouts, credenciales)
- **TestLinkConfig.php**: Configuración de TestLink (API URL, API Key, Plan de Pruebas)
- **TestLinkReporter.php**: Generador de reportes XML/JSON para TestLink

## Uso

```php
use Tests\ConfigSelenium\SeleniumConfig;
use Tests\ConfigSelenium\TestLinkReporter;

// Usar configuración
$url = SeleniumConfig::BASE_URL;
$browser = SeleniumConfig::BROWSER; // 'edge' por defecto

// Generar reportes
$reporter = new TestLinkReporter();
$reporter->addTestResult('TL-123', 'Mi Prueba', 'p', 'Prueba exitosa');
$reporter->generateXMLReport();
```

## Configuración

Editar `SeleniumConfig.php` para ajustar:
- URL base de la aplicación
- URL del Selenium Server
- Navegador predeterminado (Edge, Chrome, Firefox)
- Credenciales de prueba

Editar `TestLinkConfig.php` para configurar:
- URL de la API de TestLink
- API Key
- ID del Plan de Pruebas

