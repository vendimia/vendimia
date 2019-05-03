<?php
namespace Vendimia\Path;

use Vendimia;
use Vendimia\Path;
/**
 * Search for a file in several locations.
 *
 * The file is search first on the actual application directory, then
 * on the project 'base' directory, then on the Vendimia base directory.
 *
 * @author Oliver Etchebarne
 */
class FileSearch
{
    /** La extensión del fichero a buscar */
    public $ext = 'php';

    /** Busca el fichero en la carpeta base del proyecto */
    public $search_base = true;

    // Busca el fichero dentro de la carpeta de Vendimia, apps y base
    public $search_Vendimia = true;

    // Busca en otra aplicación que no sea la actual.
    public $search_app = null;

    // Rutas donde se buscó el fichero
    public $searched_paths = [];

    // Ruta donde se encontró el fichero
    public $path = null;

    // Nombre del fichero a buscar
    public $file;

    // Tipo de fichero. Básicamente la subcarpeta.
    public $type;

    // Ya buscó?
    private $searched = false;

    /**
     * Constructor.
     */
    public function __construct($file = null, $type = '', $ext = 'php')
    {
        $this->file = $file;
        $this->type = $type;
        $this->ext = $ext;
    }

    /**
     * Returns true if file was not found.
     *
     * @return bool
     */
    public function notFound()
    {
        if (!$this->searched) {
            $this->search();
        }

        return is_null($this->path);
    }

    /**
     * Returns true if file was found.
     *
     * @return bool
     */
    public function found()
    {
        return !$this->notFound();
    }

    /**
     * Reset the search
     */
    public function reset()
    {
        $this->searched = false;
    }

    /**
     * Returns the found file path
     *
     * @return string
     */
    public function get()
    {
        if (!$this->searched) {
            $this->search();
        }

        return $this->path;
    }

    /**
     * Search the file in the paths
     */
    private function search()
    {
        // Añadimos la extensión, si existe
        if ($this->ext) {
            $this->file .= '.' . $this->ext;
        }

        $app = false;

        // Si el fichero empieza con '::', entonces sólo buscamos en la base
        if (substr($this->file, 0, 2) == '::') {
            $this->file = substr($this->file, 2);
            $this->search_base = true;
        } else {
            // Si hay un :, entonces estamos buscando el fichero dentro de otra
            // aplicación. No buscamos en base
            $colon = strpos ( $this->file, ':' );

            if ( $colon !== false ) {
                $this->search_app = substr($this->file, 0, $colon);
                $this->file = substr($this->file, $colon + 1);

                $this->search_base = false;
            }


            // La app donde buscar.
            $app = $this->search_app ? $this->search_app : Vendimia::$application;

        }

        // Armamos las rutas de búsqueda
        if ($app) {
            $this->searched_paths = [
                Path::join(Vendimia\PROJECT_PATH, 'apps', $app, $this->type, $this->file),
            ];
        }

        // Buscamos en base?
        if ($this->search_base) {
            $this->searched_paths[] =
                Path::join(Vendimia\PROJECT_PATH , 'base', $this->type, $this->file);

        }

        // Buscamos en Vendimia?
        if ($this->search_Vendimia) {
            if ($app) {
                $this->searched_paths[] =
                    Path::join(Vendimia\BASE_PATH, 'apps', $app, $this->type, $this->file );
            }

            $this->searched_paths[] =
                Path::join(Vendimia\BASE_PATH, 'base', $this->type, $this->file );
        }

        // Existe el fichero?
        foreach ( $this->searched_paths as $f ) {
            if (file_exists($f)) {
                $this->path = $f;
                break;
            }
        }
        $this->searched = true;
    }
}
