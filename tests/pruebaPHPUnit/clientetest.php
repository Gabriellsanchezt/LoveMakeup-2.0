<?php
namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Cliente; // Ajusta el namespace si es necesario
use ReflectionClass;

class ClienteTestable {
   
}

class ClienteTest extends TestCase {
    private Cliente $cliente;

    protected function setUp(): void {
        $this->cliente = new Cliente();
    }

    public function testOperacionInvalida() {

        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);

        $resultado = $this->cliente->procesarCliente($json);

        fwrite(STDOUT, "\n testOperacionInvalida | Operación inválida detectada.\n");

        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['text']);
    }

   public function testActualizarCliente() {

    $datosCliente = [
        'operacion' => 'actualizar',
        'datos' => [
            'cedula' => '30716541',
            'correo' => 'vie21jo@correo.com',
            'estatus' => 1,
            'cedula_actual' => '30716541', //Validar
            'tipo_documento' => 'V',
            'correo_actual' => 'vie21jo@correo.com'
        ]
    ];

    $resultado = $this->cliente->procesarCliente(json_encode($datosCliente));

    fwrite(STDOUT, "\n testActualizarCliente | Resultado recibido.\n");

    // Siempre debe ser un array
    $this->assertIsArray($resultado);
    $this->assertArrayHasKey('accion', $resultado);
    $this->assertEquals('actualizar', $resultado['accion']);

    // Ahora validamos según la respuesta
    if ($resultado['respuesta'] === 0) {

        // Caso: error por cédula o correo
        $this->assertArrayHasKey('text', $resultado);

        fwrite(STDOUT, "\n Mensaje de error: " . $resultado['text'] . "\n");

        $this->assertContains(
            $resultado['text'],
            [
                'La cédula ya está registrada',
                'El correo electrónico ya está registrado',
                'el usuario no existe'
            ],
            "El mensaje de error no coincide con los esperados."
        );

    } elseif ($resultado['respuesta'] === 1) {
        // Caso: actualización exitosa
        fwrite(STDOUT, "\n Actualización realizada correctamente.\n");
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertArrayHasKey('accion', $resultado);
    } else {
        // Caso inesperado
        $this->fail("Respuesta inesperada en procesarCliente()");
    }
}

    public function testVerificarCedula() {

        $datosCliente = [
            'operacion' => 'verificar',
            'datos' => [
                'cedula' => '306716741'
            ]
        ];

        $resultado = $this->cliente->procesarCliente(json_encode($datosCliente));

        fwrite(STDOUT, "\n testVerificarCedula | Verificación ejecutada.\n");

        $this->assertIsArray($resultado);
        $this->assertEquals('verificar', $resultado['accion']);
        $this->assertArrayHasKey('text', $resultado);
    }

    public function testConsultar() {

        $limite = 100;
        $resultado = $this->cliente->consultar($limite);

        fwrite(STDOUT, "\n testConsultar | Consulta ejecutada.\n");

        $this->assertIsArray($resultado);

        if (!empty($resultado)) {

            // Validamos claves que sí existen
            $this->assertArrayHasKey('cedula', $resultado[0]);
            $this->assertArrayHasKey('estatus', $resultado[0]);
            $this->assertArrayHasKey('id_usuario', $resultado[0]);
            $this->assertArrayHasKey('id_rol', $resultado[0]);
        }
    }

}

?>