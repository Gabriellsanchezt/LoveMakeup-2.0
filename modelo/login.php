<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

/*||||||||||||||||||||||||||||||| TOTAL DE METODOS = 8  |||||*/            

class Login extends Conexion {
    function __construct() {
        parent::__construct();
    }

/*||||||||||||||||||||||||||||||| ENCRIPTAR CLAVE  |||||||||||||||||||||||||  01  |||||*/            
    private function encryptClave($datos) {
        $config = [
            'key' => "MotorLoveMakeup",
            'method' => "AES-256-CBC"
        ];
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($config['method']));
        $encrypted = openssl_encrypt($datos['clave'], $config['method'], $config['key'], 0, $iv);
        return base64_encode($iv . $encrypted);
    }

/*||||||||||||||||||||||||||||||| DESINCRIPTAR CLAVE  |||||||||||||||||||||||||  02  |||||*/            
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

/*||||||||||||||||||||||||||||||| OPERACIONES  |||||||||||||||||||||||||  03  |||||*/            
    public function procesarLogin($jsonDatos) {
        $datos = json_decode($jsonDatos, true);
        $operacion = $datos['operacion'];
        $datosProcesar = $datos['datos'];
        
        try {
            switch ($operacion) {
                case 'verificar':
                    return $this->verificarCredenciales($datosProcesar);
                case 'registrar':
                    if ($this->verificarExistencia(['campo' => 'cedula', 'valor' => $datosProcesar['cedula']])) {
                        return ['respuesta' => 0, 'accion' => 'incluir', 'text' => 'La cédula ya está registrada'];
                    }
                    if ($this->verificarExistencia(['campo' => 'correo', 'valor' => $datosProcesar['correo']])) {
                        return ['respuesta' => 0, 'accion' => 'incluir', 'text' => 'El correo electrónico ya está registrado'];
                    }
                    return $this->registrarCliente($datosProcesar);
                case 'validar':
                    return $this->obtenerPersonaPorCedula($datosProcesar);

                case 'dolar':

                     if ($this->verificarFechaNoExiste($datosProcesar['fecha'])) {
                         return $this->ejecutarRegistro($datosProcesar);
                    }
                    return null;
                    
                case 'activousuaio':
                    return $this->activousuario($datosProcesar);

                default:
                    return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
            }
        } catch (\Exception $e) {
            return ['respuesta' => 0, 'accion' => 'incluir', 'text' => $e->getMessage()];
        }
    }

/*||||||||||||||||||||||||||||||| VERIFICAR CREDENCIALES CEDULA Y CLAVE  |||||||||||||||||||||||||  04  |||||*/            
 private function verificarCredenciales($datos) {
    
    if (!isset($datos['cedula']) || !is_numeric($datos['cedula']) || strlen((string)$datos['cedula']) > 8) {
        $error="10";
        return $error;
    }
    
    $conex2 = $this->getConex2();

    try {
        // Verificar credenciales en usuario, filtrando también por tipo_documento
        $sql = "SELECT 
                    u.*, 
                    ru.nombre AS nombre_rol, 
                    ru.nivel, 
                    p.nombre, 
                    p.apellido, 
                    p.correo, 
                    p.telefono, 
                    p.tipo_documento
                FROM usuario u
                INNER JOIN rol_usuario ru ON u.id_rol = ru.id_rol
                INNER JOIN persona p ON u.cedula = p.cedula
                WHERE u.cedula = :cedula 
                  AND p.tipo_documento = :tipo_documento
                  AND u.estatus IN (1, 2)";
        
        $stmt = $conex2->prepare($sql);
        $stmt->execute([
            'cedula' => $datos['cedula'],
            'tipo_documento' => $datos['tipo_documento']
        ]);
        $resultado = $stmt->fetchObject();

        if ($resultado) {
            $claveDesencriptada = $this->decryptClave(['clave_encriptada' => $resultado->clave]);
           
            if ($claveDesencriptada === $datos['clave']) {
                $conex2 = null;
                return $resultado;
            }
        }

        $conex2 = null;
        return null;

    } catch (\PDOException $e) {
        if ($conex2) $conex2 = null;
        throw $e;
    }
}

/*||||||||||||||||||||||||||||||| REGISTRAR CLIENTE NUEVO  |||||||||||||||||||||||||  05  |||||*/            
    private function registrarCliente($datos) {
        
        $conex = $this->getConex2();
        try {
             $conex->beginTransaction();

            // 1. Insertar en persona
            $sqlPersona = "INSERT INTO persona (cedula, nombre, apellido, correo, telefono, tipo_documento)
                        VALUES (:cedula, :nombre, :apellido, :correo, :telefono, :tipo_documento)";

            $paramPersona = [
                'cedula' => $datos['cedula'],
                'nombre' => ucfirst(strtolower($datos['nombre'])),
                'apellido' => ucfirst(strtolower($datos['apellido'])),
                'correo' => strtolower($datos['correo']),
                'telefono' => $datos['telefono'],
                'tipo_documento' => $datos['tipo_documento']
            ];

            $stmtPersona = $conex->prepare($sqlPersona);
            $stmtPersona->execute($paramPersona);

            // 2. Insertar en usuario
            $sqlUsuario = "INSERT INTO usuario (cedula, clave, estatus, id_rol)
                             VALUES (:cedula, :clave, 1, 1)";

            $paramUsuario = [
                'cedula' => $datos['cedula'],
                'clave' => $this->encryptClave(['clave' => $datos['clave']])
            ];

            $stmtUsuario = $conex->prepare($sqlUsuario);
            $stmtUsuario->execute($paramUsuario);
            
            if ($stmtUsuario) {
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

/*||||||||||||||||||||||||||||||| VERIFICAR CEDULA Y CORREO SI EXISTE  |||||||||||||||||||||||||  06  |||||*/            
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

/*||||||||||||||||||||||||||||||| OBTENER CEDULA PARA INGRESAR OLVIDO CLAVE  |||||||||||||||||||||||||  07  |||||*/            
    private function obtenerPersonaPorCedula($datos) {

    $conex = $this->getConex2();
    try {
    $sql = "SELECT 
                p.cedula,
                p.nombre,
                p.apellido,
                p.correo,
                p.telefono,
                p.tipo_documento,
                u.estatus,
                u.id_rol,
                ru.nivel
            FROM persona p
            INNER JOIN usuario u ON p.cedula = u.cedula
            INNER JOIN rol_usuario ru ON u.id_rol = ru.id_rol
            WHERE p.cedula = :cedula AND p.tipo_documento = :tipo_documento AND u.estatus >= 1";

    $stmt = $conex->prepare($sql);
    $stmt->execute([
        'cedula' => $datos['cedula'],
        'tipo_documento' => $datos['tipo_documento']
    ]);

    $resultado = $stmt->fetchObject();

    $conex = null;
    return $resultado ?: null;

} catch (\PDOException $e) {
    if ($conex) $conex = null;
    return null;
}
}


/*||||||||||||||||||||||||||||||| CONSULTAR PERMISOS ID PERMISOS  |||||||||||||||||||||||||  08  |||||*/            
     public function consultar($id_persona) {
        $conex = $this->getConex2();
        try {
        $sql = "SELECT 
                ru.id_rol, 
                p.cedula, 
                permiso.*
                FROM usuario p
                INNER JOIN rol_usuario ru ON p.id_rol = ru.id_rol
                INNER JOIN permiso ON p.cedula = permiso.cedula
                WHERE p.cedula = :id_persona
                ";
                    
           $stmt = $conex->prepare($sql);
             $stmt->execute(['id_persona' => $id_persona]);

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


    public function consultaTasa($fecha) {
    $conex = $this->getConex1();
    try {
        $sql = "SELECT tasa_bs FROM tasa_dolar WHERE fecha = :fecha LIMIT 1";
        $stmt = $conex->prepare($sql);
        $stmt->execute(['fecha' => $fecha]);

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



}