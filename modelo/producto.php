<?php 

namespace LoveMakeup\Proyecto\Modelo;

use Dompdf\Dompdf;
use Dompdf\Options;

use LoveMakeup\Proyecto\Config\Conexion;

class Producto extends Conexion {
    private $objcategoria;

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

        // Guardar imágenes por separado
        $imagenes = [];
        if (isset($datos['imagenes']) && is_array($datos['imagenes'])) {
            $imagenes = $datos['imagenes'];
            unset($datos['imagenes']); // eliminar antes de pasar a PDO
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
        $stmt->execute($datos);

        // Actualizar imágenes si vienen
        if (!empty($imagenes)) {
            // Eliminar imágenes existentes
            $sqlDel = "DELETE FROM producto_imagen WHERE id_producto = :id_producto";
            $stmtDel = $conex->prepare($sqlDel);
            $stmtDel->execute(['id_producto' => $datos['id_producto']]);

            // Insertar nuevas imágenes
            $sqlImg = "INSERT INTO producto_imagen(id_producto, url_imagen, tipo) VALUES(:id_producto, :url_imagen, :tipo)";
            $stmtImg = $conex->prepare($sqlImg);
            foreach ($imagenes as $indice => $rutaImagen) {
                $tipo = $indice === 0 ? 'principal' : 'secundaria';
                $stmtImg->execute([
                    'id_producto' => $datos['id_producto'],
                    'url_imagen' => $rutaImagen,
                    'tipo' => $tipo
                ]);
            }
        }

        $conex->commit();
        $conex = null;
        return ['respuesta' => 1, 'accion' => 'actualizar', 'mensaje' => 'Producto actualizado exitosamente'];

    } catch (\PDOException $e) {
        if ($conex) $conex->rollBack();
        throw $e;
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
                       pi.url_imagen AS imagen
                FROM producto p
                INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                INNER JOIN marca m ON p.id_marca = m.id_marca
                LEFT JOIN producto_imagen pi 
                       ON p.id_producto = pi.id_producto AND pi.tipo = 'principal'
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
                producto.*
            FROM 
                producto
            INNER JOIN 
                pedido_detalles ON producto.id_producto = pedido_detalles.id_producto
            INNER JOIN 
                pedido ON pedido.id_pedido = pedido_detalles.id_pedido
            WHERE 
                producto.estatus = 1 AND pedido.estatus = '2'
            GROUP BY 
                producto.id_producto
            ORDER BY 
                SUM(pedido_detalles.cantidad) DESC
            LIMIT 10
        ";
         $stmt = $conex->prepare($sql);
         $stmt->execute();
         $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);
         $conex = null;
         return $resultado;
        }catch (\PDOException $e) {
            if ($conex) {
                $conex = null;
            }
            throw $e;
        }
    }

    public function ProductosActivos() {
    $conex = $this->getConex1();
    try {
        $sql = "SELECT * FROM producto WHERE estatus = 1";
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