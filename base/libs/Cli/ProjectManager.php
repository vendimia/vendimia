<?php
namespace Vendimia\Cli;

use Vendimia;

/**
 * Manages a new or existent Vendimia project.
 */
class ProjectManager
{
    /** Name of the last component of the project path */
    private $project_name;

    /** Full path of an existent project */
    private $project_path;

    /** Path received from constructor */
    private $project_raw_path;

    /** True when a Vendimia project exists in self::$project_path */
    private $project_exists = false;

    /**
     * Constructor.
     *
     * @param bool $force Process pass even if it doesn't point to a valid
     *                    vendimia project path
     */
    public function __construct($project_raw_path, $force = false)
    {
        $this->project_raw_path = $project_raw_path;

        // Buscamos la base del proyecto, si existe
        $path_parts = explode(DIRECTORY_SEPARATOR, $project_raw_path);

        $project_found = false;

        do {
            $probe_path = join(DIRECTORY_SEPARATOR, $path_parts);

            // Si existe un fichero 'config/settings.php', es un proyecto vendimia
            if (file_exists($probe_path . '/config/settings.php')) {
                $project_found = true;
                break;
            }
        } while (array_pop($path_parts));

        if ($force || $project_found) {
            $this->project_exists = true;
        }

        // Solo sacamos el nombre si existe
        if ($project_found) {
            $this->project_path = realpath($probe_path);
        } else {
            // Si no existe, lo simulamos
            $this->project_path = realpath($project_raw_path);
        }
        $this->project_name = basename($this->project_path);
    }

    /**
     * Returns if the project paths points to a valid Vendimia Project
     */
    public function isValid()
    {
        return $this->project_exists;
    }

    public function notValid()
    {
        return !$this->isValid();
    }

    /**
     * Returns the absolute project path
     */
    public function getFullPath()
    {
        return $this->project_path;
    }

    /**
     * Returns the base path where the project dir exists.
     */
    public function getBasePath()
    {
        return dirname($this->project_path);
    }

    public function getName()
    {
        return $this->project_name;
    }

    /**
     * Returns whether an app exists in this project.
     */
    public function appExists($app)
    {
        return file_exists($this->project_path . '/apps/' . $app);
    }
}
