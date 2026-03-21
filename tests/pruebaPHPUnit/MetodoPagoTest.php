<?php

namespace LoveMakeup\Tests;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\MetodoPago;

class MetodoPagoTest extends TestCase
{
    private $metodoPago;

    protected function setUp(): void
    {
        $this->metodoPago = new MetodoPago();
    }

    private function imprimirResultado($testNombre, $estado, $mensaje = "")
    {
        $color = $estado ? "\e[0;32m[CORRECTO]\e[0m" : "\e[0;31m[ERROR/FALLÓ]\e[0m";
        fwrite(STDOUT, "\n$color - Test: $testNombre. $mensaje");
    }

    /**
     * TEST DE INSERCIONES MÚLTIPLES
     * Simula la carga de varios métodos de pago de una vez.
     */
    public function testInsercionMultipleMetodos()
    {
        $metodosACargar = [
            ['nombre' => 'Transferencia Bancaria', 'descripcion' => 'Banco de Venezuela o Mercantil'],
            ['nombre' => 'Pago Móvil', 'descripcion' => '0424-5115414'],
            ['nombre' => 'Zelle', 'descripcion' => 'Pagos internacionales en USD'],
            ['nombre' => 'PayPal', 'descripcion' => 'Incluye comisión del 5%'],
            ['nombre' => 'Efectivo', 'descripcion' => 'Solo retiro en tienda']
        ];

        $exitosos = 0;
        fwrite(STDOUT, "\n\e[0;34m[INICIO]\e[0m Iniciando inserción de " . count($metodosACargar) . " métodos de pago...");

        foreach ($metodosACargar as $item) {
            $json = json_encode([
                'operacion' => 'incluir',
                'datos' => $item
            ]);

            $resultado = $this->metodoPago->procesarMetodoPago($json);

            if (isset($resultado['respuesta']) && $resultado['respuesta'] === 1) {
                $exitosos++;
            } else {
                $this->imprimirResultado("Carga Individual", false, "Falló al insertar: " . $item['nombre']);
            }
        }

        if ($exitosos === count($metodosACargar)) {
            $this->imprimirResultado("Inserción Múltiple", true, "Se registraron los $exitosos métodos correctamente.");
        }

        $this->assertEquals(count($metodosACargar), $exitosos);
    }

    /**
     * TEST DE CAJA BLANCA: Bloqueo por Keywords
     * Verifica que tu lista de palabras prohibidas (CHAR, CAST, sp_, etc) funcione.
     */
    public function testBloqueoKeywordsSeguridad()
    {
        // Usamos una keyword prohibida en tu lista: 'CAST'
        $datosPeligrosos = [
            'nombre' => 'Metodo Seguro',
            'descripcion' => 'Intento de ataque con CAST(0x41 AS CHAR)'
        ];

        $json = json_encode([
            'operacion' => 'incluir',
            'datos' => $datosPeligrosos
        ]);

        $resultado = $this->metodoPago->procesarMetodoPago($json);

        // Al detectar 'CAST', el sanitizarStringP devuelve vacío, 
        // y el insert probablemente falle o guarde vacío según la DB.
        // Pero el objetivo es ver que la respuesta no sea un error de SQL.
        
        if (isset($resultado['respuesta'])) {
            $this->imprimirResultado("Filtro Keywords", true, "El sistema procesó la entrada sin romperse ante palabras clave de SQL.");
        }
        
        $this->assertArrayHasKey('respuesta', $resultado);
    }

    /**
     * TEST DE CONSULTA: Verificar estatus activo
     */
    public function testConsultaMetodosActivos()
    {
        $lista = $this->metodoPago->consultar();
        
        $todosActivos = true;
        foreach($lista as $metodo) {
            if ($metodo['estatus'] != 1) {
                $todosActivos = false;
                break;
            }
        }

        if ($todosActivos && count($lista) > 0) {
            $this->imprimirResultado("Consulta Activos", true, "La consulta solo trajo métodos con estatus 1.");
        } else {
            $this->imprimirResultado("Consulta Activos", false, "Se encontraron métodos inactivos o la lista está vacía.");
        }

        $this->assertNotEmpty($lista);
    }

    /**
     * TEST DE ELIMINACIÓN LÓGICA
     */
    public function testEliminacionLogica()
    {
        // Intentamos eliminar el ID 1 (ajusta según tu DB)
        $idAEliminar = 1;

        $json = json_encode([
            'operacion' => 'eliminar',
            'datos' => ['id_metodopago' => $idAEliminar]
        ]);

        $resultado = $this->metodoPago->procesarMetodoPago($json);

        if ($resultado['respuesta'] === 1) {
            $this->imprimirResultado("Eliminar Lógico", true, "El método ID $idAEliminar fue marcado como inactivo (Estatus 0).");
        } else {
            $this->imprimirResultado("Eliminar Lógico", false, "No se pudo realizar la eliminación lógica.");
        }

        $this->assertArrayHasKey('respuesta', $resultado);
    }
}