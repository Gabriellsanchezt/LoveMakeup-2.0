<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class TipoUsuario extends Conexion {

    public function procesarRol($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];
        
        try {
            switch ($operacion) {
                case 'registrar':
                    return $this->ejecutarRegistro($datosProcesar);
                    
               case 'actualizar':
                    $datosProcesar['insertar_permisos'] = false;
                    
                    if ($datosProcesar['nivel'] !== $datosProcesar['nivel_actual']) {
                        $resultado = $this->ejecutarEliminacionPermisos($datosProcesar['id_rol']);
                        if ($resultado['respuesta'] === 0) {
                            return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No se pudo eliminar permisos'];
                        }
                        $datosProcesar['insertar_permisos'] = true;
                    }

                    if (!$this->verificarExistencia(['campo' => 'id_rol', 'valor' => $datosProcesar['id_rol']])) {
                        return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No Existe el tipo usuario'];
                    }

                    return $this->ejecutarActualizacion($datosProcesar);
                    
                case 'eliminar':
                    if (!$this->verificarExistencia(['campo' => 'id_rol', 'valor' => $datosProcesar['id_rol']])) {
                        return ['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'No Existe el tipo usuario'];
                    }

                    return $this->ejecutarEliminacion($datosProcesar);

                case 'actualizar_permisos':
                    return $this->actualizarLotePermisos($datosProcesar);
                
                case 'verificarrol':
                    if ($this->verificarExistencia(['campo' => 'id_rol', 'valor' => $datosProcesar['id_rol']])) {
                        return ['respuesta' => 1,'accion' => 'verifirol'];
                    } else {
                        return [ 'respuesta' => 0,'accion' => 'verifirol','text' => 'Error, no se encuentra un rol registrado'];
                    }      
                
                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }

    /*||||||||||||||||||||||||||||||| VERIFICAR CEDULA Y CORREO  ||||||||||||||||||||||||| 07 |||||*/    
    private function verificarExistencia($datos) {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            $sql = "SELECT COUNT(*) FROM rol 
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

    private function ejecutarRegistro($datos) {
    $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            $sqlRol = "INSERT INTO rol (nombre, nivel, estatus)
                        VALUES (:nombre, :nivel, 1)";
            $paramRol = [
                'nombre' => $datos['nombre'],
                'nivel' => $datos['nivel']
            ];
            $stmtrol = $conex->prepare($sqlRol);
            $stmtrol->execute($paramRol);

            $id_rol = $conex->lastInsertId();

            $nivel = $datos['nivel'];

            $datosPermisos = $this->generarPermisosPorNivel($id_rol,$nivel);

            $sqlPermiso = "INSERT INTO permiso_rol (id_rol, id_modulo, id_permiso, estado)
                        VALUES (:id_rol, :id_modulo, :id_permiso, :estado)";
            $stmtPermiso = $conex->prepare($sqlPermiso);

            foreach ($datosPermisos as $permiso) {
                $stmtPermiso->execute($permiso);
            }

            $conex->commit();
            $conex = null;
            return ['respuesta' => 1, 'accion' => 'registrar'];

        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
                return ['respuesta' => 0, 'accion' => 'registrar', 'text' => $e];
            }
            throw $e;
        }
    }

    /*||||||||||||||||||||||||||||||| ELIMINAR USUARIO (LOGICO)  |||||||||||||||||||||||||| 06 ||||*/
    private function ejecutarEliminacion($datos) {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            $sql = "UPDATE rol SET estatus = 0 WHERE id_rol = :id_rol";
            
            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($datos);

            $sqlPermiso = "DELETE FROM permiso_rol WHERE id_rol = :id_rol";
            
            $stmt2 = $conex->prepare($sqlPermiso);
            $resultado2 = $stmt2->execute($datos);
            
            $conex->commit();
            $conex = null;
            return ['respuesta' => 1, 'accion' => 'eliminar'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
                return ['respuesta' => 0, 'accion' => 'eliminar', 'text' => $e];
            }
            throw $e;
        }
    }


     private function ejecutarActualizacion($datos) {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            $sql = "UPDATE rol SET nombre = :nombre, nivel = :nivel WHERE id_rol = :id_rol";
            
            $paramRol = [
                'id_rol' => $datos['id_rol'],
                'nombre' => $datos['nombre'],
                'nivel' => $datos['nivel']
            ];
            $stmtrol = $conex->prepare($sql);
            $stmtrol->execute($paramRol);
          
            // 4. Insertar nuevos permisos si corresponde
            if ($stmtrol && !empty($datos['insertar_permisos'])) {
                
                $nivel = $datos['nivel'];
                $id_rol = $datos['id_rol'];
                $datosPermisos = $this->generarPermisosPorNivel($id_rol,$nivel);

                $sqlPermiso = "INSERT INTO permiso_rol (id_rol, id_modulo, id_permiso, estado)
                        VALUES (:id_rol, :id_modulo, :id_permiso, :estado)";
                $stmtPermiso = $conex->prepare($sqlPermiso);

                foreach ($datosPermisos as $permiso) {
                    $stmtPermiso->execute($permiso);
                }
            }
            
            $conex->commit();
            $conex = null;
            return ['respuesta' => 1, 'accion' => 'actualizar'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
                return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => $e];
            }
            throw $e;
        }
    }


    public function consultar($limite = 100) {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            $sql = "SELECT * FROM rol WHERE estatus >= 1 AND id_rol > 1 ORDER BY id_rol DESC LIMIT :limite";
                    
            $stmt = $conex->prepare($sql);
            $stmt->bindParam(':limite', $limite, \PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $conex->commit();
            $conex = null;
            return $resultado;
        } catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    public function contarTotal(){
        $conex = $this->getConex2();
        $sql = "SELECT COUNT(*) AS total FROM rol WHERE estatus >= 1";
        $consulta = $conex->prepare($sql);
        $consulta->execute();
        $fila = $consulta->fetch(\PDO::FETCH_ASSOC);
        return $fila['total'];
    }

    /*||||||||||||||||||||||||||||||| CONSULTAR PERMISO DEL USUARIO SELECCIONADO  |||||||||||||||||||||||||| 12 ||||*/
    public function buscar($id_rol) {
        $conex = $this->getConex2();
        try { 
        $sql = "SELECT 
                permiso_rol.*, 
                modulo.id_modulo, 
                modulo.nombre
                FROM permiso_rol
                INNER JOIN modulo ON permiso_rol.id_modulo = modulo.id_modulo
                WHERE permiso_rol.id_rol = :id_rol;
                ";
                    
           $stmt = $conex->prepare($sql);
            $stmt->execute(['id_rol' => $id_rol]);

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

    /*||||||||||||||||||||||||||||||| ACTUALIZAR PERMISOS DEL USUARIO  ||||||||||||||||||||||||| 14 |||||*/
   /*||||||||||||||||||||||||||||||| ACTUALIZAR PERMISOS DEL ROL  |||||||||||||||||||||||||*/
private function actualizarLotePermisos($lista) {
    $conex = $this->getConex2();

    try {
        $conex->beginTransaction();

        $sql = "UPDATE permiso_rol
                SET estado = :estado
                WHERE id_permiso_rol = :id_permiso_rol";

        $stmt = $conex->prepare($sql);

        foreach ($lista as $permiso) {

            // Cada elemento contiene:
            // id_permiso_rol, id_modulo, id_permiso, estado

            $stmt->execute([
                ':estado'         => $permiso['estado'],
                ':id_permiso_rol' => $permiso['id_permiso_rol']
            ]);
        }

        $conex->commit();
        $conex = null;

        return [
            'respuesta' => 1,
            'accion' => 'actualizar_permisos',
            'text' => 'Permisos actualizados correctamente'
        ];

    } catch (\PDOException $e) {

        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }

        throw $e;
    }
}

private function generarPermisosPorNivel($id_rol, $nivel){
    // Matriz de permisos por nivel
    $permisos_por_nivel = [
    // NIVEL 3 
    3 => [
        // módulo => [permiso_id => estado]
        1  => [1 => 1],
        2  => [1 => 1, 2 => 1, 3 => 1],
        3  => [1 => 1, 2 => 1],
        4  => [1 => 1, 5 => 1],
        5  => [1 => 1, 5 => 1],
        6  => [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1],
        7  => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        8  => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        9  => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        10 => [1 => 1, 3 => 1],
        11 => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        12 => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        13 => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        14 => [1 => 1, 3 => 1],
        15 => [1 => 0, 4 => 0], // Bitácora
        16 => [1 => 1, 2 => 1, 3 => 1, 4 => 1],
        17 => [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1],
        18 => [1 => 1, 5 => 1]
    ],

    // NIVEL 2 (USUARIO BÁSICO)
    2 => [
        1  => [1 => 1],
        3  => [1 => 1, 2 => 1],
        4  => [1 => 1, 5 => 1],
        5  => [1 => 1, 5 => 1],
        6  => [1 => 1, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        10 => [1 => 1, 3 => 1],
        14 => [1 => 1, 3 => 1],
        18 => [1 => 1, 5 => 1]
    ]
];

    $permisos = [];

    foreach ($permisos_por_nivel[$nivel] as $modulo_id => $permisosModulo) {
        foreach ($permisosModulo as $id_permiso => $estado) {
            $permisos[] = [
                ':id_rol'     => $id_rol,
                ':id_modulo'  => $modulo_id,
                ':id_permiso' => $id_permiso,
                ':estado'     => $estado
            ];
        }
    }

    return $permisos;
}

/*||||||||||||||||||||||||||||||| CONSULTAR EL NIVEL PARA EDITAR LOS PERMISOS  ||||||||||||||||||||||||| 13 |||||*/    
    public function obtenerNivelPorId($id_usuario) {
    $conex = $this->getConex2();
    try {
        $sql = "SELECT r.nivel
                FROM usuario u
                INNER JOIN rol r ON u.id_rol = r.id_rol
                WHERE u.id_usuario = :id_usuario";
        $stmt = $conex->prepare($sql);
        $stmt->execute(['id_usuario' => $id_usuario]);
        $nivel = $stmt->fetchColumn();
        $conex = null;
        return $nivel !== false ? (int)$nivel : null;
    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        throw $e;
    }
}







/*||||||||||||||||||||||||||||||| ELIMINAR PERMISOS (USUARIO CAMBIO DE ROL)  |||||||||||||||||||||||||| 11 ||||*/
private function ejecutarEliminacionPermisos($id_rol) {
    
    $conex = $this->getConex2();
    try {
        $conex->beginTransaction();

        $sql = "DELETE FROM permiso_rol WHERE id_rol = ?";
        $stmt = $conex->prepare($sql);

        $resultado = $stmt->execute([$id_rol]);

        if ($resultado) {
            $conex->commit();
            $conex = null;
            return ['respuesta' => 1, 'accion' => 'eliminar'];
        }

        $conex->rollBack();
        $conex = null;
        return ['respuesta' => 0, 'accion' => 'eliminar'];
    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        throw $e;
    }
}









}


