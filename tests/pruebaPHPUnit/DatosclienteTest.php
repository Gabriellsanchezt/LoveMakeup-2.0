<?php
namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Datoscliente;
use ReflectionClass;

class DatosclienteTestable {
    private Datoscliente $datoscliente;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->datoscliente = new Datoscliente();
        $this->reflection = new ReflectionClass($this->datoscliente);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->datoscliente, $args);
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

    public function testEjecutarEliminacionCliente($id_persona) {
        return $this->invokePrivate('ejecutarEliminacion', [[
            'id_persona' => $id_persona
        ]]);
    }

    public function testRegistroDireccion($datos) {
        return $this->invokePrivate('RegistroDireccion', [$datos]);
    }

    public function testEjecutarActualizacionDireccion($datos) {
        return $this->invokePrivate('ejecutarActualizacionDireccion', [$datos]);
    }

    public function getDatoscliente(): Datoscliente {
        return $this->datoscliente;
    }
}


class DatosclienteTest extends TestCase {
    private DatosclienteTestable $datoscliente;

    protected function setUp(): void {
        $this->datoscliente = new DatosclienteTestable();
    }

    public function testOperacionInvalida() {
        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);
        $resultado = $this->datoscliente->getDatoscliente()->procesarCliente($json);
        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    public function testEncriptacionYDesencriptacion() {
        $claveOriginal = 'MiClaveSegura123';
        $claveEncriptada = $this->datoscliente->testEncrypt($claveOriginal);
        $claveDesencriptada = $this->datoscliente->testDecrypt($claveEncriptada);
        $this->assertEquals($claveOriginal, $claveDesencriptada);
    }

    public function testActualizarDatosCliente() {
        $datos = [
            'cedula' => '10200300',
            'correo' => 'actualizado@example.com',
            'nombre' => 'Daniel',
            'apellido' => 'Sánchez',
            'telefono' => '04141234567',
            'id_persona' => 1
        ];
        $resultado = $this->datoscliente->testEjecutarActualizacion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('actualizar', $resultado['accion']);
    }

    public function testValidarClaveActual() {
        $resultado = $this->datoscliente->testValidarClaveActual(1, 'love1234');
        $this->assertTrue($resultado);
    }

    public function testActualizarClaveUsuario() {
        $resultado = $this->datoscliente->testEjecutarActualizacionClave(1, 'love1234');
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('clave', $resultado['accion']);
    }

    public function testCedulaExistente() {
        $existe = $this->datoscliente->testVerificarExistencia('cedula', '20554335');
        $this->assertFalse($existe);
    }

    public function testCorreoInexistente() {
        $existe = $this->datoscliente->testVerificarExistencia('correo', 'danielsanchezcv@gmail.com');
        $this->assertFalse($existe);
    }

    public function testConsultarDatosPorId() {
        $resultado = $this->datoscliente->getDatoscliente()->consultardatos(20);
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('cedula', $resultado[0]);
            $this->assertArrayHasKey('correo', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
            $this->assertArrayHasKey('apellido', $resultado[0]);
        }
    }

    public function testEliminarClienteExistente() {
        $resultado = $this->datoscliente->testEjecutarEliminacionCliente(1);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('eliminar', $resultado['accion']);
    }

    public function testRegistrarDireccionCliente() {
        $datos = [
            'id_metodoentrega' => 1,
            'id_persona' => 3,
            'direccion_envio' => 'Av. Lara, Edif. Copilot, Piso 3',
            'sucursal_envio' => 'Sucursal Barquisimeto'
        ];
        $resultado = $this->datoscliente->testRegistroDireccion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('incluir', $resultado['accion']);
    }

    public function testActualizarDireccionExistente() {
        $datos = [
            'id_direccion' => 1,
            'direccion_envio' => 'Av. Libertador, Edif. Copilot, Piso 5',
            'sucursal_envio' => 'Sucursal Este'
        ];
        $resultado = $this->datoscliente->testEjecutarActualizacionDireccion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('actualizardireccion', $resultado['accion']);
    }
}
