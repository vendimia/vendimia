<?php

/**
 * Holding class for various App-level information
 */
class Vendimia
{
    /** Base URL for this project */
    static public $base_url;

    /** Session variables */
    static public $session;

    /** Vendimia\Request object */
    static public $request;

    /** Execution type. Default is 'web'. */
    static public $execution_type = 'web';

    /** Active application name */
    static public $application;

    /** Active controller name */
    static public $controller;

    /** Vendimia\View object, containing the main view */
    static public $view;

    /** Arguments passed to the controller */
    static public $args;

    /** Settings array, from config/settings.php */
    static public $settings;

    /** Holder for the active user, if this project uses that.*/
    static public $active_user;

    /** Debug state */
    static public $debug = false;

    /** CSRF object */
    static public $csrf;

    /** Default Vendimia Logger */
    static public $logger;

    /** Service container */
    static public $service = null;

    /**
     * Initialize the application
     */
    static public function init() {
        // Cargamos la configuración de la aplicación
        self::$settings = require Vendimia\PROJECT_PATH . '/config/settings.php';

        // Cargamos la configuración seguín este ambiente de trabajo
        $target = Vendimia\PROJECT_PATH . '/config/settings.' . Vendimia\ENVIRONMENT . '.php';

        if (file_exists($target)) {
            $more_settings = require $target;
            self::$settings = array_replace_recursive (self::$settings,
                $more_settings);
        }

        // Copiamos el estado de debug de la configuracion
        self::$debug = self::$settings['debug_mode'];

        // Venimos por el cli?
        if (PHP_SAPI == 'cli') {
            self::$execution_type = 'cli';
        }

        // Seteamos la zona horaria
        if (isset(Vendimia::$settings['time_zone'])) {
            date_default_timezone_set(Vendimia::$settings['time_zone']);
        }

        if (isset(Vendimia::$settings['locale'])) {
            setlocale(LC_ALL, Vendimia::$settings['locale']);
        }

        // No estoy seguro si esto es bueno que esté hardcoded...
        setlocale(LC_NUMERIC, 'C');

        // Todo el encoding será utf-8
        mb_internal_encoding('utf-8');

        // Creamos el placeholder para los argumentos
        self::$args = new Vendimia\Args;

        // Y creamos el request
        if (PHP_SAPI != 'cli') {
            self::$request = Vendimia\Http\Request::fromSuperGlobals();
        }

        // Sesión
        self::$session = new Vendimia\Session;

        // Protección contra csrf
        self::$csrf = new Vendimia\Csrf;

        // ServiceLocator
        self::$service = new Vendimia\ServiceContainer;

        // El logger por default
        self::$logger = new Vendimia\Logger\Logger;
        $level = Vendimia::$settings['logger']['level'];
        $target = Vendimia::$settings['logger']['target'];
        self::$logger->addTarget(new $target, $level);

        // Obtenemos la ruta web de este proyecto. Usamos
        // la ruta web de index.php para sacar la raiz.
        $base_url = dirname($_SERVER['SCRIPT_NAME']);

        // Debe acabar en un /
        $base_url .= (substr($base_url, -1) != '/'?'/':'');

        self::$base_url = $base_url;
    }
}
