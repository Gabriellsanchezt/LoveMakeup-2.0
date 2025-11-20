<?php
namespace Tests\PruebaPHPUnit;
use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Login; // Asegúrate de que el namespace y ruta sean correctos
use ReflectionClass;

class LoginTestable {
    private Login $login;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->login = new Login();
        $this->reflection = new ReflectionClass($this->login);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->login, $args);
    }

    // Métodos privados accesibles desde el test
    public function testEncrypt($clave) {
        return $this->invokePrivate('encryptClave', [['clave' => $clave]]);
    }

    public function testDecrypt($claveEncriptada) {
        return $this->invokePrivate('decryptClave', [['clave_encriptada' => $claveEncriptada]]);
    }

    public function testVerificarCredenciales($cedula, $clavePlano) {
        return $this->invokePrivate('verificarCredenciales', [[
            'cedula' => $cedula,
            'clave' => $clavePlano
        ]]);
    }

    public function testRegistrarCliente($datos) {
        return $this->invokePrivate('registrarCliente', [$datos]);
    }

    public function testVerificarExistencia($campo, $valor) {
        return $this->invokePrivate('verificarExistencia', [[
            'campo' => $campo,
            'valor' => $valor
        ]]);
    }

    public function testObtenerPersonaPorCedula($cedula) {
        return $this->invokePrivate('obtenerPersonaPorCedula', [['cedula' => $cedula]]);
    }

    public function testConsultar($id_persona) {
        return $this->invokePrivate('consultar', [$id_persona]);
    }

    public function getLogin(): Login {
        return $this->login;
    }
}

/*||||||||||||||||||||||||||||||| CLASE DE TEST  |||||||||||||||||||||||||||||| */
class LoginTest extends TestCase {
    private LoginTestable $login;

    protected function setUp(): void {
        $this->login = new LoginTestable();
    }

    public function testEncriptacionYDesencriptacion() {
        $claveOriginal = 'MiClaveSegura123';
        $claveEncriptada = $this->login->testEncrypt($claveOriginal);
        $claveDesencriptada = $this->login->testDecrypt($claveEncriptada);

        $this->assertEquals($claveOriginal, $claveDesencriptada);
    }

    public function testVerificarCredenciales() {
        $cedula = '20152522';
        $clavePlano = 'love1234';

        $resultado = $this->login->testVerificarCredenciales($cedula, $clavePlano);

        $this->assertNotNull($resultado);
        $this->assertObjectHasProperty('cedula', $resultado);
        $this->assertObjectHasProperty('estatus', $resultado);
    }

    public function testRegistrarClienteNuevo() {
        $datos = [
            'cedula' => '30800123',
            'nombre' => 'Daniel',
            'apellido' => 'Sánchez',
            'correo' => 'daniel.sanchez.test@gmail.com',
            'telefono' => '04141234567',
            'clave' => 'ClaveSegura123'
        ];

        $resultado = $this->login->testRegistrarCliente($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('incluir', $resultado['accion']);
    }

    public function testCedulaExistente() {
        $cedula = '3071651';
        $existe = $this->login->testVerificarExistencia('cedula', $cedula);
        $this->assertFalse($existe);
    }

    public function testCorreoInexistente() {
        $correo = 'danielsanchezcv@gmail.com';
        $existe = $this->login->testVerificarExistencia('correo', $correo);
        $this->assertFalse($existe);
    }

    public function testObtenerPersonaPorCedula() {
        $cedula = '30716541';
        $resultado = $this->login->testObtenerPersonaPorCedula($cedula);

        $this->assertNotNull($resultado);
        $this->assertObjectHasProperty('cedula', $resultado);
        $this->assertObjectHasProperty('origen', $resultado);
    }

    public function testConsultarPermisosPorId() {
        $id_persona = 1;
        $resultado = $this->login->testConsultar($id_persona);

        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_permiso', $resultado[0]);
            $this->assertArrayHasKey('id_modulo', $resultado[0]);
            $this->assertArrayHasKey('accion', $resultado[0]);
            $this->assertArrayHasKey('estado', $resultado[0]);
            $this->assertArrayHasKey('id_rol', $resultado[0]);
        } else {
            $this->fail("No se encontraron permisos para el id_persona $id_persona");
        }
    }
}


?>
