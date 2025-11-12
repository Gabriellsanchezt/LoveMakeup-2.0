<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Marca extends Conexion {
    private $bitacoraObj;
    function __construct() {
        parent::__construct();
        $this->bitacoraObj = new Bitacora();
    }

        public function registrarBitacora(string $jsonDatos): bool {
        $datos = json_decode($jsonDatos, true);
        try {
            $this->bitacoraObj->registrarOperacion(
                $datos['accion'],
                'marca',  // nombre del módulo
                $datos
            );
            return true;
        } catch (\Throwable $e) {
            error_log('Bitacora fallo (marca): ' . $e->getMessage());
            return false;
        }
    }

    // 2) Router JSON → CRUD
    public function procesarMarca(string $jsonDatos): array {
        $payload   = json_decode($jsonDatos, true);
        $operacion = $payload['operacion'] ?? ''; 
        $d         = $payload['datos']    ?? [];

        try {
            switch ($operacion) {
                case 'incluir':    return $this->insertar($d);
                case 'actualizar': return $this->actualizar($d);
                case 'eliminar':   return $this->eliminarLogico($d);
                default:
                    return [
                      'respuesta'=>0,
                      'accion'   =>$operacion,
                      'mensaje'  =>'Operación no válida'
                    ];
            }
        } catch (\PDOException $e) {
            return [
              'respuesta'=>0,
              'accion'   =>$operacion,
              'mensaje'  =>$e->getMessage()
            ];
        } catch (\Exception $e) {
            // Manejar excepciones personalizadas
            return [
              'respuesta'=>0,
              'accion'   =>$operacion,
              'mensaje'  =>$e->getMessage()
            ];
        }
    }

    // 3a) Incluir
    private function insertar(array $d): array {
        $conex = $this->getConex1();
        try {
            // Validar que el nombre no esté vacío
            if (empty($d['nombre'])) {
                throw new \Exception("El nombre de la marca no puede estar vacío.");
            }
            
            // Verificar si ya existe una marca con el mismo nombre (ignorando mayúsculas/minúsculas)
            $sqlCheck = "SELECT COUNT(*) FROM marca WHERE LOWER(nombre) = LOWER(:nombre) AND estatus = 1";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['nombre' => $d['nombre']]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new \Exception("Ya existe una marca con el nombre \"{$d['nombre']}\".");
            }
            
            $conex->beginTransaction();

            $sql  = "INSERT INTO marca (nombre, estatus)
                     VALUES (:nombre, 1)";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute(['nombre'=>$d['nombre']]);

            if ($ok) {
                $conex->commit();
                $respuesta = ['respuesta'=>1,'accion'=>'incluir','mensaje'=>'marca creada'];
            } else {
                $conex->rollBack();
                $respuesta = ['respuesta'=>0,'accion'=>'incluir','mensaje'=>'Error al crear'];
            }
            $conex = null;
            return $respuesta;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    // 3b) Actualizar
    private function actualizar(array $d): array {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();

            // Verificar si la marca existe antes de actualizar
            $sqlCheck  = "SELECT COUNT(*) FROM marca WHERE id_marca = :id";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['id' => $d['id_marca']]);
            $existe = $stmtCheck->fetchColumn();
            
            if ($existe == 0) {
                throw new \Exception("La marca con ID {$d['id_marca']} no existe.");
            }
            
            // Verificar si ya existe otra marca con el mismo nombre (ignorando mayúsculas/minúsculas)
            $sqlCheckName = "SELECT COUNT(*) FROM marca WHERE LOWER(nombre) = LOWER(:nombre) AND id_marca != :id AND estatus = 1";
            $stmtCheckName = $conex->prepare($sqlCheckName);
            $stmtCheckName->execute([
                'nombre' => $d['nombre'],
                'id' => $d['id_marca']
            ]);
            if ($stmtCheckName->fetchColumn() > 0) {
                throw new \Exception("Ya existe otra marca con el nombre \"{$d['nombre']}\".");
            }

            $sql  = "UPDATE marca
                     SET nombre = :nombre
                     WHERE id_marca = :id";
            $stmt= $conex->prepare($sql);
            $ok  = $stmt->execute([
                'id'     => $d['id_marca'],
                'nombre' => $d['nombre']
            ]);

            if ($ok) {
                $conex->commit();
                $respuesta = ['respuesta'=>1,'accion'=>'actualizar','mensaje'=>'Marca modificada'];
            } else {
                $conex->rollBack();
                $respuesta = ['respuesta'=>0,'accion'=>'actualizar','mensaje'=>'Error al modificar'];
            }
            $conex = null;
            return $respuesta;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    // 3c) Eliminar lógico
    private function eliminarLogico(array $d): array {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();

            // Verificar si la marca existe antes de eliminar
            $sqlCheck  = "SELECT COUNT(*) FROM marca WHERE id_marca = :id";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['id'=>$d['id_marca']]);
            $existe = $stmtCheck->fetchColumn();
            
            if ($existe == 0) {
                throw new \Exception("La marca con ID {$d['id_marca']} no existe.");
            }

            $sql  = "UPDATE marca
                     SET estatus = 0
                     WHERE id_marca = :id";
            $stmt= $conex->prepare($sql);
            $ok  = $stmt->execute(['id'=>$d['id_marca']]);

            if ($ok) {
                $conex->commit();
                $respuesta = ['respuesta'=>1,'accion'=>'eliminar','mensaje'=>'marca eliminada'];
            } else {
                $conex->rollBack();
                $respuesta = ['respuesta'=>0,'accion'=>'eliminar','mensaje'=>'Error al eliminar'];
            }
            $conex = null;
            return $respuesta;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    // 4) Consultar (listado)
    public function consultar(): array {
        $conex = $this->getConex1();
        try {
            $sql   = "SELECT id_marca, nombre
                      FROM marca
                      WHERE estatus = 1
                      ORDER BY id_marca DESC";
            $stmt  = $conex->prepare($sql);
            $stmt->execute();
            $datos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex = null;
            return $datos;
        } catch (\PDOException $e) {
            if ($conex) $conex = null;
            throw $e;
        }
    }
}