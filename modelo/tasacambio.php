<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

/*||||||||||||||||||||||||||||||| TOTAL DE METODOS =  |||||||||||||||||||||||||  05  |||||*/    

class Tasacambio extends Conexion
{

    function __construct() {
        parent::__construct(); // Llama al constructor de la clase padre
    }

/*||||||||||||||||||||||||||||||| OPERACIONES  |||||||||||||||||||||||||  01  |||||*/        
    public function procesarTasa($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];
        
        try {
            // Validar y sanitizar datos
            $datosProcesar['fecha'] = filter_var($datosProcesar['fecha'], FILTER_SANITIZE_STRING);
            $datosProcesar['tasa'] = filter_var($datosProcesar['tasa'], FILTER_VALIDATE_FLOAT);
            $datosProcesar['fuente'] = filter_var($datosProcesar['fuente'], FILTER_SANITIZE_STRING);
            
            if ($datosProcesar['tasa'] === false || $datosProcesar['tasa'] <= 0) {
                return ['respuesta' => 0, 'accion' => $operacion, 'text' => 'La tasa debe ser un número válido mayor a 0'];
            }
            
            switch ($operacion) {
                case 'modificar':
                case 'sincronizar':
                    if ($this->verificarFechaNoExiste($datosProcesar['fecha'])) {
                        return $this->ejecutarRegistro($datosProcesar, $operacion);
                    }
                    return $this->ejecutarMofidicacion($datosProcesar, $operacion);
                    
                default:
                    return ['respuesta' => 0, 'accion' => $operacion, 'text' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'accion' => $operacion ?? 'desconocida', 'text' => $e->getMessage()];
        }
    }

/*||||||||||||||||||||||||||||||| CONSULTAR DATOS  |||||||||||||||||||||||||  02  |||||*/        
        public function consultar() {
            $conex = $this->getConex1();
            try {
                $sql = "SELECT * FROM tasa_dolar ORDER BY fecha DESC LIMIT 5";
                        
                $stmt = $conex->prepare($sql);
                $stmt->execute();
                $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $conex = null;
                return $resultado;
            } catch (\PDOException $e) {
                if ($conex) {
                    $conex = null;
                }
                throw $e;
            }
        }

/*||||||||||||||||||||||||||||||| OBTENER TASA ACTUAL  |||||||||||||||||||||||||  05  |||||*/        
        public function obtenerTasaActual() {
            $conex = $this->getConex1();
            try {
                // Obtener la tasa más reciente activa
                $sql = "SELECT tasa_bs, fecha, fuente 
                        FROM tasa_dolar 
                        WHERE estatus = 1 
                        ORDER BY fecha DESC 
                        LIMIT 1";
                        
                $stmt = $conex->prepare($sql);
                $stmt->execute();
                $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
                $conex = null;
                return $resultado;
            } catch (\PDOException $e) {
                if ($conex) {
                    $conex = null;
                }
                throw $e;
            }
        }

/*||||||||||||||||||||||||||||||| ACTUALIZAR DATOS DE TASA  |||||||||||||||||||||||||  03  |||||*/    
   private function ejecutarMofidicacion($datos, $operacion) {
    $conex = $this->getConex1();
  
    try {
        $conex->beginTransaction();

        $sql = "UPDATE tasa_dolar 
                       SET tasa_bs = :tasa, 
                           fuente = :fuente 
                       WHERE fecha = :fecha";

        $parametros = [
            'tasa' => $datos['tasa'],
            'fuente' => $datos['fuente'],
            'fecha' => $datos['fecha']
        ];

        $stmt = $conex->prepare($sql);
        $stmt->execute($parametros);

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => $operacion];

    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        return ['respuesta' => 0, 'accion' => $operacion, 'text' => 'Error al actualizar: ' . $e->getMessage()];
    }
}


/*||||||||||||||||||||||||||||||| VERIFICAR FECHA NO EXISTE  |||||||||||||||||||||||||  04  |||||*/        
private function verificarFechaNoExiste($fecha) {
    $conex = $this->getConex1();
    try {
        $sql = "SELECT COUNT(*) FROM tasa_dolar WHERE fecha = :fecha";
        $stmt = $conex->prepare($sql);
        $stmt->execute(['fecha' => $fecha]);

        $noExiste = $stmt->fetchColumn() == 0;
        $conex = null;
        return $noExiste;
    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        throw $e;
    }
}

/*||||||||||||||||||||||||||||||| REGISTRAR NUEVA TASA  |||||||||||||||||||||||||  05  |||||*/
private function ejecutarRegistro($datos, $operacion) {
    $conex = $this->getConex1();
  
    try {
        $conex->beginTransaction();

        $sql = "INSERT INTO tasa_dolar (fecha, tasa_bs, fuente, estatus)
                         VALUES (:fecha, :tasa, :fuente, 1)";

        $parametros = [
            'tasa' => $datos['tasa'],
            'fuente' => $datos['fuente'],
            'fecha' => $datos['fecha']
        ];

        $stmt = $conex->prepare($sql);
        $stmt->execute($parametros);

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => $operacion];

    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        return ['respuesta' => 0, 'accion' => $operacion, 'text' => 'Error al registrar: ' . $e->getMessage()];
    }
}


public function consultaTasaUltima() {
    $conex = $this->getConex1();
    try {
        $sql = "SELECT tasa_bs FROM tasa_dolar ORDER BY fecha DESC LIMIT 1";
        $stmt = $conex->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetchColumn();
        $conex = null;
        return $resultado;
    } catch (\PDOException $e) {
        if ($conex) {
            $conex = null;
        }
        throw $e;
    }
}
}
