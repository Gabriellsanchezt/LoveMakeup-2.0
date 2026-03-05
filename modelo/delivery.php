<?php

namespace LoveMakeup\Proyecto\Modelo;

use Dompdf\Dompdf;

use LoveMakeup\Proyecto\Config\Conexion;

class Delivery extends Conexion {
 
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
                'delivery',
                $datos
            );
            return true;
        } catch (\Throwable $e) {
            error_log('Bitacora fallo (delivery): ' . $e->getMessage());
            return false;
        }
    }

    public function procesarDelivery(string $jsonDatos): array {
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
                case 'cambiarEstatus':
                    return $this->cambiarEstatus($datos);
                default:
                    return ['respuesta'=>0, 'accion'=>$operacion, 'mensaje'=>'Operación inválida'];
            }
        } catch (\Exception $e) {
            return ['respuesta'=>0, 'accion'=>$operacion, 'mensaje'=>$e->getMessage()];
        }
    }

    //---------------------------------------------------
    // 3) Métodos privados de cada operación
    //---------------------------------------------------
    private function ejecutarRegistro(array $d): array {
        $conex = $this->getConex1();
        try {
            // ========================================
            // VALIDACIÓN ESTRICTA DE DATOS
            // ========================================
            
            // Validar que los datos existan y sean válidos
            if (empty($d['nombre']) || empty($d['tipo']) || 
                empty($d['contacto']) || empty($d['estatus'])) {
                throw new \Exception("Datos incompletos para registrar delivery");
            }
            
            // Validación estricta del nombre
            if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,50}$/', $d['nombre'])) {
                throw new \Exception("Nombre inválido");
            }
            
            // Validación estricta del tipo
            if (!in_array($d['tipo'], ['Carro', 'Moto', 'Bicicleta'], true)) {
                throw new \Exception("Tipo de vehículo inválido");
            }
            
            // Validación estricta del contacto (formato 0414-0000000: 4 dígitos, guion, 7 dígitos)
            if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $d['contacto'])) {
                throw new \Exception("Contacto inválido. El formato debe ser 0414-0000000");
            }
            
            // Validación estricta del estatus
            if (!in_array($d['estatus'], [1, 2], true)) {
                throw new \Exception("Estatus inválido");
            }
            
            // Verificar si ya existe un delivery con el mismo nombre
            $sqlCheck = "SELECT COUNT(*) FROM delivery WHERE nombre = :nombre AND estatus != 0";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute([
                'nombre' => $d['nombre']
            ]);
            
            if ($stmtCheck->fetchColumn() > 0) {
                throw new \Exception("Ya existe un delivery registrado con el mismo nombre.");
            }
            
            $conex->beginTransaction();
            $sql = "INSERT INTO delivery(nombre, tipo, contacto, estatus)
                    VALUES (:nombre, :tipo, :contacto, :estatus)";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute([
                'nombre' => $d['nombre'],
                'tipo' => $d['tipo'],
                'contacto' => $d['contacto'],
                'estatus' => $d['estatus']
            ]);
            if ($ok) {
                $conex->commit();
                $conex = null;
                return ['respuesta'=>1, 'accion'=>'incluir', 'mensaje'=>'Delivery registrado'];
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
            // ========================================
            // VALIDACIÓN ESTRICTA DE DATOS Y CLAVE FORÁNEA
            // ========================================
            
            // Validar que el ID exista y sea válido
            if (empty($d['id_delivery']) || !is_numeric($d['id_delivery'])) {
                throw new \Exception("ID de delivery inválido");
            }
            
            // Validar que el delivery existe en la base de datos
            $sqlCheck = "SELECT COUNT(*) FROM delivery WHERE id_delivery = :id_delivery AND estatus != 0";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['id_delivery' => $d['id_delivery']]);
            if ($stmtCheck->fetchColumn() == 0) {
                throw new \Exception("El delivery no existe");
            }
            
            // Validar que los datos existan y sean válidos
            if (empty($d['nombre']) || empty($d['tipo']) || 
                empty($d['contacto']) || empty($d['estatus'])) {
                throw new \Exception("Datos incompletos para actualizar delivery");
            }
            
            // Validación estricta del nombre
            if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,50}$/', $d['nombre'])) {
                throw new \Exception("Nombre inválido");
            }
            
            // Validación estricta del tipo
            if (!in_array($d['tipo'], ['Carro', 'Moto', 'Bicicleta'], true)) {
                throw new \Exception("Tipo de vehículo inválido");
            }
            
            // Validación estricta del contacto (formato 0414-0000000: 4 dígitos, guion, 7 dígitos)
            if (!preg_match('/^[0-9]{4}-[0-9]{7}$/', $d['contacto'])) {
                throw new \Exception("Contacto inválido. El formato debe ser 0414-0000000");
            }
            
            // Validación estricta del estatus
            if (!in_array($d['estatus'], [1, 2], true)) {
                throw new \Exception("Estatus inválido");
            }
            
            // Verificar si ya existe otro delivery con el mismo nombre
            $sqlCheck = "SELECT COUNT(*) FROM delivery WHERE nombre = :nombre AND id_delivery != :id_delivery AND estatus != 0";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute([
                'nombre' => $d['nombre'],
                'id_delivery' => $d['id_delivery']
            ]);
            
            if ($stmtCheck->fetchColumn() > 0) {
                throw new \Exception("Ya existe otro delivery registrado con el mismo nombre.");
            }
            
            $conex->beginTransaction();
            $sql = "UPDATE delivery SET
                        nombre = :nombre,
                        tipo = :tipo,
                        contacto = :contacto,
                        estatus = :estatus
                    WHERE id_delivery = :id_delivery";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute([
                'nombre' => $d['nombre'],
                'tipo' => $d['tipo'],
                'contacto' => $d['contacto'],
                'estatus' => $d['estatus'],
                'id_delivery' => $d['id_delivery']
            ]);
            if ($ok) {
                $conex->commit();
                $conex = null;
                return ['respuesta'=>1, 'accion'=>'actualizar', 'mensaje'=>'Delivery actualizado'];
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
            // ========================================
            // VALIDACIÓN ESTRICTA DE CLAVE FORÁNEA
            // ========================================
            
            // Validar que el ID exista y sea válido
            if (empty($d['id_delivery']) || !is_numeric($d['id_delivery'])) {
                throw new \Exception("ID de delivery inválido");
            }
            
            // Validar que el delivery existe en la base de datos
            $sqlCheck = "SELECT COUNT(*) FROM delivery WHERE id_delivery = :id_delivery AND estatus != 0";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['id_delivery' => $d['id_delivery']]);
            if ($stmtCheck->fetchColumn() == 0) {
                throw new \Exception("El delivery no existe o ya está eliminado");
            }
            
            $conex->beginTransaction();
            $sql = "UPDATE delivery SET estatus = 0 WHERE id_delivery = :id_delivery";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute($d);
            if ($ok) {
                $conex->commit();
                $conex = null;
                return ['respuesta'=>1, 'accion'=>'eliminar', 'mensaje'=>'Delivery eliminado'];
            }
            $conex->rollBack();
            $conex = null;
            return ['respuesta'=>0, 'accion'=>'eliminar', 'mensaje'=>'Error al eliminar'];
        } catch (\PDOException $e) {
            if ($conex) { $conex->rollBack(); $conex = null; }
            throw $e;
        }
    }

    private function cambiarEstatus(array $d): array {
        $conex = $this->getConex1();
        try {
            // ========================================
            // VALIDACIÓN ESTRICTA DE DATOS Y CLAVE FORÁNEA
            // ========================================
            
            // Validar que el ID exista y sea válido
            if (empty($d['id_delivery']) || !is_numeric($d['id_delivery'])) {
                throw new \Exception("ID de delivery inválido");
            }
            
            // Validar que el delivery existe en la base de datos
            $sqlCheck = "SELECT COUNT(*) FROM delivery WHERE id_delivery = :id_delivery";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['id_delivery' => $d['id_delivery']]);
            if ($stmtCheck->fetchColumn() == 0) {
                throw new \Exception("El delivery no existe");
            }
            
            // Validar que el estatus sea válido
            if (empty($d['estatus']) || !is_numeric($d['estatus'])) {
                throw new \Exception("Estatus inválido");
            }
            
            // Validación estricta del estatus
            if (!in_array($d['estatus'], [1, 2], true)) {
                throw new \Exception("Estatus inválido");
            }
            
            $conex->beginTransaction();
            $sql = "UPDATE delivery SET estatus = :estatus WHERE id_delivery = :id_delivery";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute($d);
            if ($ok) {
                $conex->commit();
                $conex = null;
                $estatusTexto = $d['estatus'] == 1 ? 'activado' : 'inactivado';
                return ['respuesta'=>1, 'accion'=>'cambiarEstatus', 'mensaje'=>"Delivery $estatusTexto"];
            }
            $conex->rollBack();
            $conex = null;
            return ['respuesta'=>0, 'accion'=>'cambiarEstatus', 'mensaje'=>'Error al cambiar estatus'];
        } catch (\PDOException $e) {
            if ($conex) { $conex->rollBack(); $conex = null; }
            throw $e;
        }
    }

    //---------------------------------------------------
    // 4) Consultas "simples"
    //---------------------------------------------------
    public function consultar(): array {
        $conex = $this->getConex1();
        $sql   = "SELECT * FROM delivery WHERE estatus != 0 ORDER BY id_delivery DESC";
        $stmt  = $conex->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $conex = null;
        return $data;
    }

    public function consultarPorId(int $id): array {
        $conex = $this->getConex1();
        $sql   = "SELECT * FROM delivery WHERE id_delivery = :id_delivery";
        $stmt  = $conex->prepare($sql);
        $stmt->execute(['id_delivery'=>$id]);
        $row   = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        $conex  = null;
        return $row;
    }
    
    public function consultarTodos(): array {
        $conex = $this->getConex1();
        $sql   = "SELECT * FROM delivery ORDER BY id_delivery DESC";
        $stmt  = $conex->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $conex = null;
        return $data;
    }

    public function consultarActivos() {
        $conex = $this->getConex1();
        $sql = "SELECT id_delivery, nombre , tipo, contacto
                FROM delivery 
                WHERE estatus = 1";

        $stmt = $conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


}