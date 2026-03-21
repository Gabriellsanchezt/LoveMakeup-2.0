<?php

namespace Tests\PruebaPHPUnit;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\TipoUsuario;
use ReflectionClass;

/*||||||||||||||||||||||||||||||| CLASE TESTABLE CON REFLEXIÓN ||||||||||||||||||||||||||*/
class RolTestable {
    private Rol $rol;
    private ReflectionClass $reflection;

    public function __construct() {
        $this->rol = new rol();
        $this->reflection = new ReflectionClass($this->rol);
    }

    private function invokePrivate(string $method, array $args = []) {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($this->rol, $args);
    }

    
}

/*||||||||||||||||||||||||||||||| CLASE DE TEST PRINCIPAL ||||||||||||||||||||||||||||||*/
class RolTest extends TestCase {
    private TipoUsuario $TipoUsuario;
    protected function setUp(): void {
        $this->TipoUsuario = new TipoUsuario();
    }

    public function testRegistrarRol() {

        $datosRol = [
            'operacion' => 'registrar',
            'datos' => [
                'nombre' => 'RolTest',
                'nivel' => 2
            ]
        ];

        $resultado = $this->TipoUsuario->procesarRol(json_encode($datosRol));

        fwrite(STDOUT, "\n testRegistrarRol | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('accion', $resultado);
        $this->assertEquals('registrar', $resultado['accion']);

        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, " Rol registrado correctamente.\n");
            $this->assertEquals(1, $resultado['respuesta']);
        } else {
            fwrite(STDOUT, " Error al registrar rol: " . $resultado['text'] . "\n");
            $this->assertArrayHasKey('text', $resultado);
        }
    }

    /* ============================================================
       TEST: Actualizar Rol
    ============================================================ */
    public function testActualizarRol() {

        $datosRol = [
            'operacion' => 'actualizar',
            'datos' => [
                'id_rol' => 5,
                'nombre' => 'RolActualizado',
                'nivel' => 2,
                'nivel_actual' => 2
            ]
        ];

        $resultado = $this->TipoUsuario->procesarRol(json_encode($datosRol));

        fwrite(STDOUT, "\n testActualizarRol | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        $this->assertEquals('actualizar', $resultado['accion']);

        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, " Rol actualizado correctamente.\n");
            $this->assertEquals(1, $resultado['respuesta']);
        } else {
            fwrite(STDOUT, " Error al actualizar rol: " . $resultado['text'] . "\n");

            $this->assertContains(
                $resultado['text'],
                [
                    'No se pudo eliminar permisos',
                    'No Existe el tipo usuario'
                ]
            );
        }
    }

    public function testEliminarRol() {

        $datosRol = [
            'operacion' => 'eliminar',
            'datos' => [
                'id_rol' => 4
            ]
        ];

        $resultado = $this->TipoUsuario->procesarRol(json_encode($datosRol));

        fwrite(STDOUT, "\n testEliminarRol | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        $this->assertEquals('eliminar', $resultado['accion']);

        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "Rol eliminado correctamente.\n");
        } else {
            fwrite(STDOUT, "Error al eliminar rol: " . $resultado['text'] . "\n");
            $this->assertEquals('No Existe el tipo usuario', $resultado['text']);
        }
    }

    public function testActualizarPermisos() {

        $datosRol = [
            'operacion' => 'actualizar_permisos',
            'datos' => [
                ['id_permiso' => 1, 'estado' => 1],
                ['id_permiso' => 2, 'estado' => 1]
            ]
        ];

        $resultado = $this->TipoUsuario->procesarRol(json_encode($datosRol));
        fwrite(STDOUT, "\n testActualizarPermisos | Respuesta recibida.\n");

        $this->assertIsArray($resultado);
        $this->assertEquals('actualizar_permisos', $resultado['accion']);

        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, "Permisos actualizados correctamente.\n");
        } else {
            fwrite(STDOUT, "Error al actualizar permisos.\n");
        }
    }

    public function testVerificarRol() {

        $datosRol = [
            'operacion' => 'verificarrol',
            'datos' => [
                'id_rol' => 3
            ]
        ];

        $resultado = $this->TipoUsuario->procesarRol(json_encode($datosRol));
        fwrite(STDOUT, "\n testVerificarRol | Respuesta recibida.\n");
        $this->assertIsArray($resultado);
        $this->assertEquals('verifirol', $resultado['accion']);

        if ($resultado['respuesta'] === 1) {
            fwrite(STDOUT, " El rol existe.\n");
        } else {
            fwrite(STDOUT, " El rol NO existe: " . $resultado['text'] . "\n");
            $this->assertEquals('Error, no se encuentra un rol registrado', $resultado['text']);
        }
    }

     public function testConsultar() {

        $limite = 100;
        $resultado = $this->TipoUsuario->consultar($limite);

        fwrite(STDOUT, "\n testConsultar | Consulta ejecutada.\n");

        $this->assertIsArray($resultado);

        if (!empty($resultado)) {

            // Validamos claves que sí existen
            $this->assertArrayHasKey('id_rol', $resultado[0]);
            $this->assertArrayHasKey('estatus', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
     
        }
    }

    //----------------- MULTIPLES

    public function testRegistrarVariosRoles() {

        fwrite(STDOUT, "\n\n INICIO DEL TEST DE REGISTRO MULTIPLE DE ROLES  \n");

        $cantidad = 1000; 
        for ($i = 1; $i <= $cantidad; $i++) {

            $nombre = "RolTest_" . $i;
            $nivel = rand(2, 3); // Nivel aleatorio para variar

            $datosRol = [
                'operacion' => 'registrar',
                'datos' => [
                    'nombre' => $nombre,
                    'nivel' => $nivel
                ]
            ];

            $resultado = $this->TipoUsuario->procesarRol(json_encode($datosRol));
            fwrite(STDOUT, "\n → Intento #{$i} | Nombre: {$nombre} | Nivel: {$nivel}\n");
            $this->assertIsArray($resultado);

            if (!isset($resultado['accion'])) {
                fwrite(STDOUT, " Error interno: " . ($resultado['mensaje'] ?? 'Sin mensaje') . "\n");
                continue;
            }

            $this->assertEquals('registrar', $resultado['accion']);

            // Registro exitoso
            if ($resultado['respuesta'] === 1) {
                fwrite(STDOUT, " Rol registrado correctamente.\n");
                $this->assertEquals(1, $resultado['respuesta']);

            // Caso 2: Error esperado
            } elseif ($resultado['respuesta'] === 0) {

                fwrite(STDOUT, "Error al registrar rol: " . $resultado['text'] . "\n");

                $this->assertArrayHasKey('text', $resultado);

                $this->assertContains(
                    $resultado['text'],
                    [
                        'El nombre ya existe',
                        'El nivel ya existe',
                        'Error al registrar rol'
                    ],
                    "Mensaje inesperado en el intento #{$i}"
                );

            } else {
                $this->fail("Respuesta inesperada en el intento #{$i}");
            }
        }

        fwrite(STDOUT, "\n=== FIN DEL TEST DE REGISTRO MULTIPLE DE ROLES ===\n\n");
    }

}
