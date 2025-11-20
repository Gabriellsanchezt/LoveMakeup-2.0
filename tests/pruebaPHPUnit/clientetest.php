<?php
namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Cliente; // Ajusta el namespace si es necesario
use ReflectionClass;

class ClienteTestable {
    private Cliente $cliente;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->cliente = new Cliente();
        $this->reflection = new ReflectionClass($this->cliente);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->cliente, $args);
    }

    public function testVerificarExistencia($campo, $valor) {
        return $this->invokePrivate('verificarExistencia', [[
            'campo' => $campo,
            'valor' => $valor
        ]]);
    }

    public function testEjecutarActualizacion($datos) {
        return $this->invokePrivate('ejecutarActualizacion', [$datos]);
    }

    public function getCliente(): Cliente {
        return $this->cliente;
    }
}

class ClienteTest extends TestCase {
    private ClienteTestable $cliente;

    protected function setUp(): void {
        $this->cliente = new ClienteTestable();
    }

    public function testOperacionInvalida() {
        $clienteReal = $this->cliente->getCliente();
        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);

        $resultado = $clienteReal->procesarCliente($json);
        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    public function testConsultar() {
        $resultado = $this->cliente->getCliente()->consultar();
        $this->assertIsArray($resultado);

        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_persona', $resultado[0]);
            $this->assertArrayHasKey('estatus', $resultado[0]);
            echo "\n testConsultar | Consulta exitosa.\n";
        } else {
            echo "\n testConsultar | Consulta vacía o fallida.\n";
        }
    }

    public function testCedulaExistente() {
        $cedula = '306716741';
        $existe = $this->cliente->testVerificarExistencia('cedula', $cedula);

        echo $existe
            ? "\n testCedulaExistente | La cédula '$cedula' existe.\n"
            : "\n testCedulaExistente | La cédula '$cedula' NO existe.\n";

        $this->assertFalse($existe);
    }

    public function testCorreoInexistente() {
        $correo = 'danielactual50@gmail.com';
        $existe = $this->cliente->testVerificarExistencia('correo', $correo);

        echo $existe
            ? "\n testCorreoInexistente | El correo '$correo' existe.\n"
            : "\n testCorreoInexistente | El correo '$correo' NO existe.\n";

        $this->assertFalse($existe);
    }

    public function testActualizarCliente() {
        $datos = [
            'cedula' => '1044',
            'correo' => 'hola@expla.com',
            'estatus' => 1,
            'id_persona' => 100000
        ];

        $resultado = $this->cliente->testEjecutarActualizacion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('actualizar', $resultado['accion']);

        echo ($resultado['respuesta'] === 1 && $resultado['accion'] === 'actualizar')
            ? "\n testActualizarCliente | Actualización exitosa.\n"
            : "\n testActualizarCliente | Error en la actualización.\n";
    }
}

?>