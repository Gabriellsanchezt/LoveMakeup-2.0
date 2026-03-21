<?php

namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Tasacambio;
use ReflectionClass;

/*||||||||||||||||||||||||||||||| TESTABLE  ||||||||||||||||||||||||||*/
class TasacambioTestable {
    private Tasacambio $tasa;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->tasa = new Tasacambio();
        $this->reflection = new ReflectionClass($this->tasa);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->tasa, $args);
    }

    /* Métodos privados */
    public function testVerificarFechaNoExiste($fecha) {
        return $this->invokePrivate('verificarFechaNoExiste', [$fecha]);
    }

    public function testEjecutarRegistro($datos, $operacion = 'modificar') {
        return $this->invokePrivate('ejecutarRegistro', [$datos, $operacion]);
    }

    public function testEjecutarModificacion($datos, $operacion = 'modificar') {
        return $this->invokePrivate('ejecutarMofidicacion', [$datos, $operacion]);
    }

    public function getTasa() {
        return $this->tasa;
    }
}

/*||||||||||||||||||||||||||||||| CLASE PRINCIPAL DE TEST ||||||||||||||||||||||||||||||*/
class TasacambioTest extends TestCase {

    private TasacambioTestable $tasa;

    protected function setUp(): void {
        $this->tasa = new TasacambioTestable();
    }

    /*|||||||||||| OPERACIÓN INVÁLIDA ||||||||||||*/
    public function testOperacionInvalida() {

        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);

        $resultado = $this->tasa->getTasa()->procesarTasa($json);

        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['text']);
    }

    /*|||||||||||| REGISTRAR NUEVA TASA ||||||||||||*/
    public function testRegistrarNuevaTasa() {

        $fecha = date('Y-m-d');

        $json = json_encode([
            'operacion' => 'modificar',
            'datos' => [
                'fecha' => $fecha,
                'tasa' => 36.5,
                'fuente' => 'BCV'
            ]
        ]);

        $resultado = $this->tasa->getTasa()->procesarTasa($json);

        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
    }

    /*|||||||||||| ACTUALIZAR TASA EXISTENTE ||||||||||||*/
    public function testActualizarTasaExistente() {

        $fecha = date('Y-m-d');

        $json = json_encode([
            'operacion' => 'modificar',
            'datos' => [
                'fecha' => $fecha,
                'tasa' => 40.0,
                'fuente' => 'Monitor'
            ]
        ]);

        $resultado = $this->tasa->getTasa()->procesarTasa($json);

        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
    }

    /*|||||||||||| VALIDAR TASA INVÁLIDA ||||||||||||*/
    public function testTasaInvalida() {

        $json = json_encode([
            'operacion' => 'modificar',
            'datos' => [
                'fecha' => date('Y-m-d'),
                'tasa' => -10,
                'fuente' => 'ErrorTest'
            ]
        ]);

        $resultado = $this->tasa->getTasa()->procesarTasa($json);

        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertStringContainsString('tasa', $resultado['text']);
    }

    /*|||||||||||| CONSULTAR TASAS ||||||||||||*/
    public function testConsultarTasas() {

        $resultado = $this->tasa->getTasa()->consultar();

        $this->assertIsArray($resultado);
    }

    /*|||||||||||| OBTENER TASA ACTUAL ||||||||||||*/
    public function testObtenerTasaActual() {

        $resultado = $this->tasa->getTasa()->obtenerTasaActual();

        $this->assertTrue(is_array($resultado) || $resultado === false);
    }

    /*|||||||||||| TEST MASIVO (🔥 IMPORTANTE) ||||||||||||*/
    public function testRegistrarVariasTasas() {

        fwrite(STDOUT, "\n\n >---- INICIO TEST MASIVO DE TASAS ------<\n");

        $cantidad = 100;

        for ($i = 1; $i <= $cantidad; $i++) {

            $fecha = date('Y-m-d', strtotime("+{$i} days"));

            $json = json_encode([
                'operacion' => 'sincronizar',
                'datos' => [
                    'fecha' => $fecha,
                    'tasa' => rand(30, 60),
                    'fuente' => 'API'
                ]
            ]);

            $resultado = $this->tasa->getTasa()->procesarTasa($json);

            fwrite(STDOUT, "\n → Intento #{$i} | Fecha: {$fecha}\n");

            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('accion', $resultado);

            if ($resultado['respuesta'] === 1) {

                fwrite(STDOUT, "  OK\n");
                $this->assertEquals(1, $resultado['respuesta']);

            } elseif ($resultado['respuesta'] === 0) {

                fwrite(STDOUT, "  Error: {$resultado['text']}\n");

                $this->assertArrayHasKey('text', $resultado);

            } else {
                $this->fail("Respuesta inesperada en intento #{$i}");
            }
        }

        fwrite(STDOUT, "\n _-_-____ FIN TEST MASIVO DE TASAS _-_-____\n\n");
    }
}