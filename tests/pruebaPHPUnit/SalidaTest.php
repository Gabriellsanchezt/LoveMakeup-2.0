<?php

namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Salida;
use ReflectionClass;

/*|||||||||||||||||||||||||| CLASE TESTABLE CON REFLEXIÓN  |||||||||||||||||||||| */
class SalidaTestable {
    private $salida;
    private $reflection;
    
    public function __construct() {
        $this->salida = new Salida();
        $this->reflection = new ReflectionClass($this->salida);
    }
    
    private function invokePrivateMethod($methodName, $args = []) {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->salida, $args);
    }
    
    public function testEjecutarRegistro($datos) {  /*||| 1 ||| */
        return $this->invokePrivateMethod('ejecutarRegistro', [$datos]);
    }

    public function testEjecutarActualizacion($datos) {  /*||| 2 ||| */
        return $this->invokePrivateMethod('ejecutarActualizacion', [$datos]);
    }

    public function testEjecutarEliminacion($datos) {  /*||| 3 ||| */
        return $this->invokePrivateMethod('ejecutarEliminacion', [$datos]);
    }

    public function testVerificarStock($id_producto) {  /*||| 4 ||| */
        return $this->invokePrivateMethod('verificarStock', [$id_producto]);
    }

    public function testConsultarVentas() {  /*||| 5 ||| */
        return $this->salida->consultarVentas();
    }

    public function testConsultarCliente($datos) {  /*||| 6 ||| */
        return $this->salida->consultarCliente($datos);
    }

    public function testRegistrarCliente($datos) {  /*||| 7 ||| */
        return $this->salida->registrarCliente($datos);
    }

    public function testConsultarProductos() {  /*||| 8 ||| */
        return $this->salida->consultarProductos();
    }

    public function testConsultarMetodosPago() {  /*||| 9 ||| */
        return $this->salida->consultarMetodosPago();
    }

    public function testConsultarDetallesPedido($id_pedido) {  /*||| 10 ||| */
        return $this->salida->consultarDetallesPedido($id_pedido);
    }

    public function testConsultarClienteDetalle($id_pedido) {  /*||| 11 ||| */
        return $this->salida->consultarClienteDetalle($id_pedido);
    }

    public function testConsultarMetodosPagoVenta($id_pedido) {  /*||| 12 ||| */
        return $this->salida->consultarMetodosPagoVenta($id_pedido);
    }

    public function testRegistrarVentaPublico($datos) {  /*||| 13 ||| */
        return $this->salida->registrarVentaPublico($datos);
    }

    public function testActualizarVentaPublico($datos) {  /*||| 14 ||| */
        return $this->salida->actualizarVentaPublico($datos);
    }

    public function testEliminarVentaPublico($datos) {  /*||| 15 ||| */
        return $this->salida->eliminarVentaPublico($datos);
    }

    public function testConsultarClientePublico($datos) {  /*||| 16 ||| */
        return $this->salida->consultarClientePublico($datos);
    }

    public function testRegistrarClientePublico($datos) {  /*||| 17 ||| */
        return $this->salida->registrarClientePublico($datos);
    }
    
    public function getSalida() {
        return $this->salida;
    }
}

/*||||||||||||||||||||||||||||||| CLASE DE TEST  |||||||||||||||||||||||||||||| */
class SalidaTest extends TestCase {
    private SalidaTestable $salida;
    private $idProductoDisponible = null;
    private $idPedidoDisponible = null;
    private $idUsuarioDisponible = null;
    private $cedulaClienteDisponible = null;
    private $originalErrorLog = null;
    private $errorLogFile = null;

    protected function setUp(): void {
        // Silenciar error_log temporalmente para no contaminar la salida de tests
        // Guardar configuración original
        $this->originalErrorLog = [
            'log_errors' => ini_get('log_errors'),
            'error_log' => ini_get('error_log'),
            'display_errors' => ini_get('display_errors')
        ];
        
        // Crear un archivo temporal para redirigir los error_log
        // En Windows usamos 'nul', en Linux '/dev/null'
        if (PHP_OS_FAMILY === 'Windows') {
            $this->errorLogFile = 'nul';
        } else {
            $this->errorLogFile = '/dev/null';
        }
        
        // Redirigir error_log a un archivo que descarta todo
        ini_set('log_errors', '1');
        ini_set('error_log', $this->errorLogFile);
        ini_set('display_errors', '0');
        
        $this->salida = new SalidaTestable();
        // Obtener datos reales de la base de datos
        $this->obtenerDatosDisponibles();
    }
    
    protected function tearDown(): void {
        // Restaurar la configuración original de error_log
        if ($this->originalErrorLog !== null) {
            ini_set('log_errors', $this->originalErrorLog['log_errors']);
            ini_set('error_log', $this->originalErrorLog['error_log']);
            ini_set('display_errors', $this->originalErrorLog['display_errors']);
        }
    }
    
    private function obtenerDatosDisponibles(): void {
        // Obtener un producto disponible con stock
        $productos = $this->salida->testConsultarProductos();
        if (!empty($productos) && isset($productos[0]['id_producto'])) {
            $this->idProductoDisponible = $productos[0]['id_producto'];
        }
        
        // Obtener un pedido disponible y extraer datos del cliente
        $ventas = $this->salida->testConsultarVentas();
        if (!empty($ventas) && isset($ventas[0]['id_pedido'])) {
            $this->idPedidoDisponible = $ventas[0]['id_pedido'];
            
            // Extraer datos del cliente si están disponibles en la venta
            if (isset($ventas[0]['id_usuario'])) {
                $this->idUsuarioDisponible = $ventas[0]['id_usuario'];
            }
            if (isset($ventas[0]['cedula'])) {
                $this->cedulaClienteDisponible = (string)$ventas[0]['cedula'];
            }
        }
    }

    public function testOperacionInvalida() { /*|||||| OPERACIONES |||| 1 || */
        $salidaDirecto = new \LoveMakeup\Proyecto\Modelo\Salida(); 
        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);

        $resultado = $salidaDirecto->procesarVenta($json);
        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    public function testConsultarVentas() { /*|||||| CONSULTAR VENTAS ||||| 2 | */
        $resultado = $this->salida->testConsultarVentas();
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_pedido', $resultado[0]);
            $this->assertArrayHasKey('cliente', $resultado[0]);
            $this->assertArrayHasKey('fecha', $resultado[0]);
            $this->assertArrayHasKey('estado', $resultado[0]);
            $this->assertArrayHasKey('precio_total_usd', $resultado[0]);
            $this->assertArrayHasKey('precio_total_bs', $resultado[0]);
        }
    }

    public function testConsultarProductos() { /*|||||| CONSULTAR PRODUCTOS ||||| 3 | */
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

    public function testConsultarMetodosPago() { /*|||||| CONSULTAR MÉTODOS DE PAGO ||||| 4 | */
        $resultado = $this->salida->testConsultarMetodosPago();
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_metodopago', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
            $this->assertArrayHasKey('descripcion', $resultado[0]);
        }
    }

    public function testConsultarClientePorCedula() { /*|||||| CONSULTAR CLIENTE ||||| 5 | */
        if ($this->cedulaClienteDisponible === null) {
            // Intentar con una cédula común o buscar en ventas
            $ventas = $this->salida->testConsultarVentas();
            if (!empty($ventas) && isset($ventas[0]['cedula'])) {
                $this->cedulaClienteDisponible = (string)$ventas[0]['cedula'];
            }
        }
        
        if ($this->cedulaClienteDisponible === null) {
            $this->fail('ERROR: No hay clientes disponibles en la base de datos. Este test requiere al menos un cliente con cédula registrada para consultar sus datos.');
        }
        
        $datos = ['cedula' => $this->cedulaClienteDisponible];
        $resultado = $this->salida->testConsultarCliente($datos);
        
        if ($resultado === false) {
            $this->fail('ERROR: El cliente con cédula ' . $this->cedulaClienteDisponible . ' no fue encontrado en la base de datos o está inactivo.');
        }
        
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_persona', $resultado);
            $this->assertArrayHasKey('cedula', $resultado);
            $this->assertArrayHasKey('nombre', $resultado);
            $this->assertArrayHasKey('apellido', $resultado);
            $this->assertArrayHasKey('correo', $resultado);
            $this->assertArrayHasKey('telefono', $resultado);
        }
    }

    public function testConsultarDetallesPedido() { /*|||||| CONSULTAR DETALLES PEDIDO ||||| 6 | */
        if ($this->idPedidoDisponible === null) {
            $this->fail('ERROR: No hay pedidos disponibles en la base de datos. Este test requiere al menos un pedido existente para consultar sus detalles.');
        }
        
        $resultado = $this->salida->testConsultarDetallesPedido($this->idPedidoDisponible);
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('cantidad', $resultado[0]);
            $this->assertArrayHasKey('precio_unitario', $resultado[0]);
            $this->assertArrayHasKey('nombre_producto', $resultado[0]);
        }
    }

    public function testConsultarClienteDetalle() { /*|||||| CONSULTAR CLIENTE DETALLE ||||| 7 | */
        if ($this->idPedidoDisponible === null) {
            $this->fail('ERROR: No hay pedidos disponibles en la base de datos. Este test requiere al menos un pedido existente para consultar los datos del cliente.');
        }
        
        $resultado = $this->salida->testConsultarClienteDetalle($this->idPedidoDisponible);
        
        if ($resultado === null) {
            $this->fail('ERROR: El pedido ID ' . $this->idPedidoDisponible . ' o su cliente asociado no fue encontrado en la base de datos.');
        }
        
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('cedula', $resultado);
            $this->assertArrayHasKey('nombre', $resultado);
            $this->assertArrayHasKey('apellido', $resultado);
            $this->assertArrayHasKey('telefono', $resultado);
            $this->assertArrayHasKey('correo', $resultado);
        }
    }

    public function testConsultarMetodosPagoVenta() { /*|||||| CONSULTAR MÉTODOS PAGO VENTA ||||| 8 | */
        if ($this->idPedidoDisponible === null) {
            $this->fail('ERROR: No hay pedidos disponibles en la base de datos. Este test requiere al menos un pedido existente para consultar sus métodos de pago.');
        }
        
        $resultado = $this->salida->testConsultarMetodosPagoVenta($this->idPedidoDisponible);
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('nombre_metodo', $resultado[0]);
            $this->assertArrayHasKey('monto_usd', $resultado[0]);
            $this->assertArrayHasKey('monto_bs', $resultado[0]);
        }
    }

    public function testVerificarStockProducto() { /*|||||| VERIFICAR STOCK ||||| 9 | */
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo con stock para verificar su disponibilidad.');
        }
        
        $resultado = $this->salida->testVerificarStock($this->idProductoDisponible);
        $this->assertIsInt($resultado);
        $this->assertGreaterThanOrEqual(0, $resultado);
    }

    public function testConsultarClientePublico() { /*|||||| CONSULTAR CLIENTE PÚBLICO ||||| 10 | */
        if ($this->cedulaClienteDisponible === null) {
            // Intentar obtener cédula de ventas
            $ventas = $this->salida->testConsultarVentas();
            if (!empty($ventas) && isset($ventas[0]['cedula'])) {
                $this->cedulaClienteDisponible = (string)$ventas[0]['cedula'];
            }
        }
        
        if ($this->cedulaClienteDisponible === null) {
            $this->fail('ERROR: No hay clientes disponibles en la base de datos. Este test requiere al menos un cliente con cédula registrada para consultar mediante consultarClientePublico().');
        }
        
        $datos = ['cedula' => $this->cedulaClienteDisponible];
        $resultado = $this->salida->testConsultarClientePublico($datos);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('respuesta', $resultado);
        $this->assertArrayHasKey('cliente', $resultado);
    }

    public function testRegistrarClientePublico() { /*|||||| REGISTRAR CLIENTE PÚBLICO ||||| 11 | */
        // Generar cédula única para evitar conflictos
        $cedulaUnica = '1' . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        
        $datos = [
            'cedula' => $cedulaUnica,
            'nombre' => 'Test',
            'apellido' => 'Usuario',
            'telefono' => '04141234567',
            'correo' => 'test' . time() . '@example.com'
        ];
        $resultado = $this->salida->testRegistrarClientePublico($datos);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);
        $this->assertArrayHasKey('message', $resultado);
    }

    public function testRegistrarVentaPublico() { /*|||||| REGISTRAR VENTA PÚBLICO ||||| 12 | */
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo con stock disponible para registrar una venta.');
        }
        
        if ($this->idUsuarioDisponible === null) {
            // Intentar obtener un usuario de las ventas existentes
            $ventas = $this->salida->testConsultarVentas();
            if (!empty($ventas) && isset($ventas[0]['id_usuario'])) {
                $this->idUsuarioDisponible = $ventas[0]['id_usuario'];
            } else {
                $this->fail('ERROR: No hay usuarios/clientes disponibles en la base de datos. Este test requiere al menos un usuario activo (estatus = 1) para registrar una venta.');
            }
        }
        
        // Verificar stock antes de registrar
        $stock = $this->salida->testVerificarStock($this->idProductoDisponible);
        if ($stock < 1) {
            $this->fail('ERROR: El producto ID ' . $this->idProductoDisponible . ' no tiene stock suficiente (stock actual: ' . $stock . '). Este test requiere al menos 1 unidad disponible.');
        }
        
        $datos = [
            'id_persona' => $this->idUsuarioDisponible,
            'precio_total' => 100.00,
            'precio_total_bs' => 2500.00,
            'detalles' => [
                [
                    'id_producto' => $this->idProductoDisponible,
                    'cantidad' => 1,
                    'precio_unitario' => 100.00
                ]
            ]
        ];
        $resultado = $this->salida->testRegistrarVentaPublico($datos);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('respuesta', $resultado);
    }

    public function testActualizarVentaPublico() { /*|||||| ACTUALIZAR VENTA PÚBLICO ||||| 13 | */
        if ($this->idPedidoDisponible === null) {
            $this->fail('ERROR: No hay pedidos disponibles en la base de datos. Este test requiere al menos un pedido existente para actualizar su estado.');
        }
        
        $datos = [
            'id_pedido' => $this->idPedidoDisponible,
            'estado' => '2'
        ];
        $resultado = $this->salida->testActualizarVentaPublico($datos);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('respuesta', $resultado);
    }

    public function testEliminarVentaPublico() { /*|||||| ELIMINAR VENTA PÚBLICO ||||| 14 | */
        if ($this->idPedidoDisponible === null) {
            $this->fail('ERROR: No hay pedidos disponibles en la base de datos. Este test requiere al menos un pedido existente para eliminarlo.');
        }
        
        $datos = ['id_pedido' => $this->idPedidoDisponible];
        $resultado = $this->salida->testEliminarVentaPublico($datos);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('respuesta', $resultado);
        
        // Limpiar la referencia ya que el pedido fue eliminado
        $this->idPedidoDisponible = null;
    }
}
