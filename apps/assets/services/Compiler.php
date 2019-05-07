<?php
namespace assets\services;

use Vendimia;
use Vendimia\Path;

class Compiler
{
    private $class;
    private $name;

    private $fileinfo;

    public function __construct($class, $name)
    {
        $this->class = $class;
        $this->name = $name;

        // Construimos valores comunes para este asset
        $data = new \stdClass;

        // Ruta donde se guardan los assets
        $data->path = Vendimia::$settings['static_dir'] . "assets/{$this->class}/";

        // Nombre base de este asset
        $data->base = "{$this->name}.{$this->class}";

        // Fichero donde se almacena ciertas referencias.
        $data->ref = ".{$this->name}.ref";

        // El nombre final del asset, usando un valor cambiante.
        $data->real = (string)microtime(true) . ".{$data->base}";

        // Ruta completa del fichero real
        $data->full = $data->path . $data->real;

        // Ruta completa del fichero de referencia.
        $data->fullref = $data->path . $data->ref;

        $this->fileinfo = $data;
    }

    /**
     * Returns information about a asset file
     *
     * @param string $class Asset class (css, js)
     */
    private function filename()
    {
        return $this->fileinfo;
    }

    /**
     * Create a empty ref file as lock mechanism.
     */
    public function lock()
    {
        if (Vendimia::$debug) {
            return false;
        }

        Path::makeDir($this->fileinfo->path);
        touch ($this->fileinfo->fullref);
    }

    public function save($data) {
        if (Vendimia::$debug) {
            return false;
        }

        // Creamos la estructura


        file_put_contents($this->fileinfo->full, $data);
        file_put_contents($this->fileinfo->fullref, $this->fileinfo->real);
    }

    /**
     * Returns the URL for an asset, checking if it's cached.
     */
    public function url() {
        if (!Vendimia::$debug && file_exists($this->fileinfo->fullref)) {
            $fn = file_get_contents($this->fileinfo->fullref);

            return Vendimia::$settings['static_url'] . "assets/{$this->class}/{$fn}";
        }
        else {
            return "assets/{$this->class}?source={$this->name}";
        }
    }
}
