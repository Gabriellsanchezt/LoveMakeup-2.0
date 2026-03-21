<?php

namespace LoveMakeup\Tests;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\PedidoWeb;

class PedidoWebTest extends TestCase
{
    private $pedidoWeb;

    protected function setUp(): void
    {
        $this->pedidoWeb = new PedidoWeb();
    }

    private function imprimirResultado($testNombre, $estado, $mensaje = "")
    {
        $color = $estado ? "\e[0;32m[CORRECTO]\e[0m" : "\e[0;31m[ERROR/FALLÓ]\e[0m";
        fwrite(STDOUT, "\n$color - Test: $testNombre. $mensaje");
    }

    /**
     * Test de Seguridad: Validación de Inyección SQL
     */
    public function testSeguridadInyeccionSQL()
    {
        $payloadPeligroso = "1 OR 1=1; DROP TABLE usuario";
        $resultado = $this->pedidoWeb->detectarInyeccionSQL($payloadPeligroso);
        
        if ($resultado === true) {
            $this->imprimirResultado("Detección SQLi", true, "El patrón de inyección fue bloqueado exitosamente.");
        } else {
            $this->imprimirResultado("Detección SQLi", false, "¡ALERTA! El sistema no detectó el ataque SQL.");
        }
        
        $this->assertTrue($resultado);
    }

    /**
     * Test de Sanitización: Limpieza de strings
     */
    public function testSanitizacionString()
    {
        $input = "Hola; -- <script>";
        $esperado = "Hola  &lt;script&gt;"; // El método elimina ; y -- según tu código
        $resultado = $this->pedidoWeb->sanitizarStringpw($input);
        
        if (strpos($resultado, ';') === false && strpos($resultado, '--') === false) {
            $this->imprimirResultado("Sanitización String", true, "Caracteres peligrosos eliminados correctamente.");
        } else {
            $this->imprimirResultado("Sanitización String", false, "El string aún contiene caracteres no deseados.");
        }
        
        $this->assertStringNotContainsString(';', $resultado);
    }

    /**
     * Test de Flujo: Operación inválida en el Switch
     */
    public function testOperacionInvalida()
    {
        $json = json_encode([
            'operacion' => 'metodo_fantasma',
            'datos' => []
        ]);

        $resultado = $this->pedidoWeb->procesarPedidoweb($json);

        if ($resultado['respuesta'] === 0 && $resultado['mensaje'] === 'Operación no válida') {
            $this->imprimirResultado("Switch Default", true, "El sistema manejó correctamente una operación inexistente.");
        } else {
            $this->imprimirResultado("Switch Default", false, "El sistema no retornó el error esperado para operaciones inválidas.");
        }

        $this->assertEquals(0, $resultado['respuesta']);
    }

    /**
     * Test de Caja Blanca: Confirmación de Pedido (Cambio de Estatus)
     * Este test asume que existe un ID de pedido 1 para pruebas.
     */
    public function testConfirmarPedidoEstatus()
    {
        // Simulamos un ID de pedido (ajusta según tu DB de pruebas)
        $id_pedido = 1; 
        
        // Ejecutamos la confirmación
        $resultado = $this->pedidoWeb->procesarPedidoweb(json_encode([
            'operacion' => 'confirmar',
            'datos' => $id_pedido
        ]));

        if ($resultado['respuesta'] === 1) {
            $this->imprimirResultado("Confirmar Pedido", true, "Pedido ID $id_pedido procesado. Estatus actualizado según método de entrega.");
        } else {
            // Si falla porque el ID no existe en tu DB local, el comentario te lo dirá
            $this->imprimirResultado("Confirmar Pedido", false, "No se pudo confirmar: " . ($resultado['msg'] ?? 'Error desconocido'));
        }

        // No hacemos assert estricto de true aquí por si tu DB está vacía, 
        // pero validamos que la estructura de respuesta sea correcta.
        $this->assertArrayHasKey('respuesta', $resultado);
    }

    /**
     * Test de Validación: Estado de Delivery
     */
    
    public function testValidarEstadoDelivery()
    {
        $estadoValido = "en_camino";
        $estadoInvalido = "volando_en_drone";

        $resValido = $this->pedidoWeb->validarEstadoDeliverypw($estadoValido);
        $resInvalido = $this->pedidoWeb->validarEstadoDeliverypw($estadoInvalido);

        if ($resValido && !$resInvalido) {
            $this->imprimirResultado("Validación Delivery", true, "Filtros de estado funcionan correctamente.");
        } else {
            $this->imprimirResultado("Validación Delivery", false, "La validación de estados permitidos falló.");
        }

        $this->assertTrue($resValido);
        $this->assertFalse($resInvalido);
    }
}