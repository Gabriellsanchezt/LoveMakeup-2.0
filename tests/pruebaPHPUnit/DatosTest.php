<?php
namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Datos;
use ReflectionClass;

class DatosTestable {
    private Datos $datos;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->datos = new Datos();
        $this->reflection = new ReflectionClass($this->datos);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->datos, $args);
    }

    public function testEncrypt($clave) {
        return $this->invokePrivate('encryptClave', [['clave' => $clave]]);
    }

    public function testDecrypt($claveEncriptada) {
        return $this->invokePrivate('decryptClave', [['clave_encriptada' => $claveEncriptada]]);
    }

    public function testEjecutarActualizacion($datos) {
        return $this->invokePrivate('ejecutarActualizacion', [$datos]);
    }

    public function testValidarClaveActual($id_persona, $clave_actual) {
        return $this->invokePrivate('validarClaveActual', [[
            'id_persona' => $id_persona,
            'clave_actual' => $clave_actual
        ]]);
    }

    public function testEjecutarActualizacionClave($id_persona, $clavePlano) {
        return $this->invokePrivate('ejecutarActualizacionClave', [[
            'id_persona' => $id_persona,
            'clave' => $clavePlano
        ]]);
    }

    public function testVerificarExistencia($campo, $valor) {
        return $this->invokePrivate('verificarExistencia', [[
            'campo' => $campo,
            'valor' => $valor
        ]]);
    }

    public function getDatos(): Datos {
        return $this->datos;
    }
}


class DatosTest extends TestCase {
    private DatosTestable $datos;

    protected function setUp(): void {
        $this->datos = new DatosTestable();
    }

    public function testOperacionInvalida() {
        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);
        $resultado = $this->datos->getDatos()->procesarUsuario($json);
        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    public function testEncriptacionYDesencriptacion() {
        $claveOriginal = 'MiClaveSegura123';
        $claveEncriptada = $this->datos->testEncrypt($claveOriginal);
        $claveDesencriptada = $this->datos->testDecrypt($claveEncriptada);
        $this->assertEquals($claveOriginal, $claveDesencriptada);
    }

    public function testActualizarDatosUsuario() {
        $datos = [
            'cedula' => '10200300',
            'correo' => 'actualizado@example.com',
            'nombre' => 'Daniel',
            'apellido' => 'Sánchez',
            'telefono' => '04141234567',
            'id_persona' => 2
        ];
        $resultado = $this->datos->testEjecutarActualizacion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('actualizar', $resultado['accion']);
    }

    public function testValidarClaveActual() {
        $resultado = $this->datos->testValidarClaveActual(2, 'love1234');
        $this->assertTrue($resultado);
    }

    public function testActualizarClaveUsuario() {
        $resultado = $this->datos->testEjecutarActualizacionClave(2, 'love1234');
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('clave', $resultado['accion']);
    }

    public function testCedulaExistente() {
        $existe = $this->datos->testVerificarExistencia('cedula', '3071651');
        $this->assertFalse($existe);
    }

    public function testCorreoInexistente() {
        $existe = $this->datos->testVerificarExistencia('correo', 'danielsanchezcv@gmail.com');
        $this->assertFalse($existe);
    }

    public function testConsultarDatosPorId() {
        $resultado = $this->datos->getDatos()->consultardatos(2);
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('cedula', $resultado[0]);
            $this->assertArrayHasKey('correo', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
            $this->assertArrayHasKey('apellido', $resultado[0]);
        } else {
            $this->fail("No se encontraron datos para el id_persona 2");
        }
    }
}
