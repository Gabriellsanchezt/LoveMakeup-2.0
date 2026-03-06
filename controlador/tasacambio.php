<?php
use LoveMakeup\Proyecto\Modelo\Tasacambio;   
// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['id'])) {
    require_once 'verificarsession.php';
} 

require_once 'permiso.php';

$objtasa = new Tasacambio();


function validarEntradaSQL($input) {
        // Si es array → validar cada elemento
        if (is_array($input)) {
            foreach ($input as $valor) {
                if (!validarEntradaSQL($valor)) { 
                    return false;
                }
            }
            return true;
        }
        // Convertir a string por seguridad
        $input = (string)$input;

        $blacklist = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER',
            'CREATE', 'RENAME', 'REPLACE', 'UNION', 'JOIN', 'WHERE', 'HAVING',
            'FROM', 'TABLE', 'DATABASE', 'SCHEMA', 'GRANT', 'REVOKE',
            '--', ';', '#', '/*', '*/', '@@', '@', 'CHAR', 'CAST', 'CONVERT',
            'EXEC', 'EXECUTE', 'xp_', 'sp_', 'OR', 'AND'
        ];
      
        foreach ($blacklist as $prohibida) {
            $pattern = '/\b' . preg_quote($prohibida, '/') . '\b/i'; 
            if (preg_match($pattern, $input)) {
                return false;
            }
        }
        return true;
    }



$registro = $objtasa->consultar();

if(isset($_POST['modificar'])){
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(14, 3)) { /* V2 */ 
            
            if (!empty($_POST['fecha']) && !empty($_POST['tasa']) && !empty($_POST['fuente'])) {  /* V3 */

                // Validar y sanitizar datos
                $fecha = isset($_POST['fecha']) ? filter_var($_POST['fecha'], FILTER_SANITIZE_STRING) : '';
                $tasa = isset($_POST['tasa']) ? filter_var($_POST['tasa'], FILTER_VALIDATE_FLOAT) : false;
                $fuente = isset($_POST['fuente']) ? filter_var($_POST['fuente'], FILTER_SANITIZE_STRING) : 'Manualmente';
                
                if ($tasa === false || $tasa <= 0) {
                    echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => 'La tasa debe ser un número válido mayor a 0']);
                    exit;
                }

                $campos = [
                    'Fecha' => $fecha,
                    'Tasa' => $tasa,      
                    'Fuente' => $fuente
                ];

                /// Sanitización de Entradas
                    foreach ($campos as $nombre => $valor) {  /* V4 */ 
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => "#0400 - Entrada inválida detectada en el campo: $nombre"]);
                            exit;
                        }
                    }
                        // Validar formato de fecha
                        $fechaValidada = DateTime::createFromFormat('Y-m-d', $fecha);
                        if (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fecha) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => '#0510 - Formato de fecha inválido']);
                            exit;
                        }

                        if (!preg_match('/^\d{1,5}([.,]\d{1,3})?$/', $tasa) || strlen(str_replace([',','.'],'',$tasa)) < 4 || strlen(str_replace([',','.'],'',$tasa)) > 8) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => "#0510 - Tasa inválida ($tasa)"]);
                            exit;
                        }
                
                        $datosTasa = [
                            'operacion' => 'modificar',
                            'datos' => [
                                'fecha' => $fecha,
                                'tasa' => $tasa,
                                'fuente' => $fuente
                            ]
                        ]; 

                        $resultado = $objtasa->procesarTasa(json_encode($datosTasa));

                            if($resultado['respuesta'] == 1){
                                $resultado1 = $objtasa->consultaTasaUltima();
                                    if (!empty($resultado1)) {
                                        $_SESSION["tasa"]   = $resultado1;
                                    
                                    }
                            }

                        echo json_encode($resultado);

            } else{  /* V3 datos vacios */
                echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => '#0300 - Datos Vacios']);
                exit; 
            }    
        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => '#0200 - No Tiene Permiso para realizar esta operacion']);
            exit;
        }      
    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'modificar', 'text' => '#0100 - Session no encontrada']);
        exit;
    } 

} else if(isset($_POST['sincronizar'])){
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) { /* V1 */
        if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(14, 3)) { /* V2 */ 
            
            if (!empty($_POST['fecha']) && !empty($_POST['tasa']) && !empty($_POST['fuente'])) {  /* V3 */

                // Validar y sanitizar datos
                $fecha = isset($_POST['fecha']) ? filter_var($_POST['fecha'], FILTER_SANITIZE_STRING) : '';
                $tasa = isset($_POST['tasa']) ? filter_var($_POST['tasa'], FILTER_VALIDATE_FLOAT) : false;
                $fuente = isset($_POST['fuente']) ? filter_var($_POST['fuente'], FILTER_SANITIZE_STRING) : 'Via Internet';
                
                // Validaciones
                if(empty($tasa) || $tasa === false){
                    echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'Tasa no encontrada o inválida']);
                    exit;
                } 
                
                if($tasa <= 0){
                    echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'Error: La tasa debe ser mayor a 0']);
                    exit;
                }
                
                $campos = [
                    'Fecha' => $fecha,
                    'Tasa' => $tasa,      
                    'Fuente' => $fuente
                ];

                /// Sanitización de Entradas
                    foreach ($campos as $nombre => $valor) {  /* V4 */ 
                        if (!validarEntradaSQL($valor)) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => "#0400 - Entrada inválida detectada en el campo: $nombre"]);
                            exit;
                        }
                    }
                        // Validar formato de fecha
                        $fechaValidada = DateTime::createFromFormat('Y-m-d', $fecha);
                        if (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fecha) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => 'Formato de fecha inválido']);
                            exit;
                        }

                        if (!preg_match('/^\d{1,5}([.,]\d{1,3})?$/', $tasa) || strlen(str_replace([',','.'],'',$tasa)) < 4 || strlen(str_replace([',','.'],'',$tasa)) > 8) {
                            echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => "#0510 - Tasa inválida ($tasa)"]);
                            exit;
                        }
                             
                            $datosTasa = [
                                'operacion' => 'sincronizar',
                                'datos' => [
                                    'fecha' => $fecha,
                                    'tasa' => $tasa,
                                    'fuente' => $fuente
                                ]
                            ]; 

                            $resultado = $objtasa->procesarTasa(json_encode($datosTasa));
                            if($resultado['respuesta'] == 1){
                                $resultado1 = $objtasa->consultaTasaUltima();
                                    if (!empty($resultado1)) {
                                        $_SESSION["tasa"]   = $resultado1;
                                    
                                    }
                            }
                            echo json_encode($resultado);

            } else{  /* V3 datos vacios */
                echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => '#0300 - Datos Vacios']);
                exit; 
            }

        } else{  /* 2 */ 
            echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => '#0200 - No Tiene Permiso para realizar esta operacion']);
            exit;
        }     

    } else{ /* V1 */ 
        echo json_encode(['respuesta' => 0, 'accion' => 'sincronizar', 'text' => '#0100 - Session no encontrada']);
        exit;
    } 
    
} else if(isset($_POST['obtener_tasa_actual']) || (isset($_GET['obtener_tasa_actual']) && $_GET['obtener_tasa_actual'] == '1')) {
    // Endpoint para obtener la tasa actual desde la base de datos
    header('Content-Type: application/json; charset=utf-8');
    try {
        $tasa = $objtasa->obtenerTasaActual();
        if ($tasa && isset($tasa['tasa_bs'])) {
            echo json_encode([
                'respuesta' => 1,
                'tasa' => floatval($tasa['tasa_bs']),
                'fecha' => $tasa['fecha'],
                'fuente' => $tasa['fuente'] ?? 'Base de datos'
            ]);
        } else {
            echo json_encode([
                'respuesta' => 0,
                'mensaje' => 'No se encontró una tasa de cambio en la base de datos'
            ]);
        }
    } catch (\Exception $e) {
        echo json_encode([
            'respuesta' => 0,
            'mensaje' => 'Error al obtener la tasa de cambio: ' . $e->getMessage()
        ]);
    }
    exit;
    
} else if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(14, 1)) {
     $pagina_actual = isset($_GET['pagina']) ? $_GET['pagina'] : 'tasacambio';
    require_once 'vista/tasacambio.php'; // Asegúrate de tener esta vista
} else {
    require_once 'vista/seguridad/privilegio.php';
}

