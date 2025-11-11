
## Resumen General

Este documento explica los cambios realizados para incorporar **Composer** al sistema de gestión de LoveMakeup. Composer es un gestor de dependencias para PHP que permite organizar mejor el código mediante el uso de **namespaces** y **autoloading automático**.

---

## ¿Qué es Composer y por qué se incorporó?

**Composer** es una herramienta que:
- Gestiona las dependencias del proyecto
- Permite usar **autoloading** automático de clases
- Organiza el código mediante **namespaces** (espacios de nombres)
- Facilita la carga automática de clases sin necesidad de usar múltiples `require` o `require_once`

---

## Cambios Realizados

### 1. Creación del archivo `composer.json`

**Ubicación:** `Proyecto-III/composer.json`

Este archivo es el corazón de Composer. Define la configuración del proyecto y cómo se cargarán automáticamente las clases.

**Contenido principal:**
```json
{
    "name": "lovemakeup/proyecto",
    "description": "PLATAFORMA DE COMERCIO ELECTRONICO...",
    "type": "project",
    "autoload": {
        "psr-4": {
            "LoveMakeup\\Proyecto\\Modelo\\": "modelo/",
            "LoveMakeup\\Proyecto\\Controlador\\": "controlador/",
            "LoveMakeup\\Proyecto\\Config\\": "config/"
        }
    },
    "require": {
        "dompdf/dompdf": "^2.0",
        "phpmailer/phpmailer": "^6.9"
    },
    "require-dev": {
        "phpunit/phpunit": "*"
    }
}
```

**¿Qué significa esto?**
- **PSR-4**: Es un estándar de autoloading que mapea namespaces a directorios
- **LoveMakeup\\Proyecto\\Modelo\\** → Se mapea a la carpeta `modelo/`
- **LoveMakeup\\Proyecto\\Controlador\\** → Se mapea a la carpeta `controlador/`
- **LoveMakeup\\Proyecto\\Config\\** → Se mapea a la carpeta `config/`

---

### 2. Creación del Autoloader (`vendor/autoload.php`)

**Ubicación:** `Proyecto-III/vendor/autoload.php`

Este archivo carga automáticamente las clases cuando se necesitan, sin tener que hacer `require` manual de cada archivo.

**Funcionalidad:**
- Registra un autoloader que busca clases en los namespaces configurados
- Cuando se usa una clase como `LoveMakeup\Proyecto\Modelo\Salida`, automáticamente busca el archivo en `modelo/salida.php`
- Elimina la necesidad de múltiples `require_once` en cada archivo

---

### 3. Integración en el punto de entrada (`index.php`)

**Ubicación:** `Proyecto-III/index.php`

**Cambio realizado:**
```php
<?php
    require __DIR__ . '/vendor/autoload.php';  // ← NUEVA LÍNEA
    
    $pagina = "catalogo";
    // ... resto del código
```

**¿Por qué es importante?**
- Esta línea carga el autoloader al inicio de la aplicación
- Permite que todas las clases del proyecto se carguen automáticamente
- Debe estar al principio del archivo para que funcione en todo el sistema

---

### 4. Implementación de Namespaces en las Clases

#### 4.1. Clases de Configuración

**Ejemplo:** `config/conexion.php`

**Antes (sin namespace):**
```php
<?php
class Conexion {
    // código...
}
```

**Después (con namespace):**
```php
<?php

namespace LoveMakeup\Proyecto\Config;

class Conexion {
    // código...
}
```

#### 4.2. Clases del Modelo

**Ejemplo:** `modelo/salida.php`

**Cambios realizados:**
```php
<?php

namespace LoveMakeup\Proyecto\Modelo;

require_once(__DIR__ . '/../config/conexion.php');
use LoveMakeup\Proyecto\Config\Conexion;  // ← NUEVO: Importa la clase

class Salida extends Conexion {
    // código...
}
```

**¿Qué cambió?**
- Se agregó `namespace LoveMakeup\Proyecto\Modelo;` al inicio
- Se agregó `use LoveMakeup\Proyecto\Config\Conexion;` para importar la clase de conexión
- Ahora la clase se identifica completamente por su namespace

---

### 5. Integración de PHPUnit para Testing

**PHPUnit** es un framework de pruebas unitarias para PHP que permite verificar que el código funciona correctamente mediante la ejecución de tests automatizados.

#### 5.1. Configuración en `composer.json`

PHPUnit se ha añadido como dependencia de desarrollo en la sección `require-dev`:

```json
"require-dev": {
    "phpunit/phpunit": "*"
}
```

**¿Por qué en `require-dev`?**
- Las herramientas de testing solo se necesitan durante el desarrollo
- No se instalan en producción, reduciendo el tamaño del proyecto en servidores
- Permite mantener el código de producción más limpio

#### 5.2. Instalación de PHPUnit

Para instalar PHPUnit y sus dependencias, ejecutar:

```bash
composer update
```

O específicamente para dependencias de desarrollo:

```bash
composer install --dev
```

#### 5.3. Uso de PHPUnit

Una vez instalado, PHPUnit estará disponible en:

```
vendor/bin/phpunit
```

**Ejemplo de ejecución de tests:**

```bash
# Ejecutar todos los tests
vendor/bin/phpunit

# Ejecutar tests de un directorio específico
vendor/bin/phpunit tests/

# Ejecutar un archivo de test específico
vendor/bin/phpunit tests/ModeloTest.php
```

#### 5.4. Estructura de Tests

Se ha creado la carpeta `tests/` en la raíz del proyecto para organizar los archivos de prueba. La estructura recomendada es:

```
Proyecto-III/
├── tests/
│   ├── Modelo/
│   │   └── SalidaTest.php
│   ├── Controlador/
│   └── Config/
│       └── ConexionTest.php
├── composer.json
└── ...
```

**Nota:** La carpeta `tests/` ya está creada y lista para agregar los archivos de prueba.

**Ejemplo de test básico:**

```php
<?php

namespace Tests\Modelo;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Salida;

class SalidaTest extends TestCase
{
    public function testSalidaExiste()
    {
        $salida = new Salida();
        $this->assertInstanceOf(Salida::class, $salida);
    }
}
```

#### 5.5. Ventajas de PHPUnit

- **Detección temprana de errores**: Los tests ayudan a encontrar problemas antes de que lleguen a producción
- **Documentación viva**: Los tests sirven como documentación de cómo debe funcionar el código
- **Refactorización segura**: Permite modificar código con confianza sabiendo que los tests detectarán errores
- **Mejora de calidad**: Fomenta escribir código más modular y testeable

---

## Ventajas de estos Cambios

### 1. **Organización del Código**
- Cada clase tiene un nombre único y completo
- Evita conflictos de nombres entre clases
- Facilita la identificación de dónde está cada clase

### 2. **Autoloading Automático**
- Ya no es necesario hacer múltiples `require_once`
- Las clases se cargan automáticamente cuando se necesitan
- Reduce errores por archivos no incluidos

### 3. **Mantenibilidad**
- Código más limpio y profesional
- Facilita la colaboración en equipo
- Sigue estándares de la industria (PSR-4)

### 4. **Escalabilidad**
- Fácil agregar nuevas dependencias externas
- Preparado para usar librerías de terceros
- Estructura lista para crecer

### 5. **Testing y Calidad**
- PHPUnit integrado para pruebas unitarias
- Permite verificar el correcto funcionamiento del código
- Facilita la detección temprana de errores

---

## Cómo Usar las Clases Ahora

### Antes (sin Composer):
```php
require_once('config/conexion.php');
require_once('modelo/salida.php');

$salida = new Salida();
```

### Después (con Composer):
```php
require __DIR__ . '/vendor/autoload.php';

use LoveMakeup\Proyecto\Modelo\Salida;

$salida = new Salida();
```

**O también:**
```php
require __DIR__ . '/vendor/autoload.php';

$salida = new \LoveMakeup\Proyecto\Modelo\Salida();
```

---

## Archivos Modificados

### Archivos Nuevos Creados:
1. `composer.json` - Configuración de Composer
2. `vendor/autoload.php` - Autoloader personalizado
3. `tests/` - Carpeta para archivos de pruebas unitarias con PHPUnit

### Archivos Modificados:
1. `index.php` - Agregado `require vendor/autoload.php`
2. `config/conexion.php` - Agregado namespace
3. `modelo/salida.php` - Agregado namespace y use statements
4. Otros archivos del modelo - Agregados namespaces según corresponda

---

## Próximos Pasos Recomendados

1. **Migrar todas las clases** a usar namespaces
2. **Eliminar `require_once` innecesarios** que ahora se manejan con autoload
3. **Agregar dependencias externas** si es necesario (ej: PHPMailer, librerías de PDF, etc.)
4. **Actualizar controladores** para usar namespaces
5. **Crear tests unitarios** usando PHPUnit para las clases principales del sistema
6. **Configurar un archivo `phpunit.xml`** para personalizar la ejecución de tests

---

## Notas Importantes

- El archivo `vendor/autoload.php` debe incluirse **una sola vez** al inicio de la aplicación
- Los namespaces son **case-sensitive** (sensibles a mayúsculas/minúsculas)
- El uso de `use` statements hace el código más legible
- Si una clase no tiene namespace, se puede usar con `\` al inicio (ej: `\PDO`)

---

## Referencias

- **PSR-4 Autoloading Standard**: https://www.php-fig.org/psr/psr-4/
- **Composer Documentation**: https://getcomposer.org/doc/
- **PHPUnit Documentation**: https://phpunit.de/documentation.html

---

**Fecha de creación:** 2024  
**Versión del documento:** 1.0  
**Proyecto:** LoveMakeup C.A.

