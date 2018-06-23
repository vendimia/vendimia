<?php
namespace Vendimia;

use Vendimia;

/**
 * Class autoloader.
 */
class Autoloader
{
    private static $namespaces = [
        'Vendimia\\' => [
            [VENDIMIA\BASE_PATH, 'base/libs'],
        ],
    ];

    /**
     * Class file locator
     *
     * @param string $class Full qualified class name
     *
     * @return string|null
     */
    public static function locate($class)
    {
        $search_paths = [];

        // Primero exploramos las clases que no tienen un namespace
        // definido en self::$namespaces. Pueden ser del proyecto, o
        // pueden ser de terceras partes, dentro de vendor/

        if (defined('Vendimia\\PROJECT_PATH')) {
            $search_paths = [
                [Vendimia\PROJECT_PATH, 'apps'],
                [Vendimia\PROJECT_PATH, 'base'],
                [Vendimia\PROJECT_PATH, 'vendor'],
            ];
        }

        $search_paths = array_merge($search_paths,[
            [Vendimia\BASE_PATH, 'apps'],
            [Vendimia\BASE_PATH, 'vendor'],
        ]);

        $file_path = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
        foreach ($search_paths as $base_path) {
            $path = Vendimia\Path::join($base_path, $file_path);
            // Existe?
            if (file_exists($path)) {
                return $path;
            }
        }

        // Ahora exploramos los namespaces
        foreach (self::$namespaces as $namespacename => $paths) {
            $namelen = strlen($namespacename);
            if (substr($class, 0, $namelen) == $namespacename) {
                $subnamespace = substr($class, $namelen);
                $file_path = strtr($subnamespace, '\\', DIRECTORY_SEPARATOR) . '.php';
                // Good. Existe el fichero?
                foreach ($paths as $path) {
                    $target = Vendimia\Path::join($path, $file_path);
                    if (file_exists($target)) {
                        return $target;
                    }
                }
            }
        }

        // No encontramos nada.
        return null;
    }

    public static function autoloader($class)
    {
        // Ubicamos el fichero
        $path = self::locate($class);

        if ($path) {
            require $path;
        }
    }

    public static function register()
    {
        // No nos registramos en el CLI, por que eso sucede antes
        if (class_exists('Vendimia') && Vendimia::$execution_type == 'cli') {
            return;
        }

        spl_autoload_register('self::autoloader');

        // Añadimos soporte por defecto para composer
        $composer_autoloader = Vendimia\PROJECT_PATH . '/vendor/autoload.php';

        if (file_exists($composer_autoloader)) {
            require_once $composer_autoloader;
        }
    }
}
