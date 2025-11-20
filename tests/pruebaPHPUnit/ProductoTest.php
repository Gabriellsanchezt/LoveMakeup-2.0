<?php

namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Producto;
use ReflectionClass;

/*||||||||||||||||||||||||||||||| CLASE TESTABLE CON REFLEXIÓN ||||||||||||||||||||||||||*/
class ProductoTestable {
    private Producto $producto;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->producto = new Producto();
        $this->reflection = new ReflectionClass($this->producto);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->producto, $args);
    }

    /* Métodos privados obtenidos mediante Reflection */
    public function testVerificarProductoExistente($nombre, $id_marca) {
        return $this->invokePrivate('verificarProductoExistente', [$nombre, $id_marca]);
    }

    public function testEjecutarRegistro($datos) {
        return $this->invokePrivate('ejecutarRegistro', [$datos]);
    }

    public function testEjecutarActualizacion($datos) {
        return $this->invokePrivate('ejecutarActualizacion', [$datos]);
    }

    public function testEjecutarEliminacion($datos) {
        return $this->invokePrivate('ejecutarEliminacion', [$datos]);
    }

    public function testEjecutarCambioEstatus($datos) {
        return $this->invokePrivate('ejecutarCambioEstatus', [$datos]);
    }

    public function getProducto() {
        return $this->producto;
    }
}

/*||||||||||||||||||||||||||||||| CLASE DE TEST PRINCIPAL ||||||||||||||||||||||||||||||*/
class ProductoTest extends TestCase {

    private ProductoTestable $producto;

    protected function setUp(): void {
        $this->producto = new ProductoTestable();
    }

    /*|||||||||||| OPERACIÓN INVÁLIDA ||||||||||||*/
    public function testOperacionInvalida() {
        $productoReal = new Producto();

        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);

        $resultado = $productoReal->procesarProducto($json);

        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    /*|||||||||||| REGISTRAR PRODUCTO EXISTENTE ||||||||||||*/
    public function testRegistrarProductoExistente() {
        $json = json_encode([
            'operacion' => 'registrar',
            'datos' => [
                'nombre' => 'Bálsamo premium',
                'id_marca' => 1,
                'descripcion' => 'Prueba',
                'cantidad_mayor' => 10,
                'precio_mayor' => 20,
                'precio_detal' => 25,
                'stock_maximo' => 100,
                'stock_minimo' => 1,
                'imagenes' => ['imagen.png'],
                'id_categoria' => 1
            ]
        ]);

        $resultado = $this->producto->getProducto()->procesarProducto($json);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('respuesta', $resultado);
    }

    /*|||||||||||| CONSULTAR PRODUCTOS ||||||||||||*/
    public function testConsultarProductos() {
        $resultado = $this->producto->getProducto()->consultar();

        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_producto', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
            $this->assertArrayHasKey('estatus', $resultado[0]);
        }
    }

    /*|||||||||||| PRODUCTOS ACTIVOS ||||||||||||*/
    public function testProductosActivos() {
        $resultado = $this->producto->getProducto()->ProductosActivos();

        $this->assertIsArray($resultado);
        foreach ($resultado as $p) {
            $this->assertEquals(1, $p['estatus']);
        }
    }

    /*|||||||||||| CATEGORÍAS ||||||||||||*/
    public function testObtenerCategoria() {
        $resultado = $this->producto->getProducto()->obtenerCategoria();

        $this->assertIsArray($resultado);
    }

    /*|||||||||||| MÉTODOS PRIVADOS ||||||||||||*/
    public function testVerificarProductoInexistente() {
        // nombre + ID de marca que no existe
        $resp = $this->producto->testVerificarProductoExistente('ProductoInexistenteXYZ', 9999);
        $this->assertFalse($resp);
    }

    public function testVerificarProductoExistenteFallido() {
        // Producto real → Base de gotero con id_marca = 1 (ajusta si en BD es otro)
        $resp = $this->producto->testVerificarProductoExistente('Base de gotero', 1);
        $this->assertTrue($resp, "Debe devolver true porque el producto sí existe");
    }

    public function testCambioEstatus() {
        $datos = [
            'id_producto' => 1,
            'estatus_actual' => 1
        ];

        $resultado = $this->producto->testEjecutarCambioEstatus($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('cambiarEstatus', $resultado['accion']);
    }
}
