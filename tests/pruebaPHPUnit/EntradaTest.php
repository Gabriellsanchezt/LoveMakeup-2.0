<?php

namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Entrada;
use ReflectionClass;
use ReflectionMethod;

/*|||||||||||||||||||||||||| CLASE TESTABLE CON REFLEXIÓN  |||||||||||||||||||||| */
class EntradaTestable {
    private $entrada;
    private $reflection;
    
    public function __construct() {
        $this->entrada = new Entrada();
        $this->reflection = new ReflectionClass($this->entrada);
    }
    
    private function invokePrivateMethod($methodName, $args = []) {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->entrada, $args);
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

    public function testEjecutarConsulta() {  /*||| 4 ||| */
        return $this->invokePrivateMethod('ejecutarConsulta');
    }

    public function testEjecutarConsultaDetalles($datos) {  /*||| 5 ||| */
        return $this->invokePrivateMethod('ejecutarConsultaDetalles', [$datos]);
    }

    public function testEjecutarConsultaProductos() {  /*||| 6 ||| */
        return $this->invokePrivateMethod('ejecutarConsultaProductos');
    }

    public function testEjecutarConsultaProveedores() {  /*||| 7 ||| */
        return $this->invokePrivateMethod('ejecutarConsultaProveedores');
    }
    
    public function getEntrada() {
        return $this->entrada;
    }
}

/*||||||||||||||||||||||||||||||| CLASE DE TEST  |||||||||||||||||||||||||||||| */
class EntradaTest extends TestCase {
    private EntradaTestable $entrada;
    private $idProductoDisponible = null;
    private $idProveedorDisponible = null;
    private $idCompraDisponible = null;

    protected function setUp(): void {
        $this->entrada = new EntradaTestable();
        // Obtener datos reales de la base de datos
        $this->obtenerDatosDisponibles();
    }
    
    private function obtenerDatosDisponibles(): void {
        // Obtener un producto disponible
        $productos = $this->entrada->testEjecutarConsultaProductos();
        if (!empty($productos['datos']) && isset($productos['datos'][0]['id_producto'])) {
            $this->idProductoDisponible = $productos['datos'][0]['id_producto'];
        }
        
        // Obtener un proveedor disponible
        $proveedores = $this->entrada->testEjecutarConsultaProveedores();
        if (!empty($proveedores['datos']) && isset($proveedores['datos'][0]['id_proveedor'])) {
            $this->idProveedorDisponible = $proveedores['datos'][0]['id_proveedor'];
        }
        
        // Obtener una compra disponible
        $compras = $this->entrada->testEjecutarConsulta();
        if (!empty($compras['datos']) && isset($compras['datos'][0]['id_compra'])) {
            $this->idCompraDisponible = $compras['datos'][0]['id_compra'];
        }
    }

    public function testOperacionInvalida() { /*|||||| OPERACIONES |||| 1 || */
        $entradaDirecto = new \LoveMakeup\Proyecto\Modelo\Entrada(); 
        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);

        $resultado = $entradaDirecto->procesarCompra($json);
        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    public function testConsultarCompras() { /*|||||| CONSULTAR COMPRAS ||||| 2 | */
        $resultado = $this->entrada->testEjecutarConsulta();
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('datos', $resultado);

        if (!empty($resultado['datos'])) {
            $this->assertArrayHasKey('id_compra', $resultado['datos'][0]);
            $this->assertArrayHasKey('fecha_entrada', $resultado['datos'][0]);
            $this->assertArrayHasKey('proveedor_nombre', $resultado['datos'][0]);
            $this->assertArrayHasKey('proveedor_telefono', $resultado['datos'][0]);
            $this->assertArrayHasKey('id_proveedor', $resultado['datos'][0]);
        }
    }

    public function testConsultarProductos() { /*|||||| CONSULTAR PRODUCTOS ||||| 3 | */
        $resultado = $this->entrada->testEjecutarConsultaProductos();
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('datos', $resultado);

        if (!empty($resultado['datos'])) {
            $this->assertArrayHasKey('id_producto', $resultado['datos'][0]);
            $this->assertArrayHasKey('nombre', $resultado['datos'][0]);
            $this->assertArrayHasKey('marca', $resultado['datos'][0]);
            $this->assertArrayHasKey('stock_disponible', $resultado['datos'][0]);
        }
    }

    public function testConsultarProveedores() { /*|||||| CONSULTAR PROVEEDORES ||||| 4 | */
        $resultado = $this->entrada->testEjecutarConsultaProveedores();
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('datos', $resultado);

        if (!empty($resultado['datos'])) {
            $this->assertArrayHasKey('id_proveedor', $resultado['datos'][0]);
            $this->assertArrayHasKey('nombre', $resultado['datos'][0]);
        }
    }

    public function testConsultarDetallesCompra() { /*|||||| CONSULTAR DETALLES COMPRA ||||| 5 | */
        if ($this->idCompraDisponible === null) {
            $this->fail('ERROR: No hay compras disponibles en la base de datos. Este test requiere al menos una compra existente para consultar sus detalles.');
        }
        
        $datos = ['id_compra' => $this->idCompraDisponible];
        $resultado = $this->entrada->testEjecutarConsultaDetalles($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('datos', $resultado);

        if (!empty($resultado['datos'])) {
            $this->assertArrayHasKey('id_detalle_compra', $resultado['datos'][0]);
            $this->assertArrayHasKey('cantidad', $resultado['datos'][0]);
            $this->assertArrayHasKey('precio_total', $resultado['datos'][0]);
            $this->assertArrayHasKey('precio_unitario', $resultado['datos'][0]);
            $this->assertArrayHasKey('id_producto', $resultado['datos'][0]);
            $this->assertArrayHasKey('producto_nombre', $resultado['datos'][0]);
            $this->assertArrayHasKey('marca', $resultado['datos'][0]);
        }
    }

    public function testRegistrarCompraValida() { /*|||||| REGISTRAR COMPRA VÁLIDA ||||| 6 | */
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo (estatus = 1) para registrar una compra.');
        }
        if ($this->idProveedorDisponible === null) {
            $this->fail('ERROR: No hay proveedores disponibles en la base de datos. Este test requiere al menos un proveedor activo (estatus = 1) para registrar una compra.');
        }
        
        $datos = [
            'fecha_entrada' => date('Y-m-d'),
            'id_proveedor' => $this->idProveedorDisponible,
            'productos' => [
                [
                    'id_producto' => $this->idProductoDisponible,
                    'cantidad' => 10,
                    'precio_unitario' => 25.50,
                    'precio_total' => 255.00
                ]
            ]
        ];

        $resultado = $this->entrada->testEjecutarRegistro($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('Compra registrada exitosamente', $resultado['mensaje']);
        $this->assertArrayHasKey('id_compra', $resultado);
        
        // Guardar el ID de la compra creada para otros tests
        if (isset($resultado['id_compra'])) {
            $this->idCompraDisponible = $resultado['id_compra'];
        }
    }

    public function testRegistrarCompraDatosIncompletos() { /*|||||| REGISTRAR COMPRA DATOS INCOMPLETOS ||||| 7 | */
        if ($this->idProveedorDisponible === null) {
            $this->fail('ERROR: No hay proveedores disponibles en la base de datos. Este test requiere al menos un proveedor activo (estatus = 1) para probar la validación de datos incompletos.');
        }
        
        $datos = [
            'fecha_entrada' => date('Y-m-d'),
            'id_proveedor' => $this->idProveedorDisponible,
            'productos' => [] // Array vacío
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Datos incompletos');
        $this->entrada->testEjecutarRegistro($datos);
    }

    public function testRegistrarCompraSinFecha() { /*|||||| REGISTRAR COMPRA SIN FECHA ||||| 8 | */
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo (estatus = 1) para probar la validación de fecha vacía.');
        }
        if ($this->idProveedorDisponible === null) {
            $this->fail('ERROR: No hay proveedores disponibles en la base de datos. Este test requiere al menos un proveedor activo (estatus = 1) para probar la validación de fecha vacía.');
        }
        
        $datos = [
            'fecha_entrada' => '', // Fecha vacía
            'id_proveedor' => $this->idProveedorDisponible,
            'productos' => [
                [
                    'id_producto' => $this->idProductoDisponible,
                    'cantidad' => 10,
                    'precio_unitario' => 25.50,
                    'precio_total' => 255.00
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Datos incompletos');
        $this->entrada->testEjecutarRegistro($datos);
    }

    public function testRegistrarCompraSinProveedor() { /*|||||| REGISTRAR COMPRA SIN PROVEEDOR ||||| 9 | */
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo (estatus = 1) para probar la validación de proveedor nulo.');
        }
        
        $datos = [
            'fecha_entrada' => date('Y-m-d'),
            'id_proveedor' => null, // Proveedor nulo
            'productos' => [
                [
                    'id_producto' => $this->idProductoDisponible,
                    'cantidad' => 10,
                    'precio_unitario' => 25.50,
                    'precio_total' => 255.00
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Datos incompletos');
        $this->entrada->testEjecutarRegistro($datos);
    }

    public function testRegistrarCompraProductoInexistente() { /*|||||| REGISTRAR COMPRA PRODUCTO INEXISTENTE ||||| 10 | */
        if ($this->idProveedorDisponible === null) {
            $this->fail('ERROR: No hay proveedores disponibles en la base de datos. Este test requiere al menos un proveedor activo (estatus = 1) para probar la validación de producto inexistente.');
        }
        
        $datos = [
            'fecha_entrada' => date('Y-m-d'),
            'id_proveedor' => $this->idProveedorDisponible,
            'productos' => [
                [
                    'id_producto' => 99999, // Producto que no existe
                    'cantidad' => 10,
                    'precio_unitario' => 25.50,
                    'precio_total' => 255.00
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El producto ID 99999 no existe o está inactivo');
        $this->entrada->testEjecutarRegistro($datos);
    }

    public function testActualizarCompraExistente() { /*|||||| ACTUALIZAR COMPRA EXISTENTE ||||| 11 | */
        if ($this->idCompraDisponible === null) {
            $this->fail('ERROR: No hay compras disponibles en la base de datos. Este test requiere al menos una compra existente para actualizarla.');
        }
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo (estatus = 1) para actualizar la compra.');
        }
        if ($this->idProveedorDisponible === null) {
            $this->fail('ERROR: No hay proveedores disponibles en la base de datos. Este test requiere al menos un proveedor activo (estatus = 1) para actualizar la compra.');
        }
        
        $datos = [
            'id_compra' => $this->idCompraDisponible,
            'fecha_entrada' => date('Y-m-d'),
            'id_proveedor' => $this->idProveedorDisponible,
            'productos' => [
                [
                    'id_producto' => $this->idProductoDisponible,
                    'cantidad' => 15,
                    'precio_unitario' => 30.00,
                    'precio_total' => 450.00
                ]
            ]
        ];

        $resultado = $this->entrada->testEjecutarActualizacion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('Compra actualizada exitosamente', $resultado['mensaje']);
    }

    public function testActualizarCompraInexistente() { /*|||||| ACTUALIZAR COMPRA INEXISTENTE ||||| 12 | */
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo (estatus = 1) para probar la actualización de compra inexistente.');
        }
        if ($this->idProveedorDisponible === null) {
            $this->fail('ERROR: No hay proveedores disponibles en la base de datos. Este test requiere al menos un proveedor activo (estatus = 1) para probar la actualización de compra inexistente.');
        }
        
        $datos = [
            'id_compra' => 99999, // Compra que no existe
            'fecha_entrada' => date('Y-m-d'),
            'id_proveedor' => $this->idProveedorDisponible,
            'productos' => [
                [
                    'id_producto' => $this->idProductoDisponible,
                    'cantidad' => 15,
                    'precio_unitario' => 30.00,
                    'precio_total' => 450.00
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('La compra no existe');
        $this->entrada->testEjecutarActualizacion($datos);
    }

    public function testEliminarCompraExistente() { /*|||||| ELIMINAR COMPRA EXISTENTE ||||| 13 | */
        if ($this->idCompraDisponible === null) {
            $this->fail('ERROR: No hay compras disponibles en la base de datos. Este test requiere al menos una compra existente para eliminarla.');
        }
        
        $datos = ['id_compra' => $this->idCompraDisponible];

        $resultado = $this->entrada->testEjecutarEliminacion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('Compra eliminada exitosamente', $resultado['mensaje']);
        
        // Limpiar la referencia ya que la compra fue eliminada
        $this->idCompraDisponible = null;
    }

    public function testEliminarCompraInexistente() { /*|||||| ELIMINAR COMPRA INEXISTENTE ||||| 14 | */
        $datos = ['id_compra' => 99999]; // Compra que no existe

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('La compra no existe');
        $this->entrada->testEjecutarEliminacion($datos);
    }

    public function testProcesarCompraRegistrar() { /*|||||| PROCESAR COMPRA REGISTRAR ||||| 15 | */
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo (estatus = 1) para registrar una compra mediante procesarCompra().');
        }
        if ($this->idProveedorDisponible === null) {
            $this->fail('ERROR: No hay proveedores disponibles en la base de datos. Este test requiere al menos un proveedor activo (estatus = 1) para registrar una compra mediante procesarCompra().');
        }
        
        $entradaDirecto = new \LoveMakeup\Proyecto\Modelo\Entrada();
        $json = json_encode([
            'operacion' => 'registrar',
            'datos' => [
                'fecha_entrada' => date('Y-m-d'),
                'id_proveedor' => $this->idProveedorDisponible,
                'productos' => [
                    [
                        'id_producto' => $this->idProductoDisponible,
                        'cantidad' => 5,
                        'precio_unitario' => 20.00,
                        'precio_total' => 100.00
                    ]
                ]
            ]
        ]);

        $resultado = $entradaDirecto->procesarCompra($json);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('Compra registrada exitosamente', $resultado['mensaje']);
    }

    public function testProcesarCompraConsultar() { /*|||||| PROCESAR COMPRA CONSULTAR ||||| 16 | */
        $entradaDirecto = new \LoveMakeup\Proyecto\Modelo\Entrada();
        $json = json_encode([
            'operacion' => 'consultar',
            'datos' => null
        ]);

        $resultado = $entradaDirecto->procesarCompra($json);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('datos', $resultado);
    }

    public function testProcesarCompraConsultarProductos() { /*|||||| PROCESAR COMPRA CONSULTAR PRODUCTOS ||||| 17 | */
        $entradaDirecto = new \LoveMakeup\Proyecto\Modelo\Entrada();
        $json = json_encode([
            'operacion' => 'consultarProductos',
            'datos' => null
        ]);

        $resultado = $entradaDirecto->procesarCompra($json);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('datos', $resultado);
    }

    public function testProcesarCompraConsultarProveedores() { /*|||||| PROCESAR COMPRA CONSULTAR PROVEEDORES ||||| 18 | */
        $entradaDirecto = new \LoveMakeup\Proyecto\Modelo\Entrada();
        $json = json_encode([
            'operacion' => 'consultarProveedores',
            'datos' => null
        ]);

        $resultado = $entradaDirecto->procesarCompra($json);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('datos', $resultado);
    }

    public function testProcesarCompraConsultarDetalles() { /*|||||| PROCESAR COMPRA CONSULTAR DETALLES ||||| 19 | */
        if ($this->idCompraDisponible === null) {
            $this->fail('ERROR: No hay compras disponibles en la base de datos. Este test requiere al menos una compra existente para consultar sus detalles mediante procesarCompra().');
        }
        
        $entradaDirecto = new \LoveMakeup\Proyecto\Modelo\Entrada();
        $json = json_encode([
            'operacion' => 'consultarDetalles',
            'datos' => ['id_compra' => $this->idCompraDisponible]
        ]);

        $resultado = $entradaDirecto->procesarCompra($json);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('datos', $resultado);
    }

    public function testProcesarCompraActualizar() { /*|||||| PROCESAR COMPRA ACTUALIZAR ||||| 20 | */
        if ($this->idCompraDisponible === null) {
            $this->fail('ERROR: No hay compras disponibles en la base de datos. Este test requiere al menos una compra existente para actualizarla mediante procesarCompra().');
        }
        if ($this->idProductoDisponible === null) {
            $this->fail('ERROR: No hay productos disponibles en la base de datos. Este test requiere al menos un producto activo (estatus = 1) para actualizar la compra mediante procesarCompra().');
        }
        if ($this->idProveedorDisponible === null) {
            $this->fail('ERROR: No hay proveedores disponibles en la base de datos. Este test requiere al menos un proveedor activo (estatus = 1) para actualizar la compra mediante procesarCompra().');
        }
        
        $entradaDirecto = new \LoveMakeup\Proyecto\Modelo\Entrada();
        $json = json_encode([
            'operacion' => 'actualizar',
            'datos' => [
                'id_compra' => $this->idCompraDisponible,
                'fecha_entrada' => date('Y-m-d'),
                'id_proveedor' => $this->idProveedorDisponible,
                'productos' => [
                    [
                        'id_producto' => $this->idProductoDisponible,
                        'cantidad' => 8,
                        'precio_unitario' => 22.00,
                        'precio_total' => 176.00
                    ]
                ]
            ]
        ]);

        $resultado = $entradaDirecto->procesarCompra($json);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('Compra actualizada exitosamente', $resultado['mensaje']);
    }

    public function testProcesarCompraEliminar() { /*|||||| PROCESAR COMPRA ELIMINAR ||||| 21 | */
        if ($this->idCompraDisponible === null) {
            $this->fail('ERROR: No hay compras disponibles en la base de datos. Este test requiere al menos una compra existente para eliminarla mediante procesarCompra().');
        }
        
        $entradaDirecto = new \LoveMakeup\Proyecto\Modelo\Entrada();
        $json = json_encode([
            'operacion' => 'eliminar',
            'datos' => ['id_compra' => $this->idCompraDisponible]
        ]);

        $resultado = $entradaDirecto->procesarCompra($json);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('Compra eliminada exitosamente', $resultado['mensaje']);
    }
}
