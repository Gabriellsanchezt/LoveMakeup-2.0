<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Catalogo_datos extends Conexion{
private $objEntrega;
    
    public function __construct() {
        parent::__construct();
        $this->objEntrega = new MetodoEntrega();
    }

    private function encryptClave($datos) {
        $config = [
            'key' => "MotorLoveMakeup",
            'method' => "AES-256-CBC"
        ];
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($config['method']));
        $encrypted = openssl_encrypt($datos['clave'], $config['method'], $config['key'], 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decryptClave($datos) {
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

    public function procesarCliente($jsonDatos) {
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

                case 'eliminar':
                         return $this->ejecutarEliminacion($datosProcesar);

                case 'incluir':
                         return $this->RegistroDireccion($datosProcesar); 

                case 'actualizardireccion':
                         return $this->ejecutarActualizacionDireccion($datosProcesar);           
                         
                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }
    
     private function ejecutarActualizacion($datos) {
        $conex = $this->getConex2();
         $conex2 = $this->getConex1();
        try {
            $conex->beginTransaction();
            $conex2->beginTransaction();
            
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
       
            if ($datos['cedula'] !== $datos['cedula_actual']) {
         
            // Verificar si hay pedidos con la cédula actual
            $sqlCheckPedido = "SELECT COUNT(*) FROM pedido WHERE cedula = :cedula_actual";
            $stmtCheckPedido = $conex2->prepare($sqlCheckPedido);
            $stmtCheckPedido->execute(['cedula_actual' => $datos['cedula_actual']]);
            $hayPedidos = $stmtCheckPedido->fetchColumn() > 0;

            // Verificar si hay direcciones con la cédula actual
            $sqlCheckDireccion = "SELECT COUNT(*) FROM direccion WHERE cedula = :cedula_actual";
            $stmtCheckDireccion = $conex2->prepare($sqlCheckDireccion);
            $stmtCheckDireccion->execute(['cedula_actual' => $datos['cedula_actual']]);
            $hayDirecciones = $stmtCheckDireccion->fetchColumn() > 0;

                // Solo actualizar si hay registros en ambas tablas
            if ($hayPedidos || $hayDirecciones) {

                    // Actualizar pedidos
                    $sqlPedido = "UPDATE pedido SET cedula = :cedula_nueva WHERE cedula = :cedula_actual";
                    $stmtPedido = $conex2->prepare($sqlPedido);
                    $stmtPedido->execute([
                        'cedula_nueva' => $datos['cedula'],
                        'cedula_actual' => $datos['cedula_actual']
                    ]);

                    // Actualizar direcciones
                    $sqlDireccion = "UPDATE direccion SET cedula = :cedula_nueva WHERE cedula = :cedula_actual";
                    $stmtDireccion = $conex2->prepare($sqlDireccion);
                    $stmtDireccion->execute([
                        'cedula_nueva' => $datos['cedula'],
                        'cedula_actual' => $datos['cedula_actual']
                    ]);
                } 
            }
            
            if ($stmtUsuario) {
                $conex->commit();
                $conex = null;
                $conex2->commit();
                $conex2 = null;
                return ['respuesta' => 1, 'accion' => 'actualizar'];
            }
            
            $conex->rollBack();
            $conex = null;
            $conex2->rollBack();
            $conex2 = null;
            return ['respuesta' => 0, 'accion' => 'actualizar'];
              
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
              if ($conex2) {
                $conex2->rollBack();
                $conex2 = null;
            }
            throw $e;
        }
    }

    private function ejecutarActualizacionDireccion($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            $sql = "UPDATE direccion 
                        SET direccion_envio = :direccion_envio,
                            sucursal_envio = :sucursal_envio
                        WHERE id_direccion = :id_direccion";
            
               $parametros = [
                'direccion_envio' => $datos['direccion_envio'],
                'sucursal_envio' => $datos['sucursal_envio'],
                'id_direccion' => $datos['id_direccion']
                ];

            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($parametros);
            
            if ($resultado) {
                $conex->commit();
                $conex = null;
                return ['respuesta' => 1, 'accion' => 'actualizardireccion'];
            }
            
            $conex->rollBack();
            $conex = null;
            return ['respuesta' => 0, 'accion' => 'actualizardireccion'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }

     private function RegistroDireccion($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            $sql = "INSERT INTO direccion(id_metodoentrega, cedula, direccion_envio, sucursal_envio)
                    VALUES(:id_metodoentrega, :cedula, :direccion_envio, :sucursal_envio)";
            
            $parametros = [
                'id_metodoentrega' => $datos['id_metodoentrega'],
                'cedula' => $datos['cedula'],
                'direccion_envio' => $datos['direccion_envio'],
                'sucursal_envio' => $datos['sucursal_envio']
                ];

            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($parametros);

             if ($resultado) {
                $conex->commit();
                $conex = null;
               return ['respuesta' => 1, 'accion' => 'incluir'];
            }
            
            $conex->rollBack();
            $conex = null;
            return ['respuesta' => 0, 'accion' => 'incluir'];
                    
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
    
   private function ejecutarEliminacion($datos) {
    $conex = $this->getConex1(); 
    $conex2 = $this->getConex2(); 

    try {
        $conex->beginTransaction();
        $conex2->beginTransaction();

        // 1. Actualizar la cédula en pedidos (anteponer 0)
        $nuevaCedula = '1' . $datos['cedula'];
        $sqlPedido = "UPDATE pedido SET cedula = :nueva_cedula WHERE cedula = :cedula";
        $stmtPedido = $conex->prepare($sqlPedido);
        $stmtPedido->execute([
            'nueva_cedula' => $nuevaCedula,
            'cedula' => $datos['cedula']
        ]);

        // 2. Eliminar de usuario
        $sqlUsuario = "DELETE FROM usuario WHERE id_usuario = :id_usuario";
        $stmtUsuario = $conex2->prepare($sqlUsuario);
        $stmtUsuario->execute(['id_usuario' => $datos['id_usuario']]);

        $sqlbitacora = "DELETE FROM bitacora WHERE cedula = :cedula";
        $stmbitacora = $conex2->prepare($sqlbitacora);
        $stmbitacora->execute(['cedula' => $datos['cedula']]);

        // 3. Eliminar de persona
        $sqlPersona = "DELETE FROM persona WHERE cedula = :cedula";
        $stmtPersona = $conex2->prepare($sqlPersona);
        $stmtPersona->execute(['cedula' => $datos['cedula']]);

     
        $conex->commit();
        $conex2->commit();

        $conex = null;
        $conex2 = null;

        return ['respuesta' => 1, 'accion' => 'eliminar'];

    } catch (\PDOException $e) {
      
        if ($conex) {
            $conex->rollBack();
            $conex = null;
        }
        if ($conex2) {
            $conex2->rollBack();
            $conex2 = null;
        }
        throw $e;
    }
}


     public function obtenerEntrega() {
        return $this->objEntrega->consultar();
    }
  
    public function consultardireccion() {
        $conex = $this->getConex1();
        try {
             $sql = "SELECT *
                FROM direccion 
                WHERE id_metodoentrega IN (1, 2, 3) AND cedula = :cedula";

            $stmt = $conex->prepare($sql);
            $stmt->bindParam(':cedula', $_SESSION['id'], \PDO::PARAM_INT);
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
