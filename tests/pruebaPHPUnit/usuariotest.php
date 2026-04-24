<?php
namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\Usuario;
use ReflectionClass;

class UsuarioTestable {
    private Usuario $usuario;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->usuario = new Usuario();
        $this->reflection = new ReflectionClass($this->usuario);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->usuario, $args);
    }

    public function testEncrypt($clave) {
        return $this->invokePrivate('encryptClave', [$clave]);
    }

    public function testDecrypt($claveEncriptada) {
        return $this->invokePrivate('decryptClave', [$claveEncriptada]);
    }
}

//---------------------- TEST PRUEBA
class UsuarioTest extends TestCase {
    private Usuario $usuario;

    protected function setUp(): void {
        $this->usuario = new Usuario();
    }

    public function testRegistrarUsuario() {

        $datosUsuario = [
            'operacion' => 'registrar',
            'datos' => [
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'cedula' => '30716547',
                'tipo_documento' => 'V',
                'telefono' => '04141234567',
                'correo' => 'juan@tes1214t.com',
                'clave' => '12345',
                'id_rol' => 3,
                'nivel' => 1
            ]
        ];

       $resultado = $this->usuario->procesarUsuario(json_encode($datosUsuario));
        fwrite(STDOUT, "\n testRegistrarUsuario | Respuesta recibida.\n");
       
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('accion', $resultado);
        $this->assertEquals('incluir', $resultado['accion']);

        //  Registro exitoso
        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "\n Registro exitoso del usuario.\n");
            $this->assertEquals(1, $resultado['respuesta']);
        // Error en el registro
        } elseif ($resultado['respuesta'] === 0) {
            fwrite(STDOUT, "\n Error en el registro: " . $resultado['text'] . "\n");
            $this->assertArrayHasKey('text', $resultado);
            // Validamos que el mensaje sea uno de los esperados
            $this->assertContains(
                $resultado['text'],
                [
                    'La cédula ya está registrada',
                    'El correo electrónico ya está registrado',
                    'el rol no existe'
                ],
                "El mensaje de error no coincide con los esperados."
            );

        } else {
            $this->fail("Respuesta inesperada en procesarUsuario()");
        }
    }

   public function testActualizarUsuario() {
        $datosUsuario = [
            'operacion' => 'actualizar',
            'datos' => [
                'cedula' => '30716547',
                'correo' => 'juan@tes1214t.com',
                'cedula_actual' => '30716547',
                'correo_actual' => 'juan@tes1214t.com',
                'id_rol' => 3,
                'nivel' => 3
            ]
        ];

        $resultado = $this->usuario->procesarUsuario(json_encode($datosUsuario));
        fwrite(STDOUT, "\n testActualizarUsuario | Respuesta recibida.\n");
       
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('accion', $resultado);
        $this->assertEquals('actualizar', $resultado['accion']);

        // Actualización exitosa
        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "\n  Actualización exitosa del usuario.\n");
            $this->assertEquals(1, $resultado['respuesta']);
        //  Error en la actualización
        } elseif ($resultado['respuesta'] === 0) {

            fwrite(STDOUT, "\n  Error en la actualización: " . $resultado['text'] . "\n");
            $this->assertArrayHasKey('text', $resultado);
        
            $this->assertContains(
                $resultado['text'],
                [
                    'La cédula ya está registrada',
                    'El correo electrónico ya está registrado',
                    'el usuario no existe',
                    'el rol no existe'
                ],
                "El mensaje de error no coincide con los esperados."
            );

        } else {
            $this->fail("Respuesta inesperada en procesarUsuario()");
        }
    }

    public function testEliminarUsuario() {
        $datosUsuario = [
            'operacion' => 'eliminar',
            'datos' => [
                'cedula' => '12345678'
            ]
        ];
        $resultado = $this->usuario->procesarUsuario(json_encode($datosUsuario));

        fwrite(STDOUT, "\n testEliminarUsuario | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('accion', $resultado);
        $this->assertEquals('eliminar', $resultado['accion']);

        // Eliminación exitosa
        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "\n Usuario eliminado correctamente.\n");
            $this->assertEquals(1, $resultado['respuesta']);

        //  Error en la eliminación
        } elseif ($resultado['respuesta'] === 0) {
            fwrite(STDOUT, "\n Error al eliminar usuario: " . $resultado['text'] . "\n");
            $this->assertArrayHasKey('text', $resultado);
            $this->assertContains(
                $resultado['text'],
                [
                    'el usuario no existe'
                ],
                "El mensaje de error no coincide con los esperados."
            );
        } else {
            $this->fail("Respuesta inesperada en procesarUsuario()");
        }
    }


    public function testVerificarCedula() {
        $datosUsuario = [
            'operacion' => 'verificar',
            'datos' => [
                'cedula' => '12345678'
            ]
        ];

        $resultado = $this->usuario->procesarUsuario(json_encode($datosUsuario));
        fwrite(STDOUT, "\n testVerificarCedula | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        $this->assertEquals('verificar', $resultado['accion']);
        $this->assertArrayHasKey('text', $resultado);

        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "\n  La cédula está registrada.\n");
            $this->assertEquals('La cédula ya está registrada', $resultado['text']);

        } elseif ($resultado['respuesta'] === 0) {
            fwrite(STDOUT, "\n  La cédula NO está registrada.\n");
            $this->assertEquals('La cédula no se encuentra registrada', $resultado['text']);
        } else {
            $this->fail("Respuesta inesperada en verificar cédula.");
        }
    }

    public function testVerificarCorreo() {

        $datosUsuario = [
            'operacion' => 'verificarCorreo',
            'datos' => [
                'correo' => 'test@test.com'
            ]
        ];

        $resultado = $this->usuario->procesarUsuario(json_encode($datosUsuario));
        fwrite(STDOUT, "\n testVerificarCorreo | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        $this->assertEquals('verificarcorreo', $resultado['accion']);
        $this->assertArrayHasKey('text', $resultado);

        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "\n  El correo está registrado.\n");
            $this->assertEquals('La correo ya está registrada', $resultado['text']);

        } elseif ($resultado['respuesta'] === 0) {
            fwrite(STDOUT, "\n  El correo NO está registrado.\n");
            $this->assertEquals('La correo no se encuentra registrada', $resultado['text']);

        } else {
            $this->fail("Respuesta inesperada en verificar correo.");
        }
    }

   public function testVerificarRol() {

        $datosUsuario = [
            'operacion' => 'verificarrol',
            'datos' => [
                'id_rol' => 1
            ]
        ];

        $resultado = $this->usuario->procesarUsuario(json_encode($datosUsuario));
        fwrite(STDOUT, "\n testVerificarRol | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        $this->assertEquals('verifirol', $resultado['accion']);

        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "\n Exitoso - El rol existe.\n");
            $this->assertEquals(1, $resultado['respuesta']);

        } elseif ($resultado['respuesta'] === 0) {
            fwrite(STDOUT, "\n El rol NO existe: " . $resultado['text'] . "\n");
            $this->assertEquals('Error, no se encuentra un rol registrado', $resultado['text']);

        } else {
            $this->fail("Respuesta inesperada en verificar rol.");
        }
    }

     public function testConsultar() {

        $limite = 100;
        $resultado = $this->usuario->consultar($limite);

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

    //------------------------------------ MULTIPLES

    public function testRegistrarVariosUsuarios() {
        fwrite(STDOUT, "\n\n >---- INICIO DEL TEST DE REGISTRO MULTIPLE ------<\n");

        // Cantidad de usuarios a registrar
        $cantidad = 1000;
        for ($i = 1; $i <= $cantidad; $i++) {
           
            $cedula = "1011" . $i;
            $correo = "usuaTEST{$i}@test.com";

            $datosUsuario = [
                'operacion' => 'registrar',
                'datos' => [
                    'nombre' => "Usuario{$i}",
                    'apellido' => "Apellido{$i}",
                    'cedula' => $cedula,
                    'tipo_documento' => 'V',
                    'telefono' => '04140000000',
                    'correo' => $correo,
                    'clave' => '12345',
                    'id_rol' => 3,
                    'nivel' => 1
                ]
            ];

            $resultado = $this->usuario->procesarUsuario(json_encode($datosUsuario));
            fwrite(STDOUT, "\n → Intento #{$i} | Cedula: {$cedula} | Correo: {$correo}\n");
            // Validaciones generales
            $this->assertIsArray($resultado);
            $this->assertArrayHasKey('accion', $resultado);
            $this->assertEquals('incluir', $resultado['accion']);

            //  Registro exitoso
            if ($resultado['respuesta'] === 1) {
                fwrite(STDOUT, "  Registro exitoso.\n");
                $this->assertEquals(1, $resultado['respuesta']);
            // Error en el registro
            } elseif ($resultado['respuesta'] === 0) {
                fwrite(STDOUT, "   Error: " . $resultado['text'] . "\n");
                $this->assertArrayHasKey('text', $resultado);
                
                $this->assertContains(
                    $resultado['text'],
                    [
                        'La cédula ya está registrada',
                        'El correo electrónico ya está registrado',
                        'el rol no existe'
                    ],
                    "Mensaje inesperado en el intento #{$i}"
                );

            } else {
                $this->fail("Respuesta inesperada en el intento #{$i}");
            }
        }

        fwrite(STDOUT, "\n _-_-____ FIN DEL TEST DE REGISTRO MULTIPLE _-_-____n\n");
    }


}
?>