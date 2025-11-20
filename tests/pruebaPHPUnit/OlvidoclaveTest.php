<?php
namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Olvido;
use ReflectionClass;

class OlvidoTestable {
    private Olvido $olvido;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->olvido = new Olvido();
        $this->reflection = new ReflectionClass($this->olvido);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->olvido, $args);
    }

    public function testEncryptClave($clave) {
        return $this->invokePrivate('encryptClave', [['clave' => $clave]]);
    }

    public function testDecryptClave($claveEncriptada) {
        return $this->invokePrivate('decryptClave', [['clave_encriptada' => $claveEncriptada]]);
    }

    public function testEjecutarActualizacionCliente($datos) {
        return $this->invokePrivate('ejecutarActualizacionCliente', [$datos]);
    }

    public function testEjecutarActualizacionUsuario($datos) {
        return $this->invokePrivate('ejecutarActualizacionUsuario', [$datos]);
    }

    public function testEjecutarActualizacionPorOrigen($datos) {
        return $this->invokePrivate('ejecutarActualizacionPorOrigen', [$datos]);
    }

    public function getOlvido(): Olvido {
        return $this->olvido;
    }
}


class OlvidoclaveTest extends TestCase {
    private OlvidoTestable $olvido;

    protected function setUp(): void {
        $this->olvido = new OlvidoTestable();
    }

    public function testEncriptacionYDesencriptacion() {
        $claveOriginal = 'LaraVenezuela123';
        $claveEncriptada = $this->olvido->testEncryptClave($claveOriginal);
        $claveDesencriptada = $this->olvido->testDecryptClave($claveEncriptada);
        $this->assertEquals($claveOriginal, $claveDesencriptada);
    }

    public function testOperacionInvalida() {
        $json = json_encode([
            'operacion' => 'desconocida',
            'datos' => []
        ]);
        $resultado = $this->olvido->getOlvido()->procesarOlvido($json);
        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    public function testActualizarClaveCliente() {
        $datos = [
            'id_persona' => 3,
            'clave' => 'NuevaClaveCliente123'
        ];
        $resultado = $this->olvido->testEjecutarActualizacionCliente($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('actualizar', $resultado['accion']);
    }

    public function testActualizarClaveUsuario() {
        $datos = [
            'id_persona' => 2,
            'clave' => 'love1234'
        ];
        $resultado = $this->olvido->testEjecutarActualizacionUsuario($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('actualizar', $resultado['accion']);
    }

    public function testActualizarPorOrigenCliente() {
        $datos = [
            'id_persona' => 3,
            'clave' => 'ClaveClienteOrigen',
            'tabla_origen' => 1
        ];
        $resultado = $this->olvido->testEjecutarActualizacionPorOrigen($datos);
        $this->assertEquals('actualizar', $resultado['accion']);
    }

    public function testActualizarPorOrigenUsuario() {
        $datos = [
            'id_persona' => 4,
            'clave' => 'ClaveUsuarioOrigen',
            'tabla_origen' => 2
        ];
        $resultado = $this->olvido->testEjecutarActualizacionPorOrigen($datos);
        $this->assertEquals('actualizar', $resultado['accion']);
    }
}
?>