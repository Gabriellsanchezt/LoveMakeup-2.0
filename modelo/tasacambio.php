<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

/*||||||||||||||||||||||||||||||| TOTAL DE METODOS =  |||||||||||||||||||||||||  04  |||||*/    

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
            switch ($operacion) {
                case 'modificar':
                    
                   if ($this->verificarFechaNoExiste($datosProcesar['fecha'])) {
                         return $this->ejecutarRegistro2($datosProcesar);
                    }
    
                    return $this->ejecutarMofidicacion($datosProcesar);
                
                case 'sincronizar':
                    if ($this->verificarFechaNoExiste($datosProcesar['fecha'])) {
                         return $this->ejecutarRegistro($datosProcesar);
                    }

                    return $this->ejecutarMofidicacion($datosProcesar);
                default:
                    return ['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
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

/*||||||||||||||||||||||||||||||| ACTUALIZAR DATOS DEL CLIENTE  |||||||||||||||||||||||||  03  |||||*/    
   private function ejecutarMofidicacion($datos) {
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
        return ['respuesta' => 1, 'accion' => 'modificar'];

    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        return ['respuesta' => 0, 'text' => $e->getMessage()];
    }
}


/*||||||||||||||||||||||||||||||| VERIFICAR CEDULA Y CORREO  |||||||||||||||||||||||||  04  |||||*/        
private function verificarFechaNoExiste($fecha) {
    $conex = $this->getConex1();
    try {
        $conex->beginTransaction();

        $sql = "SELECT COUNT(*) FROM tasa_dolar WHERE fecha = :fecha";
        $stmt = $conex->prepare($sql);
        $stmt->execute(['fecha' => $fecha]);

        $noExiste = $stmt->fetchColumn() == 0;

        $conex->commit();
        $conex = null;
        return $noExiste;
    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        throw $e;
    }
}

private function ejecutarRegistro($datos) {
    $conex = $this->getConex1();
  
    try {
        $conex->beginTransaction();

        $sql = "INSERT tasa_dolar (fecha, tasa_bs, fuente, estatus)
                         VALUES (:fecha,:tasa,:fuente, 1)";

        $parametros = [
            'tasa' => $datos['tasa'],
            'fuente' => $datos['fuente'],
            'fecha' => $datos['fecha']
        ];

        $stmt = $conex->prepare($sql);
        $stmt->execute($parametros);

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'sincronizar'];

    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        return ['respuesta' => 0, 'text' => $e->getMessage()];
    }
}

private function ejecutarRegistro2($datos) {
    $conex = $this->getConex1();
  
    try {
        $conex->beginTransaction();

        $sql = "INSERT tasa_dolar (fecha, tasa_bs, fuente, estatus)
                         VALUES (:fecha,:tasa,:fuente, 1)";

        $parametros = [
            'tasa' => $datos['tasa'],
            'fuente' => $datos['fuente'],
            'fecha' => $datos['fecha']
        ];

        $stmt = $conex->prepare($sql);
        $stmt->execute($parametros);

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'modificar'];

    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        return ['respuesta' => 0, 'text' => $e->getMessage()];
    }
}
}
