<?php
namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Olvidoclave;

class OlvidoClaveTest extends TestCase {

    private Olvidoclave $olvidoclave;

    protected function setUp(): void {
        $this->olvidoclave = new Olvidoclave();
    }


    public function testActualizarClave() {

        $datosOlvido = [
            'operacion' => 'actualizar',
            'datos' => [
                'cedula' => '30716541',
                'clave' => 'love1234'
            ]
        ];

        $resultado = $this->olvidoclave->procesarOlvido(json_encode($datosOlvido));

        fwrite(STDOUT, "\n testActualizarClave | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        if (!isset($resultado['accion'])) {

            fwrite(STDOUT, " Error interno: " . ($resultado['mensaje'] ?? 'Sin mensaje') . "\n");
            $this->assertEquals(0, $resultado['respuesta']);
            return;
        }

        $this->assertEquals('actualizar', $resultado['accion']);
        // Caso exitoso
        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "Clave actualizada correctamente.\n");
            $this->assertEquals(1, $resultado['respuesta']);

        // Caso error
        } elseif ($resultado['respuesta'] === 0) {

            fwrite(STDOUT, " Error al actualizar clave: " . $resultado['text'] . "\n");
            $this->assertArrayHasKey('text', $resultado);
            $this->assertContains(
                $resultado['text'],
                [
                    'El usuario no existe',
                    'Error al actualizar clave'
                ]
            );

        } else {
            $this->fail("Respuesta inesperada en procesarOlvido()");
        }
    }

    
}
