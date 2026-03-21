<?php

namespace LoveMakeup\Tests\Modelo;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\MetodoEntrega;
use PDO;
use PDOStatement;

class MetodoEntregaTest extends TestCase {
    private $modelo;
    private $pdoMock;
    private $stmtMock;

    protected function setUp(): void {
        // Simulamos la conexión PDO
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);

        // Creamos una instancia del modelo
        // Nota: Asegúrate de que tu clase Conexion permita inyectar o mockear el PDO
        $this->modelo = $this->getMockBuilder(MetodoEntrega::class)
            ->onlyMethods(['getConex1'])
            ->getMock();

        $this->modelo->method('getConex1')->willReturn($this->pdoMock);
    }

    public function testRegistrarMetodoExitoso() {
        $nombre = "Envío Express";
        $descripcion = "Entrega en menos de 24 horas";

        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('prepare')
            ->with($this->stringContains("INSERT INTO metodo_entrega"))
            ->willReturn($this->stmtMock);
        
        $this->stmtMock->expects($this->once())->method('execute')
            ->with([
                'nombre' => $nombre,
                'descripcion' => $descripcion
            ])
            ->willReturn(true);

        $this->pdoMock->expects($this->once())->method('commit');

        $resultado = $this->modelo->procesarMetodoEntrega(json_encode([
            'operacion' => 'incluir',
            'datos' => [
                'nombre' => $nombre,
                'descripcion' => $descripcion
            ]
        ]));

        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('incluir', $resultado['accion']);
    }

    public function testDetectarInyeccionSQLBloqueaEntrada() {
        $entradaPeligrosa = "SELECT * FROM usuarios; --";
        
        $resultado = $this->modelo->sanitizarString($entradaPeligrosa);
        
        // El modelo debería devolver un string vacío al detectar keywords peligrosas
        $this->assertEquals('', $resultado);
    }

    public function testEliminarRealizaBorradoLogico() {
        $id = 5;

        $this->pdoMock->expects($this->once())->method('prepare')
            ->with($this->stringContains("SET estatus = 0"))
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())->method('execute')
            ->with(['id_entrega' => $id])
            ->willReturn(true);

        $resultado = $this->modelo->procesarMetodoEntrega(json_encode([
            'operacion' => 'eliminar',
            'datos' => ['id_entrega' => $id]
        ]));

        $this->assertEquals(1, $resultado['respuesta']);
        $this->assertEquals('eliminar', $resultado['accion']);
    }

    public function testSanitizarEnteroValidaRangos() {
        // Caso: valor no numérico
        $this->assertNull($this->modelo->sanitizarEntero("letras"));

        // Caso: valor fuera de rango máximo
        $this->assertNull($this->modelo->sanitizarEntero(100, 1, 50));

        // Caso: valor válido
        $this->assertEquals(25, $this->modelo->sanitizarEntero("25", 1, 50));
    }
}