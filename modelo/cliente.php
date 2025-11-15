<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

/*||||||||||||||||||||||||||||||| TOTAL DE METODOS =  |||||||||||||||||||||||||  04  |||||*/    

class Cliente extends Conexion
{

    function __construct() {
        parent::__construct(); // Llama al constructor de la clase padre
    }

/*||||||||||||||||||||||||||||||| OPERACIONES  |||||||||||||||||||||||||  01  |||||*/        
    public function procesarCliente($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];
        
        try {
            switch ($operacion) {
                case 'actualizar':
                    
                    // Verifica si cambió la cédula antes de validar existencia
                    if ($datosProcesar['cedula'] !== $datosProcesar['cedula_actual']) {
                        if ($this->verificarExistencia(['campo' => 'cedula', 'valor' => $datosProcesar['cedula']])) {
                            return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'La cédula ya está registrada'];
                        }
                    }

                    // Verifica si cambió el correo antes de validar existencia
                    if ($datosProcesar['correo'] !== $datosProcesar['correo_actual']) {
                        if ($this->verificarExistencia(['campo' => 'correo', 'valor' => $datosProcesar['correo']])) {
                            return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El correo electrónico ya está registrado'];
                        }
                    }

                    return $this->ejecutarActualizacion($datosProcesar);
                                        
                default:
                    return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }

/*||||||||||||||||||||||||||||||| CONSULTAR DATOS  |||||||||||||||||||||||||  02  |||||*/        
        public function consultar() {
            $conex = $this->getConex2();
            try {
                $sql = "SELECT 
                        per.*, 
                        ru.id_rol, 
                        ru.nombre AS nombre_tipo, 
                        ru.nivel,
                        u.id_usuario,
                        u.estatus
                    FROM usuario u
                    INNER JOIN persona per ON u.cedula = per.cedula
                    INNER JOIN rol_usuario ru ON u.id_rol = ru.id_rol
                    WHERE ru.nivel IN (1) 
                    AND u.estatus >= 1
                    ORDER BY u.id_usuario DESC";
                        
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


         public function consultarPedidos() {
            $conex = $this->getConex1();
            try {
                $sql = "SELECT * FROM pedido";
                        
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
   private function ejecutarActualizacion($datos) {
    $conex = $this->getConex2();
    try {
        $conex->beginTransaction();

             // 1. Actualizar datos en la tabla persona
        $sqlPersona = "UPDATE persona 
                       SET cedula = :cedula_nueva, 
                           correo = :correo, 
                           tipo_documento = :tipo_documento 
                       WHERE cedula = :cedula_actual";

        $paramPersona = [
            'cedula_nueva' => $datos['cedula'],
            'correo' => $datos['correo'],
            'tipo_documento' => $datos['tipo_documento'],
            'cedula_actual' => $datos['cedula_actual']
        ];

        $stmtPersona = $conex->prepare($sqlPersona);
        $stmtPersona->execute($paramPersona);

        // 2. Actualizar datos en la tabla usuario
        $sqlUsuario = "UPDATE usuario 
                       SET cedula = :cedula_nueva, 
                           estatus = :estatus
                     WHERE cedula = :cedula_actual";

        $paramUsuario = [
            'cedula_nueva' => $datos['cedula'],
            'estatus' => $datos['estatus'],
            'cedula_actual' => $datos['cedula_actual']
        ];

        $stmtUsuario = $conex->prepare($sqlUsuario);
        $stmtUsuario->execute($paramUsuario);

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'actualizar'];

    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        return ['respuesta' => 0, 'text' => $e->getMessage()];
    }
}


/*||||||||||||||||||||||||||||||| VERIFICAR CEDULA Y CORREO  |||||||||||||||||||||||||  04  |||||*/        
   private function verificarExistencia($datos) {
    $conex = $this->getConex2();
    try {
        $conex->beginTransaction();
        $sql = "SELECT COUNT(*) FROM persona 
                WHERE ({$datos['campo']} = :valor)";

        $stmt = $conex->prepare($sql);
        $stmt->execute(['valor' => $datos['valor']]);
        $existe = $stmt->fetchColumn() > 0;

        $conex->commit();
        $conex = null;
        return $existe;
    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        throw $e;
    }
}


}
