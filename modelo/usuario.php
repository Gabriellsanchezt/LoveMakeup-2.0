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
                    if (!$this->verificarExistenciaROL(['id_rol' => $datosProcesar['id_rol']])) {
                        return ['respuesta' => 0,'accion' => 'incluir', 'text' => 'el rol no existe'];
                    } 
                    $datosProcesar['clave'] = $this->encryptClave($datosProcesar['clave']);
                    return $this->ejecutarRegistro($datosProcesar);
                    
               case 'actualizar':
                   
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

                    if (!$this->verificarExistencia(['campo' => 'cedula', 'valor' => $datosProcesar['cedula_actual']])) {
                        return ['respuesta' => 0, 'accion' => 'actualizar', 'text' => 'el usuario no existe'];
                    }
                    
                    if (!$this->verificarExistenciaROL(['id_rol' => $datosProcesar['id_rol']])) {
                        return ['respuesta' => 0,'accion' => 'actualizar', 'text' => 'el rol no existe'];
                    } 
                    
                    return $this->ejecutarActualizacion($datosProcesar);
                    
                case 'eliminar':

                    if (!$this->verificarExistencia(['campo' => 'cedula', 'valor' => $datosProcesar['cedula']])) {
                        return ['respuesta' => 0, 'accion' => 'eliminar', 'text' => 'el usuario no existe'];
                    }

                    return $this->ejecutarEliminacion($datosProcesar);

                case 'verificar':
                  if ($this->verificarExistencia(['campo' => 'cedula', 'valor' => $datosProcesar['cedula']])) {
                        return ['respuesta' => 1,'accion' => 'verificar','text' => 'La cédula ya está registrada' ];
                    } else {
                        return [ 'respuesta' => 0,'accion' => 'verificar','text' => 'La cédula no se encuentra registrada'];
                    }

                 case 'verificarCorreo':
                    if ($this->verificarExistencia(['campo' => 'correo', 'valor' => $datosProcesar['correo']])) {
                            return ['respuesta' => 1, 'accion' => 'verificarcorreo', 'text' => 'La correo ya está registrada' ];
                        } else {
                            return [ 'respuesta' => 0, 'accion' => 'verificarcorreo', 'text' => 'La correo no se encuentra registrada'  ];
                        } 

                case 'verificarrol':
                  if ($this->verificarExistenciaROL($datosProcesar)) {
                        return ['respuesta' => 1,'accion' => 'verifirol'];
                    } else {
                        return [ 'respuesta' => 0,'accion' => 'verifirol','text' => 'Error, no se encuentra un rol registrado'];
                    }        

                default:
                    return ['respuesta' => 0, 'accion' => 'verifirol', 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'accion' => 'verifirol', 'mensaje' => $e->getMessage()];
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


        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'incluir'];

    } catch (\PDOException $e) {
        if ($conex) {
             
            $conex->rollBack();
            $conex = null;
            return ['respuesta' => 0, 'accion' => 'incluir', 'text' => $e->getMessage()];
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
                       SET cedula = :cedula_nueva
                       WHERE cedula = :cedula_actual";

        $paramUsuario = [
            'cedula_nueva' => $datos['cedula'],
            'cedula_actual' => $datos['cedula_actual']
        ];

        
        $stmtUsuario = $conex->prepare($sqlUsuario);
        $stmtUsuario->execute($paramUsuario);

         if ($datos['cedula'] !== $datos['cedula_actual']) {
             // 2.1 Actualizar la cédula en la tabla permiso
            $sqlPermisoUpdate = "UPDATE permiso 
                                SET cedula = :cedula_nueva 
                                WHERE cedula = :cedula_actual";

            $paramPermisoUpdate = [
                'cedula_nueva' => $datos['cedula'],
                'cedula_actual' => $datos['cedula_actual']
            ];

            $stmtPermisoUpdate = $conex->prepare($sqlPermisoUpdate);
            $stmtPermisoUpdate->execute($paramPermisoUpdate);
         }

        // 3. Actualizar datos en la tabla usuario
        $sqlUsuario2 = "UPDATE usuario 
                       SET estatus = :estatus, 
                           id_rol = :id_rol 
                       WHERE cedula = :cedula_nueva";

        $paramUsuario2 = [
            'cedula_nueva' => $datos['cedula'],
            'estatus' => $datos['estatus'],
            'id_rol' => $datos['id_rol'],
        ];

        $stmtUsuario2 = $conex->prepare($sqlUsuario2);
        $stmtUsuario2->execute($paramUsuario2);

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


private function verificarExistenciaROL($datos) {
    $conex = $this->getConex2();
    try {
        $conex->beginTransaction();

        $sql = "SELECT COUNT(*) FROM rol WHERE id_rol = :id_rol";

         $paramUpdate = [
            'id_rol' => $datos['id_rol']
    
        ];

        $stmt = $conex->prepare($sql);
        $stmt->execute($paramUpdate);
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
    public function consultar($limite = 100) {
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
                    INNER JOIN rol ru ON u.id_rol = ru.id_rol
                    WHERE ru.nivel IN (2, 3) 
                    AND u.estatus >= 1 AND u.id_usuario >=2
                    ORDER BY u.id_usuario DESC LIMIT :limite";
                    
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

/*||||||||||||||||||||||||||||||| LISTAR TIPO USUARIO  |||||||||||||||||||||||||  09  |||||*/    
    public function obtenerRol() {
        return $this->objtipousuario->consultar();
    }

    public function contarTotal(){
        $conex = $this->getConex2();
        $sql = "SELECT COUNT(*) AS total FROM usuario WHERE estatus >= 1 AND id_rol = 1 OR id_rol >= 3";
        $consulta = $conex->prepare($sql);
        $consulta->execute();
        $fila = $consulta->fetch(\PDO::FETCH_ASSOC);
        return $fila['total'];
    }
}
