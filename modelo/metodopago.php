<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class MetodoPago extends Conexion {
    public function __construct() {
        parent::__construct(); 
    }

    // --- VALIDACIONES ---
    private function detectarInyeccionSQL($valor) {
        if (empty($valor)) return false;
        $valor_lower = strtolower($valor);
        $patrones_peligrosos = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(--|\#|\/\*|\*\/)/',
            '/(\bor\b.*\b1\s*=\s*1\b)/i',
            '/(\bdrop\b|\btruncate\b|\balter\b)\s+\btable\b/i'
        ];

        foreach ($patrones_peligrosos as $patron) {
            if (preg_match($patron, $valor_lower)) return true;
        }
    
        // key especificas 
        $keywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER',
            'CREATE', 'RENAME', 'REPLACE', 'UNION', 'JOIN', 'WHERE', 'HAVING',
            'FROM', 'TABLE', 'DATABASE', 'SCHEMA', 'GRANT', 'REVOKE',
            '--', ';', '#', '/*', '*/', '@@', '@', 'CHAR', 'CAST', 'CONVERT',
            'EXEC', 'EXECUTE', 'xp_', 'sp_'
        ];
    
        // Verificar Key
        foreach ($keywords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            if (preg_match($pattern, $valor_lower)) {
                return true;
            }
        }
    
        return false;
    
    }

    public function sanitizarStringP($valor, $maxLength = 255) {
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

    public function sanitizarEnteroP($valor, $min = null, $max = null) {
        if (!is_numeric($valor)) return null;
        $valor = (int)$valor;
        if ($min !== null && $valor < $min) return null;
        if ($max !== null && $valor > $max) return null;
        return $valor;
    }

   
    public function procesarMetodoPago($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'] ?? '';
        $datosProcesar = $datos['datos'] ?? [];

        try {
            switch ($operacion) {
                case 'incluir':
                    return $this->registrar(
                        $this->sanitizarStringP($datosProcesar['nombre']),
                        $this->sanitizarStringP($datosProcesar['descripcion'])
                    );
                case 'modificar':
                    return $this->modificar(
                        $this->sanitizarEnteroP($datosProcesar['id_metodopago'], 1),
                        $this->sanitizarStringP($datosProcesar['nombre']),
                        $this->sanitizarStringP($datosProcesar['descripcion'])
                    );
                case 'eliminar':
                    return $this->eliminar($this->sanitizarEnteroP($datosProcesar['id_metodopago'], 1));
                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'accion' => $operacion, 'error' => $e->getMessage()];
        }
    }
    private function registrar($nombre, $descripcion) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            $sql = "INSERT INTO metodo_pago(nombre, descripcion, estatus) VALUES (:nombre, :descripcion, 1)";
            $stmt = $conex->prepare($sql);
            $result = $stmt->execute([
                'nombre' => $nombre,
                'descripcion' => $descripcion
            ]);
            $conex->commit();
            return $result ? ['respuesta' => 1, 'accion' => 'incluir'] : ['respuesta' => 0, 'accion' => 'incluir'];
        } catch (\PDOException $e) {
            $conex->rollBack();
            throw $e;
        }
    }

    private function modificar($id_metodopago, $nombre, $descripcion) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            $sql = "UPDATE metodo_pago SET nombre = :nombre, descripcion = :descripcion WHERE id_metodopago = :id_metodopago";
            $stmt = $conex->prepare($sql);
            $result = $stmt->execute([
                'id_metodopago' => $id_metodopago,
                'nombre' => $nombre,
                'descripcion' => $descripcion
            ]);
            $conex->commit();
            return $result ? ['respuesta' => 1, 'accion' => 'actualizar'] : ['respuesta' => 0, 'accion' => 'actualizar'];
        } catch (\PDOException $e) {
            $conex->rollBack();
            throw $e;
        }
    }

    private function eliminar($id_metodopago) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            $sql = "UPDATE metodo_pago SET estatus = 0 WHERE id_metodopago = :id_metodopago";
            $stmt = $conex->prepare($sql);
            $result = $stmt->execute(['id_metodopago' => $id_metodopago]);
            $conex->commit();
            return $result ? ['respuesta' => 1, 'accion' => 'eliminar'] : ['respuesta' => 0, 'accion' => 'eliminar'];
        } catch (\PDOException $e) {
            $conex->rollBack();
            throw $e;
        }
    }

    public function consultar() {
        $sql = "SELECT * FROM metodo_pago WHERE estatus = 1";
        $stmt = $this->getConex1()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerMetodos() {
        $stmt = $this->getConex1()->prepare(
            "SELECT * FROM metodo_pago WHERE estatus = 1 AND id_metodopago = 1"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>
