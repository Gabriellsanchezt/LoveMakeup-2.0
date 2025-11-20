<?php 

namespace LoveMakeup\Proyecto\Modelo;

use Dompdf\Dompdf;
use Dompdf\Options;

use LoveMakeup\Proyecto\Config\Conexion;

class Producto extends Conexion {
    private $objcategoria;
    private $objmarca;

    function __construct() {
        parent::__construct();
        $this->objcategoria = new Categoria();
        $this->objmarca = new Marca();
    }

    public function procesarProducto($jsonDatos) {
    $datos = json_decode($jsonDatos, true);
    $operacion = $datos['operacion'];
    $datosProcesar = $datos['datos'];

    // Si vienen imágenes como JSON, decodificarlas
    if (isset($datosProcesar['imagenes']) && is_string($datosProcesar['imagenes'])) {
        $datosProcesar['imagenes'] = json_decode($datosProcesar['imagenes'], true);
    }

    try {
        switch ($operacion) {
            case 'registrar':
                if ($this->verificarProductoExistente($datosProcesar['nombre'], $datosProcesar['id_marca'])) {
                    return ['respuesta' => 0, 'mensaje' => 'Ya existe un producto con el mismo nombre y marca'];
                }
                return $this->ejecutarRegistro($datosProcesar);

            case 'actualizar':
                return $this->ejecutarActualizacion($datosProcesar);

            case 'eliminar':
                return $this->ejecutarEliminacion($datosProcesar);

            case 'cambiarEstatus':
                return $this->ejecutarCambioEstatus($datosProcesar);

            default:
                return ['respuesta' => 0, 'mensaje' => 'Operación no válida'];
        }
    } catch (\Exception $e) {
        return ['respuesta' => 0, 'mensaje' => $e->getMessage()];
    }
}


    private function verificarProductoExistente($nombre, $id_marca) {
    $conex = $this->getConex1();
    try {
        $sql = "SELECT COUNT(*) FROM producto 
                WHERE LOWER(nombre) = LOWER(:nombre) 
                AND id_marca = :id_marca 
                AND estatus = 1";
        $stmt = $conex->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'id_marca' => $id_marca
        ]);
        $resultado = $stmt->fetchColumn() > 0;
        $conex = null;
        return $resultado;
    } catch (\PDOException $e) {
        if ($conex) {
            $conex = null;
        }
        throw $e;
    }
}


   private function ejecutarRegistro($datos) {
    $conex = $this->getConex1();
    try {
        $conex->beginTransaction();

        // Guardar imágenes por separado
        $imagenes = [];
        if (isset($datos['imagenes']) && is_array($datos['imagenes'])) {
            $imagenes = $datos['imagenes'];
            unset($datos['imagenes']); // eliminar antes de pasar a PDO
        }

        $sql = "INSERT INTO producto(nombre, descripcion, id_marca, cantidad_mayor, precio_mayor, precio_detal, 
                stock_disponible, stock_maximo, stock_minimo, id_categoria, estatus)
                VALUES (:nombre, :descripcion, :id_marca, :cantidad_mayor, :precio_mayor, :precio_detal, 
                0, :stock_maximo, :stock_minimo, :id_categoria, 1)";

        $stmt = $conex->prepare($sql);
        $stmt->execute($datos);
        $idProducto = $conex->lastInsertId();

        // Insertar imágenes
        if (!empty($imagenes)) {
            $sqlImg = "INSERT INTO producto_imagen(id_producto, url_imagen, tipo) VALUES(:id_producto, :url_imagen, :tipo)";
            $stmtImg = $conex->prepare($sqlImg);

            foreach ($imagenes as $indice => $rutaImagen) {
                $tipo = $indice === 0 ? 'principal' : 'secundaria';
                $stmtImg->execute([
                    'id_producto' => $idProducto,
                    'url_imagen' => $rutaImagen,
                    'tipo' => $tipo
                ]);
            }
        }

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'incluir', 'mensaje' => 'Producto registrado exitosamente'];

    } catch (\PDOException $e) {
        if ($conex) $conex->rollBack();
        throw $e;
    }
}


    
   private function ejecutarActualizacion($datos) {
    $conex = $this->getConex1();
    try {
        $conex->beginTransaction();
        $imagenesNuevas     = $datos['imagenes_nuevas']     ?? [];
        $imagenesReemplazos = $datos['imagenes_reemplazos'] ?? [];
        unset($datos['imagenes'], $datos['imagenes_nuevas'], $datos['imagenes_reemplazos']);
        $params = [
            'nombre'         => $datos['nombre'] ?? null,
            'descripcion'    => $datos['descripcion'] ?? null,
            'id_marca'       => $datos['id_marca'] ?? null,
            'cantidad_mayor' => $datos['cantidad_mayor'] ?? null,
            'precio_mayor'   => $datos['precio_mayor'] ?? null,
            'precio_detal'   => $datos['precio_detal'] ?? null,
            'stock_maximo'   => $datos['stock_maximo'] ?? null,
            'stock_minimo'   => $datos['stock_minimo'] ?? null,
            'id_categoria'   => $datos['id_categoria'] ?? null,
            'id_producto'    => $datos['id_producto'] ?? null
        ];
        if (empty($params['id_producto'])) {
            return ['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => 'Falta id_producto'];
        }

        $sql = "UPDATE producto SET 
                nombre = :nombre,
                descripcion = :descripcion,
                id_marca = :id_marca,
                cantidad_mayor = :cantidad_mayor,
                precio_mayor = :precio_mayor,
                precio_detal = :precio_detal,
                stock_maximo = :stock_maximo,
                stock_minimo = :stock_minimo,
                id_categoria = :id_categoria
                WHERE id_producto = :id_producto";

        $stmt = $conex->prepare($sql);
        $stmt->execute($params);

        if (!empty($imagenesReemplazos) && is_array($imagenesReemplazos)) {
            $sqlUpd = "UPDATE producto_imagen SET url_imagen = :url_imagen WHERE id_imagen = :id_imagen";
            $stmtUpd = $conex->prepare($sqlUpd);
            foreach ($imagenesReemplazos as $img) {
                if (!empty($img['id_imagen']) && !empty($img['url_imagen'])) {
                    $stmtUpd->execute([
                        'url_imagen' => $img['url_imagen'],
                        'id_imagen'  => $img['id_imagen']
                    ]);
                }
            }
        }

        if (!empty($imagenesNuevas) && is_array($imagenesNuevas)) {
            
            $q = $conex->prepare("SELECT COUNT(*) FROM producto_imagen WHERE id_producto = :id AND tipo = 'principal'");
            $q->execute(['id' => $params['id_producto']]);
            $tienePrincipal = $q->fetchColumn() > 0;

            $sqlIns = "INSERT INTO producto_imagen(id_producto, url_imagen, tipo) VALUES(:id_producto, :url_imagen, :tipo)";
            $stmtIns = $conex->prepare($sqlIns);
            foreach ($imagenesNuevas as $idx => $img) {
                if (!empty($img['url_imagen'])) {
                    $tipo = (!$tienePrincipal && $idx === 0) ? 'principal' : 'secundaria';
                    if ($tipo === 'principal') $tienePrincipal = true;
                    $stmtIns->execute([
                        'id_producto' => $params['id_producto'],
                        'url_imagen'  => $img['url_imagen'],
                        'tipo'        => $tipo
                    ]);
                }
            }
        }

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'actualizar', 'mensaje' => 'Producto actualizado exitosamente'];

    } catch (\PDOException $e) {
        if ($conex) $conex->rollBack();
        return ['respuesta' => 0, 'accion' => 'actualizar', 'mensaje' => $e->getMessage()];
    }
}

    private function ejecutarEliminacion($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            // Verificar stock antes de eliminar
            $sql = "SELECT stock_disponible FROM producto WHERE id_producto = :id_producto";
            $stmt = $conex->prepare($sql);
            $stmt->execute(['id_producto' => $datos['id_producto']]);
            $stock = $stmt->fetchColumn();
    
            if ($stock > 0) {
                $conex->rollBack();
                $conex = null;
                return ['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'No se puede eliminar un producto con stock disponible'];
            }
    
            $sql = "UPDATE producto SET estatus = 0 WHERE id_producto = :id_producto";
            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute($datos);
            
            if ($resultado) {
                $conex->commit();
                $conex = null;
                return ['respuesta' => 1, 'accion' => 'eliminar', 'mensaje' => 'Producto eliminado exitosamente'];
            }
            
            $conex->rollBack();
            $conex = null;
            return ['respuesta' => 0, 'accion' => 'eliminar', 'mensaje' => 'Error al eliminar producto'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }
    
    private function ejecutarCambioEstatus($datos) {
        $conex = $this->getConex1();
        try {
            $conex->beginTransaction();
            
            $nuevo_estatus = ($datos['estatus_actual'] == 2) ? 1 : 2;
            
            $sql = "UPDATE producto SET estatus = :nuevo_estatus WHERE id_producto = :id_producto";
            $stmt = $conex->prepare($sql);
            $resultado = $stmt->execute([
                'nuevo_estatus' => $nuevo_estatus,
                'id_producto' => $datos['id_producto']
            ]);
            
            if ($resultado) {
                $conex->commit();
                $conex = null;
                return ['respuesta' => 1, 'accion' => 'cambiarEstatus', 'nuevo_estatus' => $nuevo_estatus, 'mensaje' => 'Estatus cambiado exitosamente'];
            }
            
            $conex->rollBack();
            $conex = null;
            return ['respuesta' => 0, 'accion' => 'cambiarEstatus', 'mensaje' => 'Error al cambiar estatus'];
            
        } catch (\PDOException $e) {
            if ($conex) {
                $conex->rollBack();
                $conex = null;
            }
            throw $e;
        }
    }
    

    public function consultar() {
    $conex = $this->getConex1();
    try {
        $sql = "SELECT p.*,
                       c.nombre AS nombre_categoria,
                       m.nombre AS nombre_marca,
                       (
                         SELECT pi.url_imagen
                         FROM producto_imagen pi
                         WHERE pi.id_producto = p.id_producto
                         ORDER BY pi.id_imagen ASC
                         LIMIT 1
                       ) AS imagen
                FROM producto p
                INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                INNER JOIN marca m ON p.id_marca = m.id_marca
                WHERE p.estatus IN (1,2)";

        $stmt = $conex->prepare($sql);
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



    public function MasVendidos() {
    $conex = $this->getConex1();
    try {
        $sql = "
            SELECT 
                p.*,
                c.nombre AS nombre_categoria,
                m.nombre AS nombre_marca,
                SUM(pd.cantidad) AS cantidad_vendida
            FROM producto p
            INNER JOIN pedido_detalles pd ON p.id_producto = pd.id_producto
            INNER JOIN pedido pe ON pe.id_pedido = pd.id_pedido
            INNER JOIN categoria c ON p.id_categoria = c.id_categoria
            INNER JOIN marca m ON p.id_marca = m.id_marca
            WHERE 
                p.estatus = 1 
                AND pe.estatus = '2'
            GROUP BY 
                p.id_producto
            ORDER BY 
                cantidad_vendida DESC
            LIMIT 10
        ";

        $stmt = $conex->prepare($sql);
        $stmt->execute();
        $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Agregar imágenes como en ProductosActivos()
        foreach ($productos as &$prod) {
            $prod['imagenes'] = $this->obtenerImagenes($prod['id_producto']);
        }

        $conex = null;
        return $productos;

    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        throw $e;
    }
}


   public function ProductosActivos() {
    $conex = $this->getConex1();
    try {
        $sql = "
            SELECT p.*, 
                   c.nombre AS nombre_categoria,
                   m.nombre AS nombre_marca
            FROM producto p
            INNER JOIN categoria c ON p.id_categoria = c.id_categoria
            INNER JOIN marca m ON p.id_marca = m.id_marca
            WHERE p.estatus = 1
        ";
        $stmt = $conex->prepare($sql);
        $stmt->execute();
        $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Agregamos todas las imágenes de cada producto
        foreach ($productos as &$prod) {
            $prod['imagenes'] = $this->obtenerImagenes($prod['id_producto']);
        }

        $conex = null;
        return $productos;
    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        throw $e;
    }
}



public function obtenerImagenes($id_producto) {
    $conex = $this->getConex1();
    try {
        $sql = "SELECT id_imagen, url_imagen, tipo FROM producto_imagen WHERE id_producto = :id_producto";
        $stmt = $conex->prepare($sql);
        $stmt->execute(['id_producto' => $id_producto]);
        $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $conex = null;
        return $resultado;
    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        throw $e;
    }
}

public function eliminarImagenes($idsImagenes) {
    $conex = $this->getConex1();
    try {
        $sql = "DELETE FROM producto_imagen WHERE id_imagen = :id_imagen";
        $stmt = $conex->prepare($sql);

        foreach ($idsImagenes as $idImg) {
            $stmt->execute(['id_imagen' => $idImg]);
        }

        $conex = null;
        return ['respuesta' => 1, 'mensaje' => 'Imágenes eliminadas correctamente'];
    } catch (\PDOException $e) {
        if ($conex) $conex = null;
        throw $e;
    }
}

    public function obtenerCategoria() {
        return $this->objcategoria->consultar();
    }
    public function obtenerMarca() {
        return $this->objmarca->consultar();
    }
}

?>