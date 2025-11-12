<?php

namespace Tests\ConfigSelenium;

use DOMDocument;
use DOMElement;

/**
 * Clase para generar reportes compatibles con TestLink
 */
class TestLinkReporter
{
    /**
     * @var array Resultados de las pruebas
     */
    private $testResults = [];
    
    /**
     * @var string Ruta del archivo de reporte
     */
    private $reportPath;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reportPath = SeleniumConfig::TESTLINK_REPORT_PATH;
        
        // Crear directorio si no existe
        if (!is_dir($this->reportPath)) {
            mkdir($this->reportPath, 0777, true);
        }
    }
    
    /**
     * Agregar resultado de prueba
     * 
     * @param string $testCaseId ID del caso de prueba en TestLink
     * @param string $testCaseName Nombre del caso de prueba
     * @param string $result Resultado: 'p' (passed), 'f' (failed), 'b' (blocked)
     * @param string $notes Notas adicionales
     */
    public function addTestResult(
        string $testCaseId,
        string $testCaseName,
        string $result,
        string $notes = ''
    ): void {
        $this->testResults[] = [
            'testcase_id' => $testCaseId,
            'testcase_name' => $testCaseName,
            'result' => $result,
            'notes' => $notes,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generar reporte XML para TestLink
     * 
     * @param string $buildName Nombre del build en TestLink
     * @param string $platform Nombre de la plataforma
     * @return string Ruta del archivo generado
     */
    public function generateXMLReport(
        string $buildName = 'Automated Build',
        string $platform = 'Web'
    ): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        // Elemento raíz
        $results = $dom->createElement('results');
        $dom->appendChild($results);
        
        // Agregar cada resultado de prueba
        foreach ($this->testResults as $testResult) {
            $testcase = $dom->createElement('testcase');
            $testcase->setAttribute('name', $testResult['testcase_name']);
            $testcase->setAttribute('internalid', $testResult['testcase_id']);
            
            // Resultado
            $result = $dom->createElement('result');
            $result->setAttribute('status', $this->mapResultToStatus($testResult['result']));
            $testcase->appendChild($result);
            
            // Notas
            if (!empty($testResult['notes'])) {
                $notes = $dom->createElement('notes', htmlspecialchars($testResult['notes']));
                $testcase->appendChild($notes);
            }
            
            // Timestamp
            $timestamp = $dom->createElement('timestamp', $testResult['timestamp']);
            $testcase->appendChild($timestamp);
            
            $results->appendChild($testcase);
        }
        
        // Guardar archivo
        $filename = 'testlink_report_' . date('Y-m-d_H-i-s') . '.xml';
        $filepath = $this->reportPath . $filename;
        $dom->save($filepath);
        
        return $filepath;
    }
    
    /**
     * Generar reporte en formato TestLink API
     * 
     * @param string $testPlanId ID del plan de pruebas en TestLink
     * @param string $buildName Nombre del build
     * @return array Datos para la API de TestLink
     */
    public function generateAPIReport(
        string $testPlanId,
        string $buildName
    ): array {
        $report = [
            'testplanid' => $testPlanId,
            'buildname' => $buildName,
            'testcases' => []
        ];
        
        foreach ($this->testResults as $testResult) {
            $report['testcases'][] = [
                'testcaseid' => $testResult['testcase_id'],
                'testcasename' => $testResult['testcase_name'],
                'status' => $this->mapResultToStatus($testResult['result']),
                'notes' => $testResult['notes']
            ];
        }
        
        return $report;
    }
    
    /**
     * Generar reporte JSON
     * 
     * @return string Ruta del archivo generado
     */
    public function generateJSONReport(): string
    {
        $filename = 'testlink_report_' . date('Y-m-d_H-i-s') . '.json';
        $filepath = $this->reportPath . $filename;
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_tests' => count($this->testResults),
            'passed' => count(array_filter($this->testResults, fn($r) => $r['result'] === 'p')),
            'failed' => count(array_filter($this->testResults, fn($r) => $r['result'] === 'f')),
            'blocked' => count(array_filter($this->testResults, fn($r) => $r['result'] === 'b')),
            'results' => $this->testResults
        ];
        
        file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return $filepath;
    }
    
    /**
     * Mapear resultado a estado de TestLink
     * 
     * @param string $result 'p', 'f', o 'b'
     * @return string Estado de TestLink
     */
    private function mapResultToStatus(string $result): string
    {
        $mapping = [
            'p' => 'p',  // passed
            'f' => 'f',  // failed
            'b' => 'b'   // blocked
        ];
        
        return $mapping[$result] ?? 'b';
    }
    
    /**
     * Limpiar resultados
     */
    public function clearResults(): void
    {
        $this->testResults = [];
    }
    
    /**
     * Obtener resumen de resultados
     */
    public function getSummary(): array
    {
        return [
            'total' => count($this->testResults),
            'passed' => count(array_filter($this->testResults, fn($r) => $r['result'] === 'p')),
            'failed' => count(array_filter($this->testResults, fn($r) => $r['result'] === 'f')),
            'blocked' => count(array_filter($this->testResults, fn($r) => $r['result'] === 'b'))
        ];
    }
}

