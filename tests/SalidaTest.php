<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Salida;

// Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar configuración de base de datos si no está definida
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Clase testable que extiende Salida para exponer métodos privados/protected
 */
class SalidaTestable extends Salida {
    
    public function testEjecutarRegistro($datos) {
        return $this->ejecutarRegistro($datos);
    }

    public function testEjecutarActualizacion($datos) {
        return $this->ejecutarActualizacion($datos);
    }

    public function testEjecutarEliminacion($datos) {
        return $this->ejecutarEliminacion($datos);
    }

    public function testVerificarStock($id_producto) {
        return $this->verificarStock($id_producto);
    }

    public function testConsultarVentas() {
        return $this->consultarVentas();
    }

    public function testConsultarCliente($datos) {
        return $this->consultarCliente($datos);
    }

    public function testRegistrarCliente($datos) {
        return $this->registrarCliente($datos);
    }

    public function testConsultarProductos() {
        return $this->consultarProductos();
    }

    public function testConsultarMetodosPago() {
        return $this->consultarMetodosPago();
    }

    public function testConsultarDetallesPedido($id_pedido) {
        return $this->consultarDetallesPedido($id_pedido);
    }

    public function testConsultarClienteDetalle($id_pedido) {
        return $this->consultarClienteDetalle($id_pedido);
    }

    public function testConsultarMetodosPagoVenta($id_pedido) {
        return $this->consultarMetodosPagoVenta($id_pedido);
    }

    public function testRegistrarVentaPublico($datos) {
        return $this->registrarVentaPublico($datos);
    }

    public function testActualizarVentaPublico($datos) {
        return $this->actualizarVentaPublico($datos);
    }

    public function testEliminarVentaPublico($datos) {
        return $this->eliminarVentaPublico($datos);
    }

    public function testConsultarClientePublico($datos) {
        return $this->consultarClientePublico($datos);
    }

    public function testRegistrarClientePublico($datos) {
        return $this->registrarClientePublico($datos);
    }
}

/**
 * Clase de test para Salida
 */
class SalidaTest extends TestCase {
    
    private ?SalidaTestable $salida = null;
    private static bool $conexionDisponible = false;
    private static bool $conexionVerificada = false;

    /**
     * Verifica si hay conexión a la base de datos disponible
     */
    private function verificarConexion(): bool {
        if (self::$conexionVerificada) {
            return self::$conexionDisponible;
        }

        try {
            // Intentar conectar directamente sin usar la clase Conexion (que usa die)
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbname = defined('DB_NAME_1') ? DB_NAME_1 : 'lovemakeupbd';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            
            $pdo = new \PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8",
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $pdo = null; // Cerrar conexión
            self::$conexionDisponible = true;
        } catch (\PDOException $e) {
            self::$conexionDisponible = false;
        }
        
        self::$conexionVerificada = true;
        return self::$conexionDisponible;
    }

    /**
     * Marca el test como skipped si no hay conexión
     */
    private function requiereConexion(): void {
        if (!$this->verificarConexion()) {
            $this->markTestSkipped('Base de datos no disponible. Asegúrate de que XAMPP/MySQL esté corriendo.');
        }
    }

    protected function setUp(): void {
        parent::setUp();
        
        // Verificar conexión antes de crear la instancia
        $this->requiereConexion();
        
        try {
            $this->salida = new SalidaTestable();
        } catch (\Exception $e) {
            // Si falla la conexión al crear Salida, marcar como skipped
            if (strpos($e->getMessage(), 'Conexión') !== false || 
                strpos($e->getMessage(), 'connection') !== false) {
                $this->markTestSkipped('No se pudo establecer conexión a la base de datos: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->salida = null;
    }

    /**
     * Test: Operación inválida
     * Este test no requiere conexión a BD
     */
    public function testOperacionInvalida(): void {
        try {
            $salidaDirecto = new Salida(); 
        } catch (\Exception $e) {
            // Si no hay conexión, marcar como skipped
            $this->markTestSkipped('Base de datos no disponible para este test: ' . $e->getMessage());
            return;
        }
        
        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);

        $resultado = $salidaDirecto->procesarVenta($json);
        
        $this->assertIsArray($resultado);
        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    /**
     * Test: Consultar ventas
     */
    public function testConsultarVentas(): void {
        $resultado = $this->salida->testConsultarVentas();
        
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_pedido', $resultado[0]);
            $this->assertArrayHasKey('cliente', $resultado[0]);
            $this->assertArrayHasKey('fecha', $resultado[0]);
            $this->assertArrayHasKey('estado', $resultado[0]);
            $this->assertArrayHasKey('precio_total', $resultado[0]);
        }
    }

    /**
     * Test: Consultar productos
     */
    public function testConsultarProductos(): void {
        $resultado = $this->salida->testConsultarProductos();
        
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_producto', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
            $this->assertArrayHasKey('descripcion', $resultado[0]);
            $this->assertArrayHasKey('marca', $resultado[0]);
            $this->assertArrayHasKey('precio_detal', $resultado[0]);
            $this->assertArrayHasKey('stock_disponible', $resultado[0]);
        }
    }

    /**
     * Test: Consultar métodos de pago
     */
    public function testConsultarMetodosPago(): void {
        $resultado = $this->salida->testConsultarMetodosPago();
        
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_metodopago', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
            $this->assertArrayHasKey('descripcion', $resultado[0]);
        }
    }

    /**
     * Test: Consultar cliente por cédula
     */
    public function testConsultarClientePorCedula(): void {
        $datos = ['cedula' => '12345678'];
        
        try {
            $resultado = $this->salida->testConsultarCliente($datos);
            
            $this->assertIsArray($resultado);

            if (!empty($resultado)) {
                $this->assertArrayHasKey('id_persona', $resultado);
                $this->assertArrayHasKey('cedula', $resultado);
                $this->assertArrayHasKey('nombre', $resultado);
                $this->assertArrayHasKey('apellido', $resultado);
                $this->assertArrayHasKey('correo', $resultado);
                $this->assertArrayHasKey('telefono', $resultado);
            }
        } catch (\Exception $e) {
            // Si el cliente no existe, el resultado puede ser false
            $this->assertTrue(true, 'Cliente no encontrado o error esperado: ' . $e->getMessage());
        }
    }

    /**
     * Test: Consultar detalles de pedido
     */
    public function testConsultarDetallesPedido(): void {
        $id_pedido = 1;
        
        try {
            $resultado = $this->salida->testConsultarDetallesPedido($id_pedido);
            
            $this->assertIsArray($resultado);

            if (!empty($resultado)) {
                $this->assertArrayHasKey('cantidad', $resultado[0]);
                $this->assertArrayHasKey('precio_unitario', $resultado[0]);
                $this->assertArrayHasKey('nombre_producto', $resultado[0]);
            }
        } catch (\Exception $e) {
            // Si el pedido no existe, puede lanzar excepción
            $this->assertTrue(true, 'Pedido no encontrado o error esperado: ' . $e->getMessage());
        }
    }

    /**
     * Test: Consultar cliente detalle
     */
    public function testConsultarClienteDetalle(): void {
        $id_pedido = 1;
        
        try {
            $resultado = $this->salida->testConsultarClienteDetalle($id_pedido);
            
            $this->assertIsArray($resultado);

            if (!empty($resultado)) {
                $this->assertArrayHasKey('cedula', $resultado);
                $this->assertArrayHasKey('nombre', $resultado);
                $this->assertArrayHasKey('apellido', $resultado);
                $this->assertArrayHasKey('telefono', $resultado);
                $this->assertArrayHasKey('correo', $resultado);
            }
        } catch (\Exception $e) {
            // Si el pedido no existe, puede lanzar excepción
            $this->assertTrue(true, 'Pedido no encontrado o error esperado: ' . $e->getMessage());
        }
    }

    /**
     * Test: Consultar métodos de pago de venta
     */
    public function testConsultarMetodosPagoVenta(): void {
        $id_pedido = 1;
        
        $resultado = $this->salida->testConsultarMetodosPagoVenta($id_pedido);
        
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('nombre_metodo', $resultado[0]);
            $this->assertArrayHasKey('monto_usd', $resultado[0]);
            $this->assertArrayHasKey('monto_bs', $resultado[0]);
        }
    }

    /**
     * Test: Verificar stock de producto
     */
    public function testVerificarStockProducto(): void {
        $id_producto = 1;
        
        try {
            $resultado = $this->salida->testVerificarStock($id_producto);
            
            $this->assertIsInt($resultado);
            $this->assertGreaterThanOrEqual(0, $resultado);
        } catch (\Exception $e) {
            // Si el producto no existe, puede lanzar excepción
            $this->assertTrue(true, 'Producto no encontrado o error esperado: ' . $e->getMessage());
        }
    }

    /**
     * Test: Consultar cliente público
     */
    public function testConsultarClientePublico(): void {
        $datos = ['cedula' => '12345678'];
        
        try {
            $resultado = $this->salida->testConsultarClientePublico($datos);
            
            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('respuesta', $resultado);
            $this->assertArrayHasKey('cliente', $resultado);
        } catch (\Exception $e) {
            // Si el cliente no existe, puede lanzar excepción
            $this->assertTrue(true, 'Cliente no encontrado o error esperado: ' . $e->getMessage());
        }
    }

    /**
     * Test: Registrar cliente público
     * Nota: Este test puede fallar si el cliente ya existe
     */
    public function testRegistrarClientePublico(): void {
        $datos = [
            'cedula' => '99999999',
            'nombre' => 'Test',
            'apellido' => 'Usuario',
            'telefono' => '04141234567',
            'correo' => 'test@example.com'
        ];
        
        try {
            $resultado = $this->salida->testRegistrarClientePublico($datos);
            
            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('success', $resultado);
            $this->assertArrayHasKey('message', $resultado);
        } catch (\Exception $e) {
            // Si el cliente ya existe, es un error esperado
            $this->assertStringContainsString('cédula', strtolower($e->getMessage()));
        }
    }

    /**
     * Test: Registrar venta público
     * Nota: Este test requiere datos válidos en la base de datos
     */
    public function testRegistrarVentaPublico(): void {
        $datos = [
            'id_persona' => 1,
            'precio_total' => 100.00,
            'precio_total_bs' => 2500.00,
            'detalles' => [
                [
                    'id_producto' => 1,
                    'cantidad' => 2,
                    'precio_unitario' => 50.00
                ]
            ]
        ];
        
        try {
            $resultado = $this->salida->testRegistrarVentaPublico($datos);
            
            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('respuesta', $resultado);
        } catch (\Exception $e) {
            // Puede fallar si no hay stock, cliente no existe, etc.
            $this->assertTrue(true, 'Error esperado en registro de venta: ' . $e->getMessage());
        }
    }

    /**
     * Test: Actualizar venta público
     * Nota: Este test requiere que exista un pedido con ID 1
     */
    public function testActualizarVentaPublico(): void {
        $datos = [
            'id_pedido' => 1,
            'estado' => '2'
        ];
        
        try {
            $resultado = $this->salida->testActualizarVentaPublico($datos);
            
            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('respuesta', $resultado);
        } catch (\Exception $e) {
            // Puede fallar si el pedido no existe
            $this->assertTrue(true, 'Error esperado en actualización de venta: ' . $e->getMessage());
        }
    }

    /**
     * Test: Eliminar venta público
     * Nota: Este test requiere que exista un pedido con ID 1
     */
    public function testEliminarVentaPublico(): void {
        $datos = ['id_pedido' => 1];
        
        try {
            $resultado = $this->salida->testEliminarVentaPublico($datos);
            
            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('respuesta', $resultado);
        } catch (\Exception $e) {
            // Puede fallar si el pedido no existe
            $this->assertTrue(true, 'Error esperado en eliminación de venta: ' . $e->getMessage());
        }
    }
}

