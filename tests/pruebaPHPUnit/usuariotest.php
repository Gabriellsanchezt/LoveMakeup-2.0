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

    public function testEjecutarEliminacion($datos) {
        return $this->invokePrivate('ejecutarEliminacion', [$datos]);
    }

    public function testVerificarExistencia($campo, $valor) {
        return $this->invokePrivate('verificarExistencia', [[
            'campo' => $campo,
            'valor' => $valor
        ]]);
    }

    public function testEjecutarRegistro($datos) {
        return $this->invokePrivate('ejecutarRegistro', [$datos]);
    }

    public function testEjecutarActualizacion($datos) {
        return $this->invokePrivate('ejecutarActualizacion', [$datos]);
    }

    public function testActualizarLotePermisos($lista) {
        return $this->invokePrivate('actualizarLotePermisos', [$lista]);
    }

    public function getUsuario(): Usuario {
        return $this->usuario;
    }
}


class UsuarioTest extends TestCase {
    private UsuarioTestable $usuario;

    protected function setUp(): void {
        $this->usuario = new UsuarioTestable();
    }

    public function testEncriptacionYDesencriptacion() {
        $claveOriginal = 'MiClave123';
        $claveEncriptada = $this->usuario->testEncrypt($claveOriginal);
        $claveDesencriptada = $this->usuario->testDecrypt($claveEncriptada);
        $this->assertEquals($claveOriginal, $claveDesencriptada);
    }

    public function testOperacionInvalida() {
        $usuarioReal = $this->usuario->getUsuario();
        $json = json_encode([
            'operacion' => 'desconocido',
            'datos' => []
        ]);
        $resultado = $usuarioReal->procesarUsuario($json);
        $this->assertEquals(0, $resultado['respuesta']);
        $this->assertEquals('Operación no válida', $resultado['mensaje']);
    }

    public function testConsultarDevuelveArray() {
        $resultado = $this->usuario->getUsuario()->consultar();
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_rol', $resultado[0]);
            $this->assertArrayHasKey('nombre_tipo', $resultado[0]);
            $this->assertArrayHasKey('nivel', $resultado[0]);
        }
    }

    public function testEliminarUsuarioExistente() {
        $datos = ['id_persona' => 20];
        $resultado = $this->usuario->testEjecutarEliminacion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('eliminar', $resultado['accion']);
    }

    public function testCedulaExistente() {
        $cedula = '3071651';
        $existe = $this->usuario->testVerificarExistencia('cedula', $cedula);
        $this->assertFalse($existe);
    }

    public function testCorreoInexistente() {
        $correo = 'danielsanchezcv@gmail.com';
        $existe = $this->usuario->testVerificarExistencia('correo', $correo);
        $this->assertFalse($existe);
    }

    public function testRegistroUsuarioNuevo() {
        $datos = [
            'cedula' => '30716541',
            'nombre' => 'danielsanc',
            'apellido' => 'Unitaria',
            'correo' => 'pruebaunitaria@gmail.com',
            'telefono' => '04149739941',
            'clave' => $this->usuario->testEncrypt('claveSegura123'),
            'id_rol' => 1,
            'nivel' => 3
        ];
        $resultado = $this->usuario->testEjecutarRegistro($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('incluir', $resultado['accion']);
    }

    public function testRegistroMasivoUsuarios() {
        for ($i = 1; $i <= 1; $i++) {
            $cedula = 30716541 + $i;
            $correo = "pruebaunitaria{$i}@gmail.com";
            $nombre = "danielsanc{$i}";
            $datos = [
                'cedula' => (string)$cedula,
                'nombre' => $nombre,
                'apellido' => 'Unitaria',
                'correo' => $correo,
                'telefono' => '04149739941',
                'clave' => $this->usuario->testEncrypt('claveSegura123'),
                'id_rol' => 1,
                'nivel' => 3
            ];
            $resultado = $this->usuario->testEjecutarRegistro($datos);
            $this->assertIsArray($resultado);
            $this->assertEquals(1, $resultado['respuesta']);
            $this->assertEquals('incluir', $resultado['accion']);
        }
    }

    public function testActualizarUsuarioExistente() {
        $datos = [
            'cedula' => '10200300',
            'correo' => 'actualizado@exampleee.com',
            'estatus' => 1,
            'id_rol' => 2,
            'id_persona' => 2,
            'insertar_permisos' => false,
            'nivel' => 3
        ];
        $resultado = $this->usuario->testEjecutarActualizacion($datos);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('actualizar', $resultado['accion']);
    }

    public function testBuscarPermisosPorId() {
        $id_persona = 2;
        $resultado = $this->usuario->getUsuario()->buscar($id_persona);
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('id_modulo', $resultado[0]);
            $this->assertArrayHasKey('nombre', $resultado[0]);
            $this->assertArrayHasKey('accion', $resultado[0]);
            $this->assertArrayHasKey('estado', $resultado[0]);
        }
    }

    public function testActualizarPermisosLote() {
        $lista = [];
        for ($i = 1; $i <= 10; $i++) {
            $lista[] = ['id_permiso' => $i, 'estado' => 1];
        }
        $resultado = $this->usuario->testActualizarLotePermisos($lista);
        $this->assertIsArray($resultado);
        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('actualizar_permisos', $resultado['accion']);
    }
}


?>