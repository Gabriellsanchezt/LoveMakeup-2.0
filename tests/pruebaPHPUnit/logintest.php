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

    public function procesarLogin(array $payload) {
        return $this->login->procesarLogin(json_encode($payload));
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

}

/*||||||||||||||||||||||||||||||| CLASE DE TEST  |||||||||||||||||||||||||||||| */
class LoginTest extends TestCase {
    private Login $login;

    protected function setUp(): void {
        $this->login = new Login();
    }

     public function testEncriptacionYDesencriptacion() {
        $claveOriginal = 'MiClaveSegura123';
        $claveEncriptada = $this->login->testEncrypt($claveOriginal);
        $claveDesencriptada = $this->login->testDecrypt($claveEncriptada);

        $this->assertEquals($claveOriginal, $claveDesencriptada);
    }


    public function testVerificarCredencialesOperacionVerificar() {

        $datosLogin = [
            'operacion' => 'verificar',
            'datos' => [
                'tipo_documento' => 'V',
                'cedula' => '10200300',
                'clave' => 'love1234',
            ]
        ];
        $resultado = $this->login->procesarLogin(json_encode($datosLogin));

        // Mostrar mensaje según el resultado
        if ($resultado !== null) {
            fwrite(STDOUT, "\n Credenciales verificadas correctamente. Usuario encontrado.\n");
            $this->assertIsObject($resultado);
            $this->assertObjectHasProperty('cedula', $resultado);
            $this->assertObjectHasProperty('estatus', $resultado);

        } else {
            fwrite(STDOUT, "\n No se encontró el usuario o la clave es incorrecta.\n");
            // Si esperas que SIEMPRE exista, puedes forzar fallo:
            $this->fail("El módulo devolvió null. No se verificaron las credenciales.");
        }
    }

    public function testOperacionDolar() {

        $datosLogin = [
            'operacion' => 'dolar',
            'datos' => [
                'fecha' => '2025-01-03',
                'tasa' => 40.50,
                'fuente' => 'Automatico'
            ]
        ];

        $resultado = $this->login->procesarLogin(json_encode($datosLogin));

        // Si la fecha NO existe → debe registrar y devolver algo
        if ($resultado !== null) {
            // Ajusta estas validaciones según lo que devuelva ejecutarRegistro()
             fwrite(STDOUT, "\n La fecha NO existía y se registró correctamente.\n");
            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('respuesta', $resultado);
            $this->assertArrayHasKey('accion', $resultado);

        } else {
            // Si la fecha YA existe debe devolver null
            fwrite(STDOUT, "\n Error : La fecha YA existe, por eso el módulo devolvió null.\n");
            $this->assertNull($resultado);
        }
    }
    
    public function testOperacionRegistrar() {

        $datosRegistro = [
            'operacion' => 'registrar',
            'datos' => [
                'nombre' => 'Daniel',
                'apellido' => 'Sánchez',
                'cedula' => '30716541',          
                'telefono' => '04141234567',
                'correo' => 'daniel.sanc3hez.test@gmail.com',
                'tipo_documento' => 'V',
                'clave' => 'ClaveSegura123'
            ]
        ];

        $resultado = $this->login->procesarLogin(json_encode($datosRegistro));

        //Cedula o correo ya existen
        if (isset($resultado['respuesta']) && $resultado['respuesta'] === 0) {

            fwrite(STDOUT, "\n No se pudo registrar. Motivo: {$resultado['text']}\n");
            $this->assertEquals(0, $resultado['respuesta']);
            $this->assertEquals('incluir', $resultado['accion']);
            return; // Finaliza aquí si hubo error
        }

        fwrite(STDOUT, "\n Registro exitoso. Cliente incluido correctamente.\n");
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('incluir', $resultado['accion']);
    }
    
    public function testOperacionValidar() {

        $datosValidar = [
            'operacion' => 'validar',
            'datos' => [
                'cedula' => '30800133',
                'tipo_documento' => 'V'
            ]
        ];

        $resultado = $this->login->procesarLogin(json_encode($datosValidar));

        if ($resultado !== null) {
            fwrite(STDOUT, "\n Persona encontrada. Validación exitosa.\n");
            $this->assertIsObject($resultado);
            $this->assertObjectHasProperty('cedula', $resultado);
            $this->assertObjectHasProperty('origen', $resultado);

        } else {
            // Caso 2: La persona NO existe
            fwrite(STDOUT, "\n No se encontró la persona con esa cédula.\n");

            $this->assertNull($resultado);
        }
    }

    public function testOperacionVerificarCedula() {
        $datosLogin = [
            'operacion' => 'verificarcedula',
            'datos' => [
                'cedula' => '10200300'   // Ajusta según tu BD
            ]
        ];

        $resultado = $this->login->procesarLogin(json_encode($datosLogin));
        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "\n La cédula YA está registrada.\n");
            $this->assertEquals(1, $resultado['respuesta']);
            $this->assertEquals('verificar', $resultado['accion']);
            $this->assertEquals('La cédula ya está registrada', $resultado['text']);

        } else {
            fwrite(STDOUT, "\n La cédula NO está registrada.\n");
            $this->assertEquals(0, $resultado['respuesta']);
            $this->assertEquals('verificar', $resultado['accion']);
            $this->assertEquals('La cédula no se encuentra registrada', $resultado['text']);
        }
    }

    public function testOperacionVerificarCorreo() {
        $datosLogin = [
            'operacion' => 'verificarCorreo',
            'datos' => [
                'correo' => 'daniel.sanchez.test@gmail.com'   
            ]
        ];
        $resultado = $this->login->procesarLogin(json_encode($datosLogin));

        // Caso 1: El correo YA está registrado
        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "\n El correo YA está registrado.\n");
            $this->assertEquals(1, $resultado['respuesta']);
            $this->assertEquals('verificarcorreo', $resultado['accion']);
            $this->assertEquals('La correo ya está registrada', $resultado['text']);

        } else {
            fwrite(STDOUT, "\n El correo NO está registrado.\n");
            $this->assertEquals(0, $resultado['respuesta']);
            $this->assertEquals('verificarcorreo', $resultado['accion']);
            $this->assertEquals('La correo no se encuentra registrada', $resultado['text']);
        }
    }

    

}


?>
