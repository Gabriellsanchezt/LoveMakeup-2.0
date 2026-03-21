<?php

namespace LoveMakeup\Tests;

use PHPUnit\Framework\TestCase;
use LoveMakeup\Proyecto\Modelo\VentaWeb;

class VentaWebTest extends TestCase
{
    private $ventaWeb;

    protected function setUp(): void
    {
        $this->ventaWeb = new VentaWeb();
    }

    private function imprimirResultado($testNombre, $estado, $mensaje = "")
    {
        $color = $estado ? "\e[0;32m[CORRECTO]\e[0m" : "\e[0;31m[ERROR/FALLÓ]\e[0m";
        fwrite(STDOUT, "\n$color - Test: $testNombre. $mensaje");
    }

    /**
     * Test de Caja Blanca: Inyección SQL
     */
    public function testSeguridadInyeccion()
    {
        $payload = "SELECT * FROM usuarios";
        $resultado = $this->ventaWeb->detectarInyeccionSQL($payload);
        
        if ($resultado === true) {
            $this->imprimirResultado("Detección SQLi", true, "El método detectó correctamente el patrón peligroso.");
        } else {
            $this->imprimirResultado("Detección SQLi", false, "El método NO detectó el patrón de inyección.");
        }
        
        $this->assertTrue($resultado);
    }

    /**
     * Test de Caja Blanca: Sanitización de Enteros
     */
    public function testSanitizacionLimites()
    {
        // Caso: Valor fuera de rango (max 10)
        $resultado = $this->ventaWeb->sanitizarEntero(15, 1, 10);
        
        if ($resultado === null) {
            $this->imprimirResultado("Sanitizar Entero", true, "El método bloqueó correctamente un valor fuera de rango.");
        } else {
            $this->imprimirResultado("Sanitizar Entero", false, "El método permitió un valor inválido.");
        }
        
        $this->assertNull($resultado);
    }

    /**
     * PRUEBA MASIVA: Inserción de múltiples registros únicos
     */
    public function testInsercionMasivaUnica()
    {
        $cantidad = 20; // Probaremos con 20 para no saturar la salida, puedes subirlo
        $exitosos = 0;

        fwrite(STDOUT, "\n\e[0;34m[INICIO]\e[0m Iniciando inserción masiva de $cantidad registros...");

        for ($i = 1; $i <= $cantidad; $i++) {
            $refUnica = "TEST-REF-" . bin2hex(random_bytes(3)) . "-" . $i;
            
            // Estructura de datos para procesarPedido
            $datos = [
                'datos' => [
                    'id_persona' => '2026' . $i,
                    'id_metodoentrega' => 1,
                    'direccion_envio' => 'Direccion de prueba ' . $i,
                    'sucursal_envio' => 'Sucursal ' . $i,
                    'id_delivery' => 1,
                    'tipo' => 'web',
                    'fecha' => date('Y-m-d'),
                    'estado' => 1,
                    'precio_total_usd' => 5.0,
                    'precio_total_bs' => 200.0,
                    'monto' => 200.0,
                    'monto_usd' => 5.0,
                    'id_metodopago' => 1,
                    'banco' => '0102-Banco De Venezuela',
                    'banco_destino' => '0105-Banco Mercantil',
                    'referencia_bancaria' => $refUnica, // ÚNICO
                    'telefono_emisor' => '04125555555',
                    'imagen' => '',
                    'carrito' => [
                        [
                            'id' => 2, // Asegúrate que este ID exista en tu tabla producto
                            'cantidad' => 1,
                            'cantidad_mayor' => 5,
                            'precio_detal' => 5.0,
                            'precio_mayor' => 4.0
                        ]
                    ]
                ]
            ];

            $resultado = $this->ventaWeb->procesarPedido(json_encode($datos));

            if ($resultado['success']) {
                $exitosos++;
            } else {
                $this->imprimirResultado("Registro Masivo #$i", false, "Error: " . $resultado['message']);
            }
        }

        if ($exitosos === $cantidad) {
            $this->imprimirResultado("Carga Masiva Total", true, "Se insertaron $exitosos registros únicos sin errores.");
        }
        
        $this->assertEquals($cantidad, $exitosos);
    }
}