<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

/*||||||||||||||||||||||||||||||| TOTAL METODOS =   06  |||||||||||||||||||||||||||||*/    

class Olvidoclave extends Conexion{

    private $encryptionKey = "MotorLoveMakeup"; 
    private $cipherMethod = "AES-256-CBC";
    
    function __construct() {
       parent::__construct(); // Llama al constructor de la clase padre
    }

/*||||||||||||||||||||||||||||||| ENCRIPTACION DE CLAVE  |||||||||||||||||||||||||  01  |||||*/        
     protected function encryptClave($datos) {
            $config = [
                'key' => "MotorLoveMakeup",
                'method' => "AES-256-CBC"
            ];
            
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($config['method']));
            $encrypted = openssl_encrypt($datos['clave'], $config['method'], $config['key'], 0, $iv);
            return base64_encode($iv . $encrypted);
    }

/*||||||||||||||||||||||||||||||| DESINCRIPTACION DE CLAVE   |||||||||||||||||||||||||  02  |||||*/        
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


/*||||||||||||||||||||||||||||||| OPERACIONES  |||||||||||||||||||||||||  03  |||||*/         
   public function procesarOlvido($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];
        
        try {
            switch ($operacion) {
                 case 'actualizar':
                     return $this->ejecutarActualizacionUsuario($datosProcesar);            
                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
        }
    }


/*||||||||||||||||||||||||||||||| ACTUALIZAR DE CLAVE USUARIO  |||||||||||||||||||||||||  06  |||||*/        
     protected function ejecutarActualizacionUsuario($datos) {
        $conex = $this->getConex2();
        try {
            $conex->beginTransaction();
            
            $sql = "UPDATE usuario 
                        SET  clave = :clave
                        WHERE cedula = :cedula";
            
               $parametros = [
                'clave' => $this->encryptClave(['clave' => $datos['clave']]),
                'cedula' => $datos['cedula']
                ];

            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($parametros);
            
            if ($resultado) {
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

   
   
  
}
