<?php
use Vendimia\Console;

const VENDIMIA_VERSION = "0.0.1";
const VENDIMIA_BASE_INCLUDED = true;

// Realizamos chequeos del sistema.

// Versión de PHP > 5.6
if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    exit("FATAL: Vendimia requires PHP version at least 5.6.\n");
}

// Necesitamos mbstrings si o si
if (!function_exists('mb_check_encoding')) {
    exit("FATAL: Vendimia requires the 'mbstring' PHP extension properly installed.\n");
}

// Necesitamos el fileinfo
if (!class_exists('\\finfo')) {
    exit("FATAL: Vendimia requires the 'fileinfo' PHP extension properly installed.\n");
}

# La ruta de la instalacióin del Vendimia, es el padre de esta carpeta.
define ('Vendimia\BASE_PATH', dirname( __DIR__ ));

require Vendimia\BASE_PATH . '/base/libs/helpers.php';
require Vendimia\BASE_PATH . '/base/libs/Path.php';
require Vendimia\BASE_PATH . '/base/libs/Cli.php';

/**
 * Clase para manejar la información del proyecto, para los scripts de
 * administración.
 */
class bin {

    // Argumentos
    static $args = [];

    // Nombre del script que vamos a llamar.
    static $script_name = '';

    // Ruta del proyecto, donde existe o donde se creará
    static $project_path = '';

    // Hay un proyecto en esa ruta?
    static $project_exists = false;

    // Nombre del proyecto. Usualmente el último componente de la ruta
    static $project_name = '';

    // Containe used for some Vendimia scripts
    static $module = null;

    /**
     * Determina si hay un proyecto Vendimia en la carpeta dada
     */
    static function is_Vendimiaproject ($path)
    {
        $target = Vendimia\Path::join($path,'config/settings.php');
        if (file_exists($target)) {
            // Ok.
            return true;
        }
        return false;
    }


    /**
     * Analiza la línea de comandos, ubica el proyecto donde ejecutamos
     * este script.
     */
    static function init()
    {
        // Weird PHP...
        global $argv;

        // Registramos el autoloader a mano
        require Vendimia\BASE_PATH . "/base/libs/Autoloader.php";
        spl_autoload_register('Vendimia\\Autoloader::autoloader');

        // Registramos el ExceptionHandler de la consola
        set_exception_handler ('Vendimia\\Console\\ExceptionHandler::handler');

        // Buscamos argumentos largos
        foreach ( $argv as $id => $a ) {
            if ( substr ($a, 0, 2) == '--' )  {
                $long_arg = substr ( $a, 2 );

                $value = true;

                // Tiene un valor?
                $equal = strpos ( $long_arg, '=' );

                if ( $equal !== false ) {
                    $value = substr ( $long_arg, $equal + 1 );
                    $long_arg = substr ( $long_arg, 0, $equal);
                }

                self::$args[ $long_arg ] = $value ;

                unset ( $argv[$id]) ;
            }
        }

        // Ignoramos el primer elemento.
        array_shift ( $argv );
        self::$script_name = strtolower(array_shift($argv));

        // Verificamos si el siguiente parámetro es la ruta de un proyecto
        if ( isset ( $argv[0] ) ) {
            if ( self::is_Vendimiaproject ( $argv[0] ) ) {
               self::$project_path = $argv[0];
               array_shift ( $argv );
            }
        }

        // Si no hay ruta, usamos el CMD probamos desde el CWD hacia arriba
        if (!self::$project_path) {

            // Empezamos a buscar.
            $path = getcwd();

            // Dividimos la ruta en sus partes. Y vamos explorando
            $parts = array_filter ( explode ( '/', $path ) );
            while ( $parts ) {

                // Empezamos por la raiz siempre
                $test_path = '/' . Vendimia\Path::join( $parts );

                if ( self::is_Vendimiaproject ( $test_path ) ) {
                    // Perfecto
                    self::$project_path = $test_path;
                    break;
                }

                // Sacamos el último elelento.
                array_pop ( $parts );
            }
        }

        // Si existe una ruta del proyecto, nos fijamos si hay un proyecto ahi
        if (self::$project_path) {
            self::$project_exists = self::is_Vendimiaproject ( self::$project_path );
        }

        // Si existe un proyecto, lo cargamos.
        if (self::$project_exists) {
            self::$project_name = basename ( self::$project_path );

            // Colocamos la ruta completa del proyecto
            self::$project_path = realpath ( self::$project_path  );

            // Cargamos el index del proyecto
            chdir ( self::$project_path );

            //require Vendimia\BASE_PATH . '/start.php';
            $project_loaded = false;
            if (file_exists('index.php')) {
                require 'index.php';
            } else {
                if (file_exists('index_path')) {
                    $path = trim(file_get_contents('index_path'));
                    $index_path = Vendimia\Path::join($path, 'index.php');

                    if (file_exists($index_path)) {
                        require $index_path;
                    } else {
                        Vendimia\Console::fail("I can't found the file 'index.php' in '$path'.");

                    }
                } else {
                    Vendimia\Console::fail("Neither 'index.php' or 'index_path' files are found in the project.");
                }
            }
        }

        // Cremos un objeto en el contenedor para los modulos
        self::$module = new \StdClass;

        // Mostramos todos los errores
        if (!ini_get('display_errors')) {
            ini_set('display_errors', '1');
        }
    }

    /**
     * Draws parameters and its description in a fancy way.
     */
    static public function writeFancyDefinitions(array $definitions)
    {
        // Primero, ubicamos el padding adecuado
        $max_length = 0;
        foreach (array_keys($definitions) as $def) {
            $lenght = mb_strlen($def);
            if ($lenght > $max_length) {
                $max_length = $lenght;
            }
        }

        $base_padding = 5;
        $descr_padding =  $max_length + 5;

        foreach ($definitions as $def => $descr) {

            $options = [];
            if (is_array($descr)) {
                $options = $descr;
                $descr = $descr['description'];

                if (isset($options['optional'])) {
                    $descr = $descr . ' (optional)';
                }
            }

            $var = Console::color('white', $def);

            $values = explode("\n", $descr);

            $line = str_repeat(' ', $base_padding) . $var;
            $line .= str_repeat(' ', $descr_padding - mb_strlen($def));

            $line .= array_shift($values) . PHP_EOL;

            $newline_padding = str_repeat(' ', $descr_padding + $base_padding);

            while ($data = array_shift($values)) {
                $line .= $newline_padding . $data . PHP_EOL;
            }
            echo $line;
        }

    }


    /**
     * Shows a pretty header
     */
    static function writeHeader()
    {
        $VENDIMIA_VERSION = VENDIMIA_VERSION;
        Console::write(<<<EOF
{white Vendimia Framework} Administration utility script.
Version $VENDIMIA_VERSION

EOF
);
    }

    /**
     * Shows this help if --help parameter is found.
     */
    static function help($description, $cmdline, array $parameters,
        $force = false)
    {
        if (isset(bin::$args['help']) || $force) {

            $name = self::$script_name;
            if (isset(self::$module->name)) {
                $name .= ' ' . self::$module->name;
            }

            self::writeHeader();
            console::write('{green Vendimia ' . $name . "}: $description");
            console::write ("\nSyntax: Vendimia $name $cmdline\n");

            self::writeFancyDefinitions($parameters);
            echo PHP_EOL;

            exit;
        }
    }
}

bin::init();
