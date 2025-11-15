<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Datos extends Conexion{
    
    public function __construct() {
        parent::__construct();
    }

    protected function encryptClave($datos) {
        $config = [
            'key' => "MotorLoveMakeup",
            'method' => "AES-256-CBC"
        ];
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($config['method']));
        $encrypted = openssl_encrypt($datos['clave'], $config['method'], $config['key'], 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    protected function decryptClave($datos) {
        $config = [
            'key' => "MotorLoveMakeup",
            'method' => "AES-256-CBC"
        ];
        
        $data = base64_decode($datos['clave_encriptada']);
        $ivLength = openssl_cipher_iv_length($config['method']);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, $config['method'], $config['key'], 0, $iv);
    }

/*-----*/

    public function procesarUsuario($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];
        
        try {
            switch ($operacion) {
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

                    return $this->ejecutarActualizacion($datosProcesar);
                    
                    case 'actualizarclave':
                      
                    if (!$this->validarClaveActual($datosProcesar)) {
                        return ['respuesta' => 0, 'accion' => 'clave', 'text' => 'La clave actual es incorrecta.'];
                    }

                     return $this->ejecutarActualizacionClave($datosProcesar);

                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }
    
     private function ejecutarActualizacion($datos) {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            $sql = "UPDATE persona 
                        SET cedula = :cedula, 
                            correo = :correo, 
                            nombre = :nombre,
                            apellido = :apellido,
                            telefono = :telefono,
                            tipo_documento = :tipo_documento
                    WHERE cedula = :cedula_actual";
            
               $parametros = [
                'cedula' => $datos['cedula'],
                'correo' => $datos['correo'],
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'telefono' => $datos['telefono'],
                  'tipo_documento' => $datos['tipo_documento'],
                'cedula_actual' => $datos['cedula_actual']
                ];

            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($parametros);

     
            $sqlUsuario = "UPDATE usuario 
                        SET cedula = :cedula_nueva
                        WHERE cedula = :cedula_actual";

            $paramUsuario = [
                'cedula_nueva' => $datos['cedula'],
                'cedula_actual' => $datos['cedula_actual']
            ];

            $stmtUsuario = $conex->prepare($sqlUsuario);
            $stmtUsuario->execute($paramUsuario);

           
            $sqlPermisoUpdate = "UPDATE permiso 
                                SET cedula = :cedula_nueva 
                                WHERE cedula = :cedula_actual";

            $paramPermisoUpdate = [
                'cedula_nueva' => $datos['cedula'],
                'cedula_actual' => $datos['cedula_actual']
            ];
            
            $stmtPermisoUpdate = $conex->prepare($sqlPermisoUpdate);
            $stmtPermisoUpdate->execute($paramPermisoUpdate);

            if ($stmtPermisoUpdate) {
                $conex->commit();
                $conex = null;
                return ['respuesta' => 1, 'accion' => 'actualizar'];
            }
            
            $conex->rollBack();
            $conex = null;
            return ['respuesta' => 0, 'accion' => 'actualizar'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

   private function validarClaveActual($datos) {
        $conex = $this->getConex2();
        try {
            $sql = "SELECT clave FROM usuario WHERE id_usuario = :id_usuario AND estatus >= 1";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_usuario' => $datos['id_usuario']]);
            $resultado = $stmt->fetch(\PDO::FETCH_OBJ);

            if ($resultado) {
                $claveDesencriptada = $this->decryptClave(['clave_encriptada' => $resultado->clave]);
                return $claveDesencriptada === $datos['clave_actual'];
            }
            
            $conex = null;
            return false;
        } catch (\PDOException $e) {
            if ($conex) $conex = null;
            throw $e;
        }
    }


   private function ejecutarActualizacionClave($datos) {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            $sql = "UPDATE usuario 
                        SET clave = :clave
                        WHERE id_usuario = :id_usuario";
            
               $parametros = [
                    'clave' => $this->encryptClave(['clave' => $datos['clave']]),
                    'id_usuario' => $datos['id_usuario']
                ];

            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($parametros);
            
            if ($resultado) {
                $conex->commit();
                $conex = null;
                return ['respuesta' => 1, 'accion' => 'clave'];
            }
            
            $conex->rollBack();
            $conex = null;
            return ['respuesta' => 0, 'accion' => 'clave'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

    
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

     public function consultardatos($id_usuario) {
        $conex = $this->getConex2();
        try {
        $sql = "SELECT 
                p.cedula,
                p.nombre,
                p.apellido,
                p.correo,
                p.telefono,
                p.tipo_documento,
                u.id_usuario,
                u.estatus,
                u.id_rol
            FROM persona p
            INNER JOIN usuario u ON p.cedula = u.cedula
            WHERE u.id_usuario = :id_usuario";
                    
           $stmt = $conex->prepare($sql);
             $stmt->execute(['id_usuario' => $id_usuario]);

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
  
}
   