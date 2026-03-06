<?php

namespace LoveMakeup\Proyecto\Modelo;

use LoveMakeup\Proyecto\Config\Conexion;
class VentaWeb extends Conexion
{
    public function __construct() {
        parent::__construct();
    }

    public function procesarPedido($jsonDatos)
    {
        $d = json_decode($jsonDatos, true)['datos'];
        $conex = $this->getConex1();   

        try {

          
         
          
            $conex->beginTransaction();

         
            $this->validarStockCarrito($d['carrito']);

        
            $idDireccion = $this->registrarDireccion([
                'id_metodoentrega' => $d['id_metodoentrega'],
                'cedula'           => $d['id_persona'],
                'direccion_envio'  => $d['direccion_envio'],
                'sucursal_envio'   => $d['sucursal_envio'],
                'id_delivery'      => $d['id_delivery']
            ]);

     
            $idPedido = $this->registrarPedido([
                'tipo' => $d['tipo'],
                'fecha' => $d['fecha'],
                'estatus' => $d['estado'],
                'precio_total_usd' => $d['precio_total_usd'],
                'precio_total_bs' => $d['precio_total_bs'],
                'cedula' => $d['id_persona'],
                'id_direccion' => $idDireccion
            ]);

       
            $idPago = $this->registrarPago($d);

            $this->asignarPagoAPedido($idPedido, $idPago);

      
            foreach ($d['carrito'] as $item) {

                $precio = $item['cantidad'] >= $item['cantidad_mayor']
                    ? $item['precio_mayor']
                    : $item['precio_detal'];

                $this->registrarDetalle([
                    'id_pedido' => $idPedido,
                    'id_producto' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $precio
                ]);

                $this->actualizarStock($item['id'], $item['cantidad']);
            }

          
            $conex->commit();

            return [
                'success' => true,
                'id_pedido' => $idPedido,
                'message' => 'Pedido registrado correctamente'
            ];

        } catch (\Exception $e) {

           
         
            if ($conex->inTransaction()) {
                $conex->rollBack();
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    private function registrarDireccion($d)
    {
        $conex = $this->getConex1();
        $sql = "INSERT INTO direccion(id_metodoentrega, cedula, direccion_envio, sucursal_envio,id_delivery)
                VALUES(:id_metodoentrega, :cedula, :direccion_envio, :sucursal_envio,:id_delivery)";
        $stmt = $conex->prepare($sql);
        $stmt->execute($d);
        return $conex->lastInsertId();
    }

    private function registrarPedido($d)
    {
        $conex = $this->getConex1();
        $sql = "INSERT INTO pedido(tipo, fecha, estatus, precio_total_usd, precio_total_bs, 
                                   cedula, id_direccion, id_pago)
                VALUES(:tipo, :fecha, :estatus, :precio_total_usd, :precio_total_bs,
                       :cedula, :id_direccion, NULL)";
        $stmt = $conex->prepare($sql);
        $stmt->execute($d);
        return $conex->lastInsertId();
    }

    private function registrarPago($d)
    {
        $conex = $this->getConex1();

    
        $stmt = $conex->prepare(
            "INSERT INTO detalle_pago(monto, monto_usd, id_metodopago)
             VALUES(:monto, :monto_usd, :id_metodopago)"
        );
        $stmt->execute([
            'monto' => $d['monto'],
            'monto_usd' => $d['monto_usd'],
            'id_metodopago' => $d['id_metodopago']
        ]);

        $idPago = $conex->lastInsertId();

        $stmt = $conex->prepare(
            "INSERT INTO referencia_pago(id_pago, banco_emisor, banco_receptor, referencia, telefono_emisor)
             VALUES(:id_pago, :banco_emisor, :banco_receptor, :referencia, :telefono_emisor)"
        );
        $stmt->execute([
            'id_pago' => $idPago,
            'banco_emisor' => $d['banco'],
            'banco_receptor' => $d['banco_destino'],
            'referencia' => $d['referencia_bancaria'],
            'telefono_emisor' => $d['telefono_emisor']
        ]);

  
        if (!empty($d['imagen'])) {
            $stmt = $conex->prepare(
                "INSERT INTO comprobante_pago(id_pago, imagen)
                 VALUES(:id_pago, :imagen)"
            );
            $stmt->execute([
                'id_pago' => $idPago,
                'imagen' => $d['imagen']
            ]);
        }

        return $idPago;
    }

    private function asignarPagoAPedido($idPedido, $idPago)
    {
        $conex = $this->getConex1();
        $stmt = $conex->prepare(
            "UPDATE pedido SET id_pago = :id_pago WHERE id_pedido = :id_pedido"
        );
        $stmt->execute(['id_pago' => $idPago, 'id_pedido' => $idPedido]);
    }

    private function registrarDetalle($d)
    {
        $conex = $this->getConex1();
        $stmt = $conex->prepare(
            "INSERT INTO pedido_detalles(id_pedido, id_producto, cantidad, precio_unitario)
             VALUES(:id_pedido, :id_producto, :cantidad, :precio_unitario)"
        );
        $stmt->execute($d);
    }

    private function actualizarStock($id, $cant)
    {
        $conex = $this->getConex1();
        $stmt = $conex->prepare(
            "UPDATE producto SET stock_disponible = stock_disponible - :cant
             WHERE id_producto = :id"
        );
        $stmt->execute(['cant' => $cant, 'id' => $id]);
    }

    private function validarStockCarrito($carrito) {
   
        $conex = $this->getConex1();
        foreach ($carrito as $item) {
            $stmt = $conex->prepare("SELECT stock_disponible FROM producto WHERE id_producto = :id");
            $stmt->execute(['id' => $item['id']]);
            $p = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$p) throw new \Exception("Producto no encontrado");
            if ($item['cantidad'] > $p['stock_disponible'])
                throw new \Exception("Stock insuficiente");
        }
    }

    public function obtenerMetodosPago() {
        $conex = $this->getConex1(); 
        try {
            $sql = "SELECT id_metodopago, nombre, estatus 
                    FROM metodo_pago 
                    WHERE estatus = 1"; 
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC); 
        } catch (\PDOException $e) {
            error_log("Error al obtener métodos de pago: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerMetodosEntrega() {
        $conex = $this->getConex1(); 
        try {
            $sql = "SELECT id_entrega, nombre, estatus 
                    FROM metodo_entrega 
                    WHERE estatus = 1"; 
            $stmt = $conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC); 
        } catch (\PDOException $e) {
            error_log("Error al obtener métodos de entrega: " . $e->getMessage());
            return [];
        }
    }


  

 public function detectarInyeccionSQL($valor) {
    if (empty($valor)) {
        return false;
    }
    $valor_lower = strtolower($valor);
    $patrones_peligrosos = [
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bselect\b.*\bfrom\b)/i',
        '/(\binsert\b.*\binto\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(--|\#|\/\*|\*\/)/',
        '/(\bor\b.*\b1\s*=\s*1\b)/i',
        '/(\bdrop\b|\btruncate\b|\balter\b)\s+\btable\b/i'
    ];
    foreach ($patrones_peligrosos as $patron) {
        if (preg_match($patron, $valor_lower)) {
            return true;
        }
    }
    return false;
}

public function sanitizarString($valor, $max = 100){

    if($this->detectarInyeccionSQL($valor)){
        return '';
    }

    $valor = trim($valor);
    $valor = strip_tags($valor);
    $valor = preg_replace('/[\'";<>]/', '', $valor);

    return substr($valor,0,$max);
}

public function sanitizarEntero($valor, $min = null, $max = null) {
    if (!is_numeric($valor)) {
        return null;
    }
    $valor = (int)$valor;
    if ($min !== null && $valor < $min) {
        return null;
    }
    if ($max !== null && $valor > $max) {
        return null;
    }
    return $valor;
}

public function sanitizarDecimal($valor, $min = null, $max = null) {
    if (!is_numeric($valor)) {
        return null;
    }
    $valor = (float)$valor;
    if ($min !== null && $valor < $min) {
        return null;
    }
    if ($max !== null && $valor > $max) {
        return null;
    }
    return $valor;
}

public function validarReferenciaBancaria($referencia) {
    if (empty($referencia)) {
        return false;
    }
    if (!preg_match('/^[0-9\-\s]+$/', $referencia)) {
        return false;
    }
    if (strlen($referencia) > 50) {
        return false;
    }
    return true;
}

public function validarTelefono($telefono) {
    if (empty($telefono)) {
        return false;
    }
    if (!preg_match('/^[0-9\-\s\(\)]+$/', $telefono)) {
        return false;
    }
    $longitud = strlen(preg_replace('/[^0-9]/', '', $telefono));
    if ($longitud < 7 || $longitud > 15) {
        return false;
    }
    return true;
}


public function sanitizarDireccion($direccion) {

    if (empty($direccion)) {
        return '';
    }

    if ($this->detectarInyeccionSQL($direccion)) {
        return '';
    }

    $direccion = trim($direccion);
    $direccion = preg_replace('/[<>"\']/', '', $direccion);

    if (strlen($direccion) > 500) {
        $direccion = substr($direccion, 0, 500);
    }

    return htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida y sanitiza sucursal (solo alfanuméricos y guiones)
 */
public function sanitizarSucursal($sucursal) {
    if (empty($sucursal)) {
        return '';
    }
    
    // Detectar inyección SQL
    if ($this->detectarInyeccionSQL($sucursal)) {
        return '';
    }
    
    $sucursal = trim($sucursal);
    // Solo alfanuméricos, guiones y espacios
    if (!preg_match('/^[a-zA-Z0-9\-\s]+$/', $sucursal)) {
        return '';
    }
    // Longitud máxima
    if (strlen($sucursal) > 100) {
        $sucursal = substr($sucursal, 0, 100);
    }
    return htmlspecialchars($sucursal, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida la estructura del carrito para prevenir inyecciones
 */
public function validarCarrito($carrito) {
    if (!is_array($carrito)) {
        return [];
    }

    $carrito_validado = [];
    foreach ($carrito as $item) {
        if (!is_array($item)) {
            continue;
        }

        // Validar y sanitizar cada campo del item
        $id_producto = $this->sanitizarEntero($item['id'] ?? null, 1);
        $cantidad = $this->sanitizarEntero($item['cantidad'] ?? null, 1);
        $cantidad_mayor = $this->sanitizarEntero($item['cantidad_mayor'] ?? null, 1);
        $precio_detal = $this->sanitizarDecimal($item['precio_detal'] ?? null, 0);
        $precio_mayor = $this->sanitizarDecimal($item['precio_mayor'] ?? null, 0);

        // Validar que el producto exista y esté activo
        if (!$id_producto || !$this->validarProductoActivo($id_producto)) {
            continue; // Saltar este producto si no es válido
        }

        // Solo agregar si todos los campos son válidos
        if ($id_producto && $cantidad && $precio_detal !== null && $precio_mayor !== null) {
            $carrito_validado[] = [
                'id' => $id_producto,
                'cantidad' => $cantidad,
                'cantidad_mayor' => $cantidad_mayor,
                'precio_detal' => $precio_detal,
                'precio_mayor' => $precio_mayor
            ];
        }
    }

    return $carrito_validado;
}

public function validarIdMetodoPago($id_metodopago, $metodos_pago) {
    if (empty($id_metodopago) || !is_numeric($id_metodopago)) {
        return false;
    }
    $id_metodopago = (int)$id_metodopago;
    foreach ($metodos_pago as $metodo) {
        if ($metodo['id_metodopago'] == $id_metodopago && $metodo['estatus'] == 1) {
            return true;
        }
    }
    return false;
}

/**
 * Valida que el id_metodoentrega sea válido y exista en la base de datos
 */
public function validarIdMetodoEntrega($id_metodoentrega, $metodos_entrega) {
    if (empty($id_metodoentrega) || !is_numeric($id_metodoentrega)) {
        return false;
    }
    $id_metodoentrega = (int)$id_metodoentrega;
    foreach ($metodos_entrega as $metodo) {
        if ($metodo['id_entrega'] == $id_metodoentrega && $metodo['estatus'] == 1) {
            return true;
        }
    }
    return false;
}

/**
 * Valida que el banco sea válido (lista de bancos permitidos)
 */
public function validarBanco($banco) {
    if (empty($banco)) {
        return false;
    }
    $bancos_validos = [
        '0102-Banco De Venezuela',
        '0156-100% Banco ',
        '0172-Bancamiga Banco Universal,C.A',
        '0114-Bancaribe',
        '0171-Banco Activo',
        '0166-Banco Agricola De Venezuela',
        '0128-Bancon Caroni',
        '0163-Banco Del Tesoro',
        '0175-Banco Digital De Los Trabajadores, Banco Universal',
        '0115-Banco Exterior',
        '0151-Banco Fondo Comun',
        '0173-Banco Internacional De Desarrollo',
        '0105-Banco Mercantil',
        '0191-Banco Nacional De Credito',
        '0138-Banco Plaza',
        '0137-Banco Sofitasa',
        '0104-Banco Venezolano De Credito',
        '0168-Bancrecer',
        '0134-Banesco',
        '0177-Banfanb',
        '0146-Bangente',
        '0174-Banplus',
        '0108-BBVA Provincial',
        '0157-Delsur Banco Universal',
        '0601-Instituto Municipal De Credito Popular',
        '0178-N58 Banco Digital Banco Microfinanciero S.A',
        '0169-R4 Banco Microfinanciero C.A.'
    ];
    return in_array($banco, $bancos_validos, true);
}


/* Valida que un producto exista y esté activo
*/
public function validarProductoActivo($id_producto) {
    $conex = $this->getConex1();
    $stmt = $conex->prepare("SELECT id_producto FROM producto WHERE id_producto = :id AND estatus = 1");
    $stmt->execute(['id' => $id_producto]);
    return $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
}

/**
 * Valida que el banco_destino sea válido (solo 2 opciones permitidas)
 */
public function validarBancoDestino($banco_destino) {
    if (empty($banco_destino)) {
        return false;
    }
    $bancos_destino_validos = [
        '0102-Banco De Venezuela',
        '0105-Banco Mercantil'
    ];
    return in_array($banco_destino, $bancos_destino_validos, true);
}

public function validarMetodoEntrega($metodo_entrega) {
    if (empty($metodo_entrega) || !is_numeric($metodo_entrega)) {
        return false;
    }
    $metodo_entrega = (int)$metodo_entrega;
    $metodos_validos = [1, 2, 3, 4];
    return in_array($metodo_entrega, $metodos_validos, true);
}

/**
 * Valida que la empresa_envio sea válida (2, 3 para MRW y ZOOM)
 */
public function validarEmpresaEnvio($empresa_envio) {
    if (empty($empresa_envio) || !is_numeric($empresa_envio)) {
        return false;
    }
    $empresa_envio = (int)$empresa_envio;
    $empresas_validas = [2, 3];
    return in_array($empresa_envio, $empresas_validas, true);
}

/**
 * Valida que el id_delivery sea válido y exista en la base de datos
 */
public function validarIdDelivery($id_delivery, $deliveries_activos) {
    if (empty($id_delivery) || !is_numeric($id_delivery)) {
        return false;
    }
    $id_delivery = (int)$id_delivery;
    foreach ($deliveries_activos as $delivery) {
        if ($delivery['id_delivery'] == $id_delivery) {
            return true;
        }
    }
    return false;
}

/**
 * Valida que la zona sea válida
 */
public function validarZona($zona) {
    if (empty($zona)) {
        return false;
    }
    $zonas_validas = ['norte', 'sur', 'este', 'oeste', 'centro'];
    return in_array(strtolower($zona), $zonas_validas, true);
}

/**
 * Valida que la parroquia sea válida (básica, puede expandirse según necesidades)
 */
public function validarParroquia($parroquia) {
    if (empty($parroquia)) {
        return false;
    }
    // Validación básica: no vacío y alfanumérico
    return ctype_alnum(str_replace([' ', '-', '_'], '', $parroquia));
}

/**
 * Valida que el sector sea válido (básica, puede expandirse según necesidades)
 */
public function validarSector($sector) {
    if (empty($sector)) {
        return false;
    }
    // Validación básica: no vacío y alfanumérico
    return ctype_alnum(str_replace([' ', '-', '_'], '', $sector));
}


}
?>

