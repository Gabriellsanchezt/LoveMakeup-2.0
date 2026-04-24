<?php
namespace Seguridad;

class FileRateLimiter {
    private $storagePath;
    private $limit;
    private $window;

    /**
     * @param int $limit Número máximo de peticiones permitidas.
     * @param int $window Ventana de tiempo en segundos.
     */
    public function __construct($limit = 60, $window = 60) {
        $this->limit = $limit;
        $this->window = $window;
        $this->storagePath = __DIR__ . '/data/';
        
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }

    public function check($ip) {
        $file = $this->storagePath . md5($ip) . '.json';
        $now = time();
        $data = ['count' => 1, 'start' => $now];

        // Abrimos el archivo en modo lectura/escritura ('c+' crea si no existe)
        $handle = fopen($file, 'c+');
        
        // Bloqueamos el archivo para evitar condiciones de carrera (Race Conditions)
        if (flock($handle, LOCK_EX)) {
            $content = stream_get_contents($handle);
            if (!empty($content)) {
                $data = json_decode($content, true);
            }

            // Si ha pasado el tiempo de la ventana, reiniciamos el contador
            if (($now - $data['start']) > $this->window) {
                $data = ['count' => 1, 'start' => $now];
            } else {
                $data['count']++;
            }

            // Guardamos el nuevo estado
            ftruncate($handle, 0); // Vaciamos el archivo
            rewind($handle);       // Volvemos al inicio
            fwrite($handle, json_encode($data));
            fflush($handle);       // Forzamos la escritura al disco
            flock($handle, LOCK_UN); // Liberamos el bloqueo
        }
        
        fclose($handle);

        return $data['count'] <= $this->limit;

        // Al final del método check, antes de cerrar el archivo:
if (rand(1, 100) === 1) { // Solo corre el 1% de las veces para no afectar rendimiento
    $files = glob($this->storagePath . '*.json');
    foreach ($files as $f) {
        if (filemtime($f) < (time() - 3600)) { // Borra archivos de hace más de 1 hora
            unlink($f);
        }
    }
}
    }
}