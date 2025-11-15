<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

/*||||||||||||||||||||||||||||||| METODO: TOTAL 14 ||||||||||||||||||||||||||||||*/

class Usuario extends Conexion
{
    private $encryptionKey = "MotorLoveMakeup"; 
    private $cipherMethod = "AES-256-CBC";
    private $objtipousuario; 
    
    function __construct() {
        parent::__construct();
        $this->objtipousuario = new TipoUsuario();
    }

/*|||||||||||||||||||||||||||||||||||||| ENCRIPTACION DE CLAVE  |||||||||||||||||||||||||||||||||| 01 ||*/   
    private function encryptClave($clave) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipherMethod)); 
        $encrypted = openssl_encrypt($clave, $this->cipherMethod, $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /*|||||||||||||||||||||||||||||||||||| DESINCRIPTACION DE CLAVE  ||||||||||||||||||||||||||||||||| 02 |||*/
    private function decryptClave($claveEncriptada) {
        $data = base64_decode($claveEncriptada);
        $ivLength = openssl_cipher_iv_length($this->cipherMethod);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, $this->cipherMethod, $this->encryptionKey, 0, $iv);
    }

/*||||||||||||||||||||||||||||||||||||||||||||||||||  OPERACIONES  ||||||||||||||||||||||||||||||||||||||||| 03 ||||*/    
    public function procesarUsuario($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];
        
        try {
            switch ($operacion) {
                case 'registrar':
                    if ($this->verificarExistencia(['campo' => 'cedula', 'valor' => $datosProcesar['cedula']])) {
                        return ['respuesta' => 0, 'accion' => 'incluir', 'text' => 'La cédula ya está registrada'];
                    }
                    if ($this->verificarExistencia(['campo' => 'correo', 'valor' => $datosProcesar['correo']])) {
                        return ['respuesta' => 0, 'accion' => 'incluir', 'text' => 'El correo electrónico ya está registrado'];
                    }
                    $datosProcesar['clave'] = $this->encryptClave($datosProcesar['clave']);
                    return $this->ejecutarRegistro($datosProcesar);
                    
               case 'actualizar':
                    $datosProcesar['insertar_permisos'] = false;

                    if ($datosProcesar['id_rol'] !== $datosProcesar['rol_actual']) {
                        $resultado = $this->ejecutarEliminacionPermisos($datosProcesar['cedula']);
                        if ($resultado['respuesta'] === 0) {
                            return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'No se pudo eliminar permisos'];
                        }
                        $datosProcesar['insertar_permisos'] = true;
                    }

                     if ($datosProcesar['cedula'] !== $datosProcesar['cedula_actual']) {
                        if ($this->verificarExistencia(['campo' => 'cedula', 'valor' => $datosProcesar['cedula']])) {
                            return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'La cédula ya está registrada'];
                        }
                    }

                    if ($datosProcesar['correo'] !== $datosProcesar['correo_actual']) {
                        if ($this->verificarExistencia(['campo' => 'correo', 'valor' => $datosProcesar['correo']])) {
                            return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'El correo electrónico ya está registrado'];
                        }
                    }

                    return $this->ejecutarActualizacion($datosProcesar);
                    
                case 'eliminar':
                    return $this->ejecutarEliminacion($datosProcesar);
                
                case 'actualizar_permisos':
                    return $this->actualizarLotePermisos($datosProcesar);

                case 'verificar':
                  if ($this->verificarExistencia(['campo' => 'cedula', 'valor' => $datosProcesar['cedula']])) {
                        return ['respuesta' => 1,'accion' => 'verificar','text' => 'La cédula ya está registrada' ];
                    } else {
                        return [ 'respuesta' => 0,'accion' => 'verificar','text' => 'La cédula no se encuentra registrada'];
                    }

                 case 'verificarCorreo':
                    if ($this->verificarExistencia(['campo' => 'correo', 'valor' => $datosProcesar['correo']])) {
                            return ['respuesta' => 1, 'accion' => 'verificar', 'text' => 'La correo ya está registrada' ];
                        } else {
                            return [ 'respuesta' => 0, 'accion' => 'verificar', 'text' => 'La correo no se encuentra registrada'  ];
                        } 

                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }

/*||||||||||||||||||||||||||||||| REGISTRO DE UN NUEVO USUARIO ||||||||||||||||||||||||||| 04 |||*/    
    private function ejecutarRegistro($datos) {
    $conex = $this->getConex2();
    try {
        $conex->beginTransaction();

        // 1
        $sqlPersona = "INSERT INTO persona (cedula, nombre, apellido, correo, telefono, tipo_documento)
                       VALUES (:cedula, :nombre, :apellido, :correo, :telefono, :tipo_documento)";
        $paramPersona = [
            'cedula' => $datos['cedula'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'correo' => $datos['correo'],
            'telefono' => $datos['telefono'],
            'tipo_documento' => $datos['tipo_documento']
        ];
        $stmtPersona = $conex->prepare($sqlPersona);
        $stmtPersona->execute($paramPersona);

        // 2
        $sqlUsuario = "INSERT INTO usuario (cedula, clave, estatus, id_rol)
                       VALUES (:cedula, :clave, 1, :id_rol)";
        $paramUsuario = [
            'cedula' => $datos['cedula'],
            'clave' => $datos['clave'],
            'id_rol' => $datos['id_rol']
        ];
        $stmtUsuario = $conex->prepare($sqlUsuario);
        $stmtUsuario->execute($paramUsuario);

        // 3
        $nivel = $datos['nivel'];
        $cedula = $datos['cedula']; // ahora usamos la cédula como identificador
        $datosPermisos = $this->generarPermisosPorNivel($cedula, $nivel);

        $sqlPermiso = "INSERT INTO permiso (id_modulo, cedula, accion, estado)
                       VALUES (:id_modulo, :cedula, :accion, :estado)";
        $stmtPermiso = $conex->prepare($sqlPermiso);

        foreach ($datosPermisos as $permiso) {
            $stmtPermiso->execute($permiso);
        }

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'incluir'];

    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        throw $e;
    }
}


/*||||||||||||||||||||||||||||||| ACTUALIZAR DATOS DEL USUARIO  ||||||||||||||||||||||||||| 05 |||*/
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
                           estatus = :estatus, 
                           id_rol = :id_rol 
                       WHERE cedula = :cedula_actual";

        $paramUsuario = [
            'cedula_nueva' => $datos['cedula'],
            'estatus' => $datos['estatus'],
            'id_rol' => $datos['id_rol'],
            'cedula_actual' => $datos['cedula_actual']
        ];

        $stmtUsuario = $conex->prepare($sqlUsuario);
        $stmtUsuario->execute($paramUsuario);

        // 3. Actualizar la cédula en la tabla permiso
        $sqlPermisoUpdate = "UPDATE permiso 
                             SET cedula = :cedula_nueva 
                             WHERE cedula = :cedula_actual";

        $paramPermisoUpdate = [
            'cedula_nueva' => $datos['cedula'],
            'cedula_actual' => $datos['cedula_actual']
        ];

        $stmtPermisoUpdate = $conex->prepare($sqlPermisoUpdate);
        $stmtPermisoUpdate->execute($paramPermisoUpdate);

        // 4. Insertar nuevos permisos si corresponde
        if ($stmtUsuario && !empty($datos['insertar_permisos'])) {
            $nivel = $datos['nivel'];
            $cedula = $datos['cedula'];
            $datosPermisos = $this->generarPermisosPorNivel($cedula, $nivel);

            $sqlPermiso = "INSERT INTO permiso (id_modulo, cedula, accion, estado)
                           VALUES (:id_modulo, :cedula, :accion, :estado)";
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
        }
        throw $e;
    }
}


/*||||||||||||||||||||||||||||||| ELIMINAR USUARIO (LOGICO)  |||||||||||||||||||||||||| 06 ||||*/
    private function ejecutarEliminacion($datos) {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            $sql = "UPDATE usuario SET estatus = 0 WHERE cedula = :cedula";
            
            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($datos);
            
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

/*||||||||||||||||||||||||||||||| VERIFICAR CEDULA Y CORREO  ||||||||||||||||||||||||| 07 |||||*/    
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

/*||||||||||||||||||||||||||||||| CONSULTAR LOS USUARIOS  |||||||||||||||||||||||||| 08 ||||*/    
    public function consultar() {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
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
                    WHERE ru.nivel IN (2, 3) 
                    AND u.estatus >= 1 AND u.id_usuario >=2
                    ORDER BY u.id_usuario DESC";
                    
            $stmt = $conex->prepare($sql);
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

/*||||||||||||||||||||||||||||||| LISTAR TIPO USUARIO  |||||||||||||||||||||||||  09  |||||*/    
    public function obtenerRol() {
        return $this->objtipousuario->consultar();
    }

/*||||||||||||||||||||||||||||||| PERMISOS PREDETERMINADOS DEL USUARIO |||||||||||||||||||||||| 10 |||||*/    
   private function generarPermisosPorNivel($cedula, $nivel) {
    $permisos = [];

    if ($nivel == 3) { // Admin
        $permisos = [
            ['id_modulo' => 1, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],

            ['id_modulo' => 2, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 2, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 2, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],

            ['id_modulo' => 3, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 3, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],

            ['id_modulo' => 4, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 4, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '1'],

            ['id_modulo' => 5, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 5, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '1'],
     
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '1'],

            ['id_modulo' => 7, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 7, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 7, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 7, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],

            ['id_modulo' => 8, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 8, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 8, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 8, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],

            ['id_modulo' => 9, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 9, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 9, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 9, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],

            ['id_modulo' => 10, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 10, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
        
            ['id_modulo' => 11, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 11, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 11, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 11, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],

            ['id_modulo' => 12, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 12, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 12, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 12, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],

            ['id_modulo' => 13, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 13, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 13, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 13, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],

            ['id_modulo' => 14, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 14, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],

            ['id_modulo' => 15, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '0'], //Bitacora
            ['id_modulo' => 15, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '0'],

            ['id_modulo' => 16, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 16, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 16, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 16, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],
            ['id_modulo' => 16, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '1'],

            ['id_modulo' => 17, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 17, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],
            ['id_modulo' => 17, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],
            ['id_modulo' => 17, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '1'],
            
            ['id_modulo' => 18, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 18, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '1']
        ];
    } elseif ($nivel == 2) { // Usuario básico
        $permisos = [
           ['id_modulo' => 1, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],

           ['id_modulo' => 3, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
           ['id_modulo' => 3, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '1'],

            ['id_modulo' => 4, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 4, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '1'],

            ['id_modulo' => 5, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 5, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '1'],

            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'registrar', 'estado' => '0'],
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '0'],
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'eliminar', 'estado' => '0'],
            ['id_modulo' => 6, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '0'],

            ['id_modulo' => 10, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 10, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '0'],

            ['id_modulo' => 14, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 14, 'cedula' => $cedula, 'accion' => 'editar', 'estado' => '1'],

            ['id_modulo' => 18, 'cedula' => $cedula, 'accion' => 'ver', 'estado' => '1'],
            ['id_modulo' => 18, 'cedula' => $cedula, 'accion' => 'especial', 'estado' => '1']
        ];
    }

    return array_map(function($permiso) {
        return [
            ':id_modulo' => $permiso['id_modulo'],
            ':cedula' => $permiso['cedula'],
            ':accion' => $permiso['accion'],
            ':estado' => $permiso['estado'],
        ];
    }, $permisos);
}

/*||||||||||||||||||||||||||||||| ELIMINAR PERMISOS (USUARIO CAMBIO DE ROL)  |||||||||||||||||||||||||| 11 ||||*/
private function ejecutarEliminacionPermisos($cedula) {
    
    $conex = $this->getConex2();
    try {
        $conex->beginTransaction();

        $sql = "DELETE FROM permiso WHERE cedula = ?";
        $stmt = $conex->prepare($sql);

        $resultado = $stmt->execute([$cedula]);

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

/*||||||||||||||||||||||||||||||| CONSULTAR PERMISO DEL USUARIO SELECCIONADO  |||||||||||||||||||||||||| 12 ||||*/
     public function buscar($cedula) {
        $conex = $this->getConex2();
        try { 
        $sql = "SELECT 
                permiso.*, 
                modulo.id_modulo, 
                modulo.nombre
                FROM permiso
                INNER JOIN modulo ON permiso.id_modulo = modulo.id_modulo
                WHERE permiso.cedula = :cedula;
                ";
                    
           $stmt = $conex->prepare($sql);
            $stmt->execute(['cedula' => $cedula]);

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

/*||||||||||||||||||||||||||||||| CONSULTAR EL NIVEL PARA EDITAR LOS PERMISOS  ||||||||||||||||||||||||| 13 |||||*/    
    public function obtenerNivelPorId($id_usuario) {
    $conex = $this->getConex2();
    try {
        $sql = "SELECT r.nivel
                FROM usuario u
                INNER JOIN rol_usuario r ON u.id_rol = r.id_rol
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

/*||||||||||||||||||||||||||||||| ACTUALIZAR PERMISOS DEL USUARIO  ||||||||||||||||||||||||| 14 |||||*/
    private function actualizarLotePermisos($lista) {
    $conex = $this->getConex2();
    try {
        $conex->beginTransaction();

        $sql = "UPDATE permiso 
                SET estado = :estado 
                WHERE id_permiso = :id_permiso";

        $stmt = $conex->prepare($sql);

        foreach ($lista as $permiso) {
           
            $stmt->execute([
                'estado' => $permiso['estado'],
                'id_permiso' => $permiso['id_permiso']
            ]);
        }

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'actualizar_permisos'];

    } catch (\PDOException $e) {
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        throw $e;
    }
}

}
