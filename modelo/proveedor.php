<?php

namespace LoveMakeup\Proyecto\Modelo;

use Dompdf\Dompdf;

use LoveMakeup\Proyecto\Config\Conexion;

class Proveedor extends Conexion {
 
    private $bitacoraObj;

    function __construct() {
        parent::__construct();
        $this->bitacoraObj = new Bitacora();
    }


    /**
     * Guarda una entrada en la bitácora para este módulo.
     * Retorna true si no hubo excepción, false en caso contrario.
     */
    public function registrarBitacora(string $jsonDatos): bool {
        $datos = json_decode($jsonDatos, true);
        try {
            $this->bitacoraObj->registrarOperacion(
                $datos['accion'],
                'proveedor',
                $datos
            );
            return true;
        } catch (\Throwable $e) {
            error_log('Bitacora fallo (proveedor): ' . $e->getMessage());
            return false;
        }
    }

    public function procesarProveedor(string $jsonDatos): array {
        $payload   = json_decode($jsonDatos, true);
        $operacion = $payload['operacion'] ?? '';
        $datos     = $payload['datos']    ?? [];

        try {
            switch ($operacion) {
                case 'registrar':
                    return $this->ejecutarRegistro($datos);
                case 'actualizar':
                    return $this->ejecutarActualizacion($datos);
                case 'eliminar':
                    return $this->ejecutarEliminacion($datos);
                default:
                    return ['respuesta'=>0, 'accion'=>$operacion, 'mensaje'=>'Operación inválida'];
            }
        } catch (\Exception $e) {
            return ['respuesta'=>0, 'accion'=>$operacion, 'mensaje'=>$e->getMessage()];
        }
    }

    // 3) Métodos privados de cada operación

    private function ejecutarRegistro(array $d): array {
        $conex = $this->getConex1();
        try {

            // VALIDACIÓN ESTRICTA DE CLAVES FORÁNEAS Y DATOS
            
            // Validar que los datos existan y sean válidos
            if (empty($d['numero_documento']) || empty($d['tipo_documento']) || 
                empty($d['nombre']) || empty($d['correo']) || 
                empty($d['telefono']) || empty($d['direccion'])) {
                throw new \Exception("Datos incompletos para registrar proveedor");
            }
            
            // Validación estricta del tipo de documento
            if (!in_array($d['tipo_documento'], ['V', 'J', 'E', 'G'], true)) {
                throw new \Exception("Tipo de documento inválido");
            }
            
            // Validación estricta del número de documento (solo números)
            if (!preg_match('/^[0-9]{7,9}$/', $d['numero_documento'])) {
                throw new \Exception("Número de documento inválido");
            }
            
            // Validación estricta del nombre
            if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,30}$/', $d['nombre'])) {
                throw new \Exception("Nombre inválido");
            }
            
            // Validación estricta del correo
            if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || 
                strlen($d['correo']) < 5 || strlen($d['correo']) > 60) {
                throw new \Exception("Correo electrónico inválido");
            }
            
            // Validación estricta del teléfono
            if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $d['telefono'])) {
                throw new \Exception("Teléfono inválido. Formato esperado: 0414-0000000");
            }
            
            // Validación estricta de la dirección
            if (strlen($d['direccion']) < 5 || strlen($d['direccion']) > 70) {
                throw new \Exception("Dirección inválida. Debe tener entre 5 y 70 caracteres");
            }
            
            // Verificar si ya existe un proveedor con el mismo número de documento
            $sqlCheck = "SELECT COUNT(*) FROM proveedor WHERE numero_documento = :numero_documento AND tipo_documento = :tipo_documento AND estatus = 1";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute([
                'numero_documento' => $d['numero_documento'],
                'tipo_documento' => $d['tipo_documento']
            ]);
            
            if ($stmtCheck->fetchColumn() > 0) {
                throw new \Exception("Ya existe un proveedor registrado con el mismo tipo y número de documento.");
            }
            
            $conex->beginTransaction();
            $sql = "INSERT INTO proveedor(numero_documento, tipo_documento, nombre, correo, telefono, direccion, estatus)
                    VALUES (:numero_documento, :tipo_documento, :nombre, :correo, :telefono, :direccion, 1)";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute($d);
            if ($ok) {
                $conex->commit();
                $conex = null;
                return ['respuesta'=>1, 'accion'=>'incluir', 'mensaje'=>'Proveedor registrado'];
            }
            $conex->rollBack();
            $conex = null;
            return ['respuesta'=>0, 'accion'=>'incluir', 'mensaje'=>'Error al registrar'];
        } catch (\PDOException $e) {
            if ($conex) { $conex->rollBack(); $conex = null; }
            throw $e;
        }
    }

    private function ejecutarActualizacion(array $d): array {
        $conex = $this->getConex1();
        try {
            
            // VALIDACIÓN ESTRICTA DE CLAVES FORÁNEAS Y DATOS
            
            // Validar que los datos existan y sean válidos
            if (empty($d['id_proveedor']) || empty($d['numero_documento']) || 
                empty($d['tipo_documento']) || empty($d['nombre']) || 
                empty($d['correo']) || empty($d['telefono']) || empty($d['direccion'])) {
                throw new \Exception("Datos incompletos para actualizar proveedor");
            }
            
            // Validación estricta del ID del proveedor (clave foránea lógica)
            if (!is_numeric($d['id_proveedor']) || (int)$d['id_proveedor'] <= 0) {
                throw new \Exception("ID de proveedor inválido");
            }
            
            // Validación estricta del tipo de documento
            if (!in_array($d['tipo_documento'], ['V', 'J', 'E', 'G'], true)) {
                throw new \Exception("Tipo de documento inválido");
            }
            
            // Validación estricta del número de documento (solo números)
            if (!preg_match('/^[0-9]{7,9}$/', $d['numero_documento'])) {
                throw new \Exception("Número de documento inválido");
            }
            
            // Validación estricta del nombre
            if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,30}$/', $d['nombre'])) {
                throw new \Exception("Nombre inválido");
            }
            
            // Validación estricta del correo
            if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || 
                strlen($d['correo']) < 5 || strlen($d['correo']) > 60) {
                throw new \Exception("Correo electrónico inválido");
            }
            
            // Validación estricta del teléfono
            if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $d['telefono'])) {
                throw new \Exception("Teléfono inválido. Formato esperado: 0414-0000000");
            }
            
            // Validación estricta de la dirección
            if (strlen($d['direccion']) < 5 || strlen($d['direccion']) > 70) {
                throw new \Exception("Dirección inválida. Debe tener entre 5 y 70 caracteres");
            }
            
            // Verificar si ya existe otro proveedor con el mismo número de documento
            $sqlCheck = "SELECT COUNT(*) FROM proveedor WHERE numero_documento = :numero_documento AND tipo_documento = :tipo_documento AND id_proveedor != :id_proveedor AND estatus = 1";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute([
                'numero_documento' => $d['numero_documento'],
                'tipo_documento' => $d['tipo_documento'],
                'id_proveedor' => $d['id_proveedor']
            ]);
            
            if ($stmtCheck->fetchColumn() > 0) {
                throw new \Exception("Ya existe otro proveedor registrado con el mismo tipo y número de documento.");
            }
            
            $conex->beginTransaction();
            $sql = "UPDATE proveedor SET
                        numero_documento = :numero_documento,
                        tipo_documento   = :tipo_documento,
                        nombre           = :nombre,
                        correo           = :correo,
                        telefono         = :telefono,
                        direccion        = :direccion
                    WHERE id_proveedor = :id_proveedor";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute($d);
            if ($ok) {
                $conex->commit();
                $conex = null;
                return ['respuesta'=>1, 'accion'=>'actualizar', 'mensaje'=>'Proveedor actualizado'];
            }
            $conex->rollBack();
            $conex = null;
            return ['respuesta'=>0, 'accion'=>'actualizar', 'mensaje'=>'Error al actualizar'];
        } catch (\PDOException $e) {
            if ($conex) { $conex->rollBack(); $conex = null; }
            throw $e;
        }
    }

    private function ejecutarEliminacion(array $d): array {
        $conex = $this->getConex1();
        try {
           
            // VALIDACIÓN ESTRICTA DE CLAVE FORÁNEA
            
            // Validar que el ID exista y sea válido
            if (empty($d['id_proveedor'])) {
                throw new \Exception("ID de proveedor requerido");
            }
            
            // Validación estricta del ID (debe ser numérico positivo)
            if (!is_numeric($d['id_proveedor']) || (int)$d['id_proveedor'] <= 0) {
                throw new \Exception("ID de proveedor inválido");
            }
            
            // Validación con expresión regular
            if (!preg_match('/^[0-9]+$/', (string)$d['id_proveedor'])) {
                throw new \Exception("ID de proveedor debe ser numérico");
            }
            
            // Verificar que el proveedor exista antes de eliminar (integridad referencial)
            $sqlCheck = "SELECT COUNT(*) FROM proveedor WHERE id_proveedor = :id_proveedor AND estatus = 1";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute([
                'id_proveedor' => $d['id_proveedor']
            ]);
            
            if ($stmtCheck->fetchColumn() == 0) {
                throw new \Exception("El proveedor seleccionado no existe o ya está desactivado");
            }
            $conex->beginTransaction();
            $sql = "UPDATE proveedor SET estatus = 0 WHERE id_proveedor = :id_proveedor";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute($d);
            if ($ok) {
                $conex->commit();
                $conex = null;
                return ['respuesta'=>1, 'accion'=>'eliminar', 'mensaje'=>'Proveedor eliminado'];
            }
            $conex->rollBack();
            $conex = null;
            return ['respuesta'=>0, 'accion'=>'eliminar', 'mensaje'=>'Error al eliminar'];
        } catch (\PDOException $e) {
            if ($conex) { $conex->rollBack(); $conex = null; }
            throw $e;
        }
    }

    // 4) Consultas "simples"
    
    public function consultar(): array {
        $conex = $this->getConex1();
        $sql   = "SELECT * FROM proveedor WHERE estatus = 1 ORDER BY id_proveedor DESC";
        $stmt  = $conex->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $conex = null;
        return $data;
    }

    public function consultarPorId(int $id): array {
        $conex = $this->getConex1();
        $sql   = "SELECT * FROM proveedor WHERE id_proveedor = :id_proveedor";
        $stmt  = $conex->prepare($sql);
        $stmt->execute(['id_proveedor'=>$id]);
        $row   = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        $conex  = null;
        return $row;
    }
}