<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Marca extends Conexion {
    private $bitacoraObj;

    function __construct() {
        parent::__construct();
        $this->bitacoraObj = new Bitacora();
    }

    // --- MÉTODOS DE VALIDACIÓN Y SANITIZACIÓN ---

    private function detectarInyeccionSQL($valor) {
        if (empty($valor)) return false;
        $valor_lower = strtolower($valor);
        $patrones_peligrosos = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\bcreate\b.*\btable\b)/i',
            '/(\balter\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(\bsp_\w+)/i',
            '/(\bxp_\w+)/i',
            '/(--|\#|\/\*|\*\/)/',
            '/(\bor\b.*\b1\s*=\s*1\b)/i',
            '/(\band\b.*\b1\s*=\s*1\b)/i',
            '/(\bor\b.*\b1\s*=\s*0\b)/i',
            '/(\band\b.*\b1\s*=\s*0\b)/i',
            '/(\bwaitfor\b.*\bdelay\b)/i'
        ];

        foreach ($patrones_peligrosos as $patron) {
            if (preg_match($patron, $valor_lower)) {
                return true;
            }
        }
        return false;
    }

    private function sanitizarString($valor, $maxLength = 255) {
        if (empty($valor)) return '';
        if ($this->detectarInyeccionSQL($valor)) return '';
        $valor = trim($valor);
        $caracteres_peligrosos = [';', '--', '/*', '*/', '<', '>', '"', "'", '`'];
        foreach ($caracteres_peligrosos as $char) {
            $valor = str_replace($char, '', $valor);
        }
        if (strlen($valor) > $maxLength) $valor = substr($valor, 0, $maxLength);
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }

    private function sanitizarEntero($valor, $min = null, $max = null) {
        if (!is_numeric($valor)) return null;
        $valor = (int)$valor;
        if ($min !== null && $valor < $min) return null;
        if ($max !== null && $valor > $max) return null;
        return $valor;
    }

    
    public function registrarBitacora(string $jsonDatos): bool {
        $datos = json_decode($jsonDatos, true);
        try {
            $this->bitacoraObj->registrarOperacion(
                $datos['accion'],
                'marca', 
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
            
            if (empty($d['nombre'])) {
                throw new \Exception("El nombre de la marca no puede estar vacío.");
            }
            
          
            $nombre = $this->sanitizarString($d['nombre'], 100);
            
            // Verificar si ya existe una marca con el mismo nombre (ignorando mayúsculas/minúsculas)
            $sqlCheck = "SELECT COUNT(*) FROM marca WHERE LOWER(nombre) = LOWER(:nombre) AND estatus = 1";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['nombre' => $nombre]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new \Exception("Ya existe una marca con el nombre \"{$nombre}\".");
            }
            
            $conex->beginTransaction();

            $sql  = "INSERT INTO marca (nombre, estatus)
                     VALUES (:nombre, 1)";
            $stmt = $conex->prepare($sql);
            $ok   = $stmt->execute(['nombre'=>$nombre]);

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
            // Sanitizar ID
            $id_marca = $this->sanitizarEntero($d['id_marca'] ?? 0, 1);
            
            if ($id_marca === null) {
                throw new \Exception("ID de marca inválido.");
            }

            $conex->beginTransaction();

           
            $sqlCheck  = "SELECT COUNT(*) FROM marca WHERE id_marca = :id";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['id' => $id_marca]);
            $existe = $stmtCheck->fetchColumn();
            
            if ($existe == 0) {
                throw new \Exception("La marca con ID {$id_marca} no existe.");
            }
            
            
            $nombre = $this->sanitizarString($d['nombre'] ?? '', 100);
            
          
            $sqlCheckName = "SELECT COUNT(*) FROM marca WHERE LOWER(nombre) = LOWER(:nombre) AND id_marca != :id AND estatus = 1";
            $stmtCheckName = $conex->prepare($sqlCheckName);
            $stmtCheckName->execute([
                'nombre' => $nombre,
                'id' => $id_marca
            ]);
            if ($stmtCheckName->fetchColumn() > 0) {
                throw new \Exception("Ya existe otra marca con el nombre \"{$nombre}\".");
            }

            $sql  = "UPDATE marca
                     SET nombre = :nombre
                     WHERE id_marca = :id";
            $stmt= $conex->prepare($sql);
            $ok  = $stmt->execute([
                'id'     => $id_marca,
                'nombre' => $nombre
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
            // Sanitizar ID
            $id_marca = $this->sanitizarEntero($d['id_marca'] ?? 0, 1);
            
            if ($id_marca === null) {
                throw new \Exception("ID de marca inválido.");
            }

            $conex->beginTransaction();

            // Verificar si la marca existe antes de eliminar
            $sqlCheck  = "SELECT COUNT(*) FROM marca WHERE id_marca = :id";
            $stmtCheck = $conex->prepare($sqlCheck);
            $stmtCheck->execute(['id'=>$id_marca]);
            $existe = $stmtCheck->fetchColumn();
            
            if ($existe == 0) {
                throw new \Exception("La marca con ID {$id_marca} no existe.");
            }

            $sql  = "UPDATE marca
                     SET estatus = 0
                     WHERE id_marca = :id";
            $stmt= $conex->prepare($sql);
            $ok  = $stmt->execute(['id'=>$id_marca]);

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