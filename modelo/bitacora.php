<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;

class Bitacora extends Conexion {

    private $conex1;
    private $conex2;
    private $id_bitacora;
    private $accion;
    private $fecha_hora;
    private $descripcion;
    private $id_persona;
    private static $timezoneConfigurado = false;

    // Constantes para tipos de acciones (mantenidas para compatibilidad si otros módulos las usan) 
    const CREAR = 'CREAR';
    const MODIFICAR = 'MODIFICAR';
    const ELIMINAR = 'ELIMINAR';
    const ACCESO_MODULO = 'ACCESO A MÓDULO';
    const CAMBIO_ESTADO = 'CAMBIO_ESTADO';

    // Niveles de log para observabilidad estructurada
    const LOG_ERROR = 'ERROR';
    const LOG_WARN = 'WARN';
    const LOG_INFO = 'INFO';
    const LOG_DEBUG = 'DEBUG';

    function __construct(){ 
        parent::__construct(); // Llama al constructor de la clase padre

        // Obtener las conexiones de la clase padre
        $this->conex1 = $this->getConex1();
        $this->conex2 = $this->getConex2();
        
        // Configurar zona horaria una sola vez (optimización)
        if (!self::$timezoneConfigurado) {
            date_default_timezone_set('America/Caracas');
            self::$timezoneConfigurado = true;
        }
        
        // NOTA: Ya no se detecta automáticamente ninguna acción
        // El módulo de bitácora solo funciona cuando se llama explícitamente
    }

    /* Log estructurado para observabilidad */
    private function logEstructurado($nivel, $mensaje, array $contexto = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $nivel,
            'component' => 'Bitacora',
            'message' => $mensaje,
            'context' => $contexto
        ];
        
        // Agregar información de sesión si está disponible
        if (session_status() !== PHP_SESSION_NONE && isset($_SESSION['id'])) {
            $logData['context']['usuario'] = $_SESSION['id'] ?? 'unknown';
        }
        
        // Agregar información de request si está disponible
        if (isset($_SERVER['REQUEST_URI'])) {
            $logData['context']['endpoint'] = $_SERVER['REQUEST_URI'];
        }
        
        // Log en formato JSON para mejor parseo
        error_log('[BITACORA] ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }

    /* Registra una operación en la bitácora */
    public function registrarOperacion($accion, $modulo, $datos = '') {
        // Validar que exista sesión (sin depender de variables globales no inicializadas)
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }
        
        if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
            return false;
        }

        try {
            // Validar parámetros de entrada
            if (empty($accion) || !is_string($accion)) {
                $this->logEstructurado(self::LOG_WARN, 'Intento de registrar operación con acción inválida', [
                    'accion_recibida' => $accion,
                    'tipo_accion' => gettype($accion)
                ]);
                return false;
            }
            
            if (empty($modulo) || !is_string($modulo)) {
                $this->logEstructurado(self::LOG_WARN, 'Intento de registrar operación con módulo inválido', [
                    'modulo_recibido' => $modulo,
                    'tipo_modulo' => gettype($modulo)
                ]);
                return false;
            }

            // La zona horaria ya está configurada en el constructor (optimización)
            $fecha = date('Y-m-d H:i:s');
            
            // Generar descripción según el tipo de datos recibidos
            $detalle = '';
            $moduloLower = strtolower(trim($modulo));
            
            // Si $datos es string, usarlo directamente (llamada explícita con descripción)
            if (is_string($datos) && !empty($datos)) {
                $detalle = trim($datos);
            } 
            // Si $datos es array, intentar generar descripción básica desde el array
            elseif (is_array($datos) && !empty($datos)) {
                // Generar descripción básica desde el array
                $detalles_parts = [];
                foreach ($datos as $key => $value) {
                    if (!empty($value) && is_scalar($value)) {
                        $detalles_parts[] = ucfirst($key) . ": " . $value;
                    }
                }
                if (!empty($detalles_parts)) {
                    $detalle = implode(' | ', $detalles_parts);
                } else {
                    $detalle = "Acción: {$accion} en módulo: " . ucfirst($modulo);
                }
            }
            // Si no hay datos o está vacío, generar descripción básica
            else {
                $detalle = "Acción: {$accion} en módulo: " . ucfirst($modulo);
            }
            
            // Agregar el módulo al final de la descripción, excepto si es 'bitacora'
            if ($moduloLower !== 'bitacora' && !empty($detalle)) {
                $detalle .= " [" . ucfirst($modulo) . "]";
            }

            // Validar que la descripción no exceda el límite de la base de datos (250 caracteres)
            if (strlen($detalle) > 250) {
                $detalle = substr($detalle, 0, 247) . '...';
            }

            // Validar que la acción no exceda el límite (250 caracteres)
            if (strlen($accion) > 250) {
                $accion = substr($accion, 0, 247) . '...';
            }

            // Insertar registro en bitácora
            $registro = "INSERT INTO bitacora (accion, fecha_hora, descripcion, cedula) 
                        VALUES (:accion, :fecha_hora, :descripcion, :cedula)";
            
            $stmt = $this->conex2->prepare($registro);
            $stmt->bindParam(':accion', $accion, \PDO::PARAM_STR);
            $stmt->bindParam(':fecha_hora', $fecha, \PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $detalle, \PDO::PARAM_STR);
            $cedula = $_SESSION['id'];
            $stmt->bindParam(':cedula', $cedula, \PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if ($result) {
                // Log estructurado para observabilidad
                $this->logEstructurado(self::LOG_INFO, 'Operación registrada exitosamente en bitácora', [
                    'accion' => $accion,
                    'modulo' => $modulo,
                    'cedula' => $cedula
                ]);
                
                return array('respuesta' => 1, 'mensaje' => 'Operación registrada exitosamente');
            } else {
                $this->logEstructurado(self::LOG_ERROR, 'Fallo al ejecutar INSERT en bitácora', [
                    'accion' => $accion,
                    'modulo' => $modulo
                ]);
                return array('respuesta' => 0, 'mensaje' => 'Error al registrar la operación');
            }
        } catch (\PDOException $e) {
            // Log estructurado del error sin exponer detalles sensibles en respuesta
            $this->logEstructurado(self::LOG_ERROR, 'Error de base de datos al registrar en bitácora', [
                'accion' => $accion ?? 'unknown',
                'modulo' => $modulo ?? 'unknown',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            return array('respuesta' => 0, 'mensaje' => 'Error al registrar la operación');
        } catch (\Exception $e) {
            $this->logEstructurado(self::LOG_ERROR, 'Error inesperado al registrar en bitácora', [
                'accion' => $accion ?? 'unknown',
                'modulo' => $modulo ?? 'unknown',
                'exception_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
            return array('respuesta' => 0, 'mensaje' => 'Error inesperado al registrar la operación');
        }
    }

    public function consultar($limite = 100){
        try {
            $registro = "SELECT b.*, p.nombre, p.apellido, ru.nombre AS nombre_usuario,
                                DATE_FORMAT(b.fecha_hora, '%d/%m/%Y %H:%i:%s') as fecha_hora_formateada
                         FROM bitacora b
                         INNER JOIN persona p ON b.cedula = p.cedula
                         INNER JOIN usuario u ON p.cedula = u.cedula
                         INNER JOIN rol ru ON u.id_rol = ru.id_rol
                         ORDER BY b.fecha_hora DESC LIMIT :limite
                        ";
            $consulta = $this->conex2->prepare($registro);
            $consulta->bindParam(':limite', $limite, \PDO::PARAM_INT);
            $consulta->execute();

            $datos = $consulta->fetchAll(\PDO::FETCH_ASSOC);
            
            if ($datos && is_array($datos)) {
                return $datos;
            } else {
                return [];
            }
        } catch (\PDOException $e) {
            $this->logEstructurado(self::LOG_ERROR, 'Error de base de datos al consultar bitácora', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            return [];
        } catch (\Exception $e) {
            $this->logEstructurado(self::LOG_ERROR, 'Error inesperado al consultar bitácora', [
                'exception_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function contarTotal(){
        $sql = "SELECT COUNT(*) AS total FROM bitacora";
        $consulta = $this->conex2->prepare($sql);
        $consulta->execute();
        $fila = $consulta->fetch(\PDO::FETCH_ASSOC);
        return $fila['total'];
    }

   
    public function obtenerRegistro($id_bitacora) {
        try {
            // Validar que el ID sea un número válido
            if (!is_numeric($id_bitacora) || $id_bitacora <= 0) {
                return array('error' => 'ID de bitácora inválido');
            }

            $query = "SELECT b.*, p.nombre, p.apellido, p.cedula, p.correo, ru.nombre AS nombre_usuario,
                            DATE_FORMAT(b.fecha_hora, '%d/%m/%Y %H:%i:%s') as fecha_hora
                     FROM bitacora b
                     INNER JOIN persona p ON b.cedula = p.cedula
                     INNER JOIN usuario u ON p.cedula = u.cedula
                     INNER JOIN rol ru ON u.id_rol = ru.id_rol
                     WHERE b.id_bitacora = :id_bitacora";
            
            $stmt = $this->conex2->prepare($query);
            $stmt->bindParam(':id_bitacora', $id_bitacora, \PDO::PARAM_INT);
            $stmt->execute();
            
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$resultado) {
                return array('error' => 'Registro no encontrado');
            }
            
            return $resultado;
        } catch (\PDOException $e) {
            return array('error' => 'Error al obtener el registro: ' . $e->getMessage());
        } catch (\Exception $e) {
            return array('error' => 'Error inesperado: ' . $e->getMessage());
        }
    }

    public function eliminar(){
        try {
            // Validar que el ID esté establecido
            if (empty($this->id_bitacora) || !is_numeric($this->id_bitacora)) {
                return array('respuesta' => 0, 'mensaje' => 'ID de bitácora inválido');
            }

            $registro = "DELETE FROM bitacora WHERE id_bitacora = :id_bitacora";
            $strExec = $this->conex2->prepare($registro);
            $strExec->bindParam(':id_bitacora', $this->id_bitacora, \PDO::PARAM_INT);
            $result = $strExec->execute();
            
            if ($result) {
                $filas_afectadas = $strExec->rowCount();
                if ($filas_afectadas > 0) {
                    return array('respuesta' => 1, 'mensaje' => 'Registro eliminado correctamente');
                } else {
                    return array('respuesta' => 0, 'mensaje' => 'No se encontró el registro a eliminar');
                }
            } else {
                return array('respuesta' => 0, 'mensaje' => 'Error al eliminar el registro');
            }
        } catch (\PDOException $e) {
            $this->logEstructurado(self::LOG_ERROR, 'Error de base de datos al eliminar registro de bitácora', [
                'id_bitacora' => $this->id_bitacora ?? 'unknown',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            return array('respuesta' => 0, 'mensaje' => 'Error al eliminar el registro');
        } catch (\Exception $e) {
            $this->logEstructurado(self::LOG_ERROR, 'Error inesperado al eliminar registro de bitácora', [
                'id_bitacora' => $this->id_bitacora ?? 'unknown',
                'exception_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
            return array('respuesta' => 0, 'mensaje' => 'Error inesperado al eliminar el registro');
        }
    }

    // Eliminar todos los registros de la bitácora
    public function limpiarBitacora() {
        try {
            // Primero contar los registros que se van a eliminar
            $countQuery = "SELECT COUNT(*) as total FROM bitacora";
            $countStmt = $this->conex2->prepare($countQuery);
            $countStmt->execute();
            $countResult = $countStmt->fetch(\PDO::FETCH_ASSOC);
            $totalRegistros = $countResult['total'] ?? 0;

            // Proceder con la eliminación
            $registro = "DELETE FROM bitacora";
            $strExec = $this->conex2->prepare($registro);
            $result = $strExec->execute();
            
            if ($result) {
                return array(
                    'success' => true,
                    'message' => "Se eliminaron {$totalRegistros} registros de la bitácora"
                );
            } else {
                return array('success' => false, 'message' => 'Error al limpiar la bitácora');
            }
        } catch (\PDOException $e) {
            $this->logEstructurado(self::LOG_ERROR, 'Error de base de datos al limpiar bitácora', [
                'registros_previos' => $totalRegistros ?? 0,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            return array('success' => false, 'message' => 'Error al limpiar la bitácora');
        } catch (\Exception $e) {
            $this->logEstructurado(self::LOG_ERROR, 'Error inesperado al limpiar bitácora', [
                'registros_previos' => $totalRegistros ?? 0,
                'exception_type' => get_class($e),
                'error_message' => $e->getMessage()
            ]);
            return array('success' => false, 'message' => 'Error inesperado al limpiar la bitácora');
        }
    }

    // Getters y Setters
    public function get_Idbitacora() {
        return $this->id_bitacora;
    }

    public function set_Idbitacora($id_bitacora) {
        $this->id_bitacora = $id_bitacora;
    }

    public function get_Accion() {
        return $this->accion;
    }

    public function set_Accion($accion) {
        $this->accion = $accion;
    }

    public function get_Descripcion() {
        return $this->descripcion;
    }

    public function set_Descripcion($descripcion) {
        $this->descripcion = $descripcion;
    }

    public function get_IdPersona() {
        return $this->id_persona;
    }

    public function set_IdPersona($id_persona) {
        $this->id_persona = $id_persona;
    }
}