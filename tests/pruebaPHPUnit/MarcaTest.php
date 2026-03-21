<?php

namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Marca;
use ReflectionClass;

/*||||||||||||||||||||||||||||||| CLASE TESTABLE CON REFLEXIÓN ||||||||||||||||||||||||||*/
class MarcaTestable {
    private Marca $marca;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->marca = new Marca();
        $this->reflection = new ReflectionClass($this->marca);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->marca, $args);
    }

    /* Métodos privados */
    public function testInsertar($datos) {
        return $this->invokePrivate('insertar', [$datos]);
    }

    public function testActualizar($datos) {
        return $this->invokePrivate('actualizar', [$datos]);
    }

    public function testEliminar($datos) {
        return $this->invokePrivate('eliminarLogico', [$datos]);
    }

    public function getMarca() {
        return $this->marca;
    }
}

/*||||||||||||||||||||||||||||||| CLASE PRINCIPAL DE TEST ||||||||||||||||||||||||||||||*/
class MarcaTest extends TestCase {

    private MarcaTestable $marca;

    protected function setUp(): void {
        $this->marca = new MarcaTestable();
    }

    /*|||||||||||| OPERACIÓN INVÁLIDA ||||||||||||*/
    public function testOperacionInvalida() {
        $marcaReal = new Marca();

        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);

        $resultado = $marcaReal->procesarMarca($json);

        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    /*|||||||||||| REGISTRAR MARCA ||||||||||||*/
    public function testRegistrarMarca() {

        $json = json_encode([
            'operacion' => 'incluir',
            'datos' => [
                'nombre' => 'MarcaTestUnit'
            ]
        ]);

        $resultado = $this->marca->getMarca()->procesarMarca($json);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('respuesta', $resultado);
    }

    /*|||||||||||| REGISTRAR MARCA EXISTENTE ||||||||||||*/
    public function testRegistrarMarcaExistente() {

        $json = json_encode([
            'operacion' => 'incluir',
            'datos' => [
                'nombre' => 'MarcaTestUnit'
            ]
        ]);

        $resultado = $this->marca->getMarca()->procesarMarca($json);

        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertStringContainsString('Ya existe', $resultado['mensaje']);
    }

    /*|||||||||||| CONSULTAR MARCAS ||||||||||||*/
    public function testConsultarMarcas() {

        $resultado = $this->marca->getMarca()->consultar();

        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_marca', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
        }
    }

    /*|||||||||||| ACTUALIZAR MARCA ||||||||||||*/
    public function testActualizarMarca() {

        $datos = [
            'id_marca' => 1,
            'nombre' => 'MarcaActualizadaTest'
        ];

        $resultado = $this->marca->testActualizar($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('actualizar', $resultado['accion']);
    }

    /*|||||||||||| ELIMINAR MARCA ||||||||||||*/
    public function testEliminarMarca() {

        $datos = [
            'id_marca' => 1
        ];

        $resultado = $this->marca->testEliminar($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('eliminar', $resultado['accion']);
    }

    /*|||||||||||| TEST MASIVO ||||||||||||*/
    public function testRegistrarVariasMarcas() {

        fwrite(STDOUT, "\n\n >---- INICIO TEST MASIVO DE MARCAS ------<\n");

        $cantidad = 200;

        for ($i = 1; $i <= $cantidad; $i++) {

            $nombre = "MarcaMasiva{$i}";

            $json = json_encode([
                'operacion' => 'incluir',
                'datos' => [
                    'nombre' => $nombre
                ]
            ]);

            $resultado = $this->marca->getMarca()->procesarMarca($json);

            fwrite(STDOUT, "\n → Intento #{$i} | Nombre: {$nombre}\n");

            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('accion', $resultado);
            $this->assertEquals('incluir', $resultado['accion']);

            if ($resultado['respuesta'] === 1) {

                fwrite(STDOUT, "  Registro exitoso.\n");
                $this->assertEquals(1, $resultado['respuesta']);

            } elseif ($resultado['respuesta'] === 0) {

                fwrite(STDOUT, "  Error: {$resultado['mensaje']}\n");

                $this->assertStringContainsString(
                    'Ya existe',
                    $resultado['mensaje']
                );

            } else {
                $this->fail("Respuesta inesperada en intento #{$i}");
            }
        }

        fwrite(STDOUT, "\n _-_-____ FIN TEST MASIVO DE MARCAS _-_-____\n\n");
    }
}