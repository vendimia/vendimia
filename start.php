<?php

// Directorio base del framework
defined ('VENDIMIA\\BASE_PATH') || define ('VENDIMIA\\BASE_PATH', __DIR__);

// Directorio del proyecto
defined('VENDIMIA\\PROJECT_PATH') || define ('VENDIMIA\\PROJECT_PATH', getcwd());

// Ambiente de trabajo. Por defecto, es production.
defined('VENDIMIA\\ENVIRONMENT') || define ('VENDIMIA\\ENVIRONMENT', 'production');

// Cargamos las funciones base, y los helpers
require_once VENDIMIA\BASE_PATH . '/base/libs/helpers.php';
require_once VENDIMIA\BASE_PATH . '/base/libs/base.php';
require_once VENDIMIA\BASE_PATH . '/base/libs/Path.php';
require_once VENDIMIA\BASE_PATH . '/base/libs/Path/FileSearch.php';
require_once VENDIMIA\BASE_PATH . '/base/libs/Autoloader.php';

// Registramos el auto-cargador de clases
Vendimia\Autoloader::register();

// Forzamos la carga de excepciones, ya que hay varias dentro del mismo fichero
class_exists('Vendimia\Exception');

// La clase Vendimia va en el namespace raiz. El autocargador no podrá
// ubicarla, asi que la cargamos a mano.
require_once VENDIMIA\BASE_PATH . '/base/libs/Vendimia.php';

// Venimos del servidor de pruebas?
if (PHP_SAPI == 'cli-server') {

    // Si pedimos algo con static/, entonces lo enviamos directo
    $request_uri = trim($_SERVER['REQUEST_URI'], '/');
    if (substr($request_uri, 0, 7) == 'static/' &&
        is_dir('static/')) {
        return false;
    }

    // Cambiamos el PATH de la sesion al directorio tmp
    if (!is_dir ('tmp')) {
        mkdir ('tmp');
    }
    session_save_path ('tmp');
}

// Inicializamos las variables de la aplicación
Vendimia::init();

// Registramos el atrapador de excepciones sueltas.
Vendimia\ExceptionHandler::register();

// Cargamos las librerías en Vendimia::SETTINGS['autoload']
if (isset(Vendimia::$settings['autoload'])) {
    foreach (Vendimia::$settings['autoload'] as $pl) {
        load_lib ($pl);
    }
}

// Registramos las aplicaciones solicitadas, ejecutando el fichero
// app/register
if (isset(Vendimia::$settings['register'])) {
    foreach (Vendimia::$settings['register'] as $id => $app) {

        // Si $id NO es numérico, entonces la app es $id, y $app son algunos
        // parámetros que le pasaremos. De lo contrario, sólo registramos $app
        // sin parámetros.

        $parameters = [];

        if (!is_numeric($id)) {
            $parameters = $app;
            $app = $id ;
        }

        $fp = new Vendimia\Path\FileSearch("register");
        $fp->search_app = $app;

        if ( $fp->found() ) {
            require_once $fp->get();

            // Ejecutamos la funcion {$app}\__register()
            $fn = "$app\\__register";
            if ( is_callable($fn) ) {
                $fn( $parameters );
            }
            else {
                throw new Vendimia\AppRegisterException("Error registering application '$app': Function '$fn()' not found in $fp.");
            }
        }
        else {
            throw new Vendimia\AppRegisterException("Error registering application '$app': File 'register.php' not found.");
        }
    }
}

if (isset(Vendimia::$settings['databases'])) {
   // Cargamos las excepciones
    //class_exists ('Vendimia\\ActiveRecord\\Exception');

    Vendimia\Database\Database::initialize(Vendimia::$settings['databases']);
}

// Si viene por la CLI, salimos.
if ( Vendimia::$execution_type == 'cli') {

    // Habilitamos mostrar los errores
    ini_set ('display_errors', '1');

    return;
}

// El servidor de pruebas de PHP /siempre/ activa el modo debug
if (PHP_SAPI == 'cli-server') {
    Vendimia::$debug = true;
}

// Procesamos el request targett contra las rutas
$routes = include Vendimia\PROJECT_PATH . '/config/routes.php';
$route_process = new Vendimia\Route\Process($routes);
$route = $route_process->process(Vendimia::$request);

// Encontramos el controlador?
$found = false;

// Es un callable?
$is_callable = false;

// Si _si_ es un callable, aquí estará.
$callable = null;

// Nombre del callable
$callable_name = null;

if ($route) {
    // Esta variable contiene la ruta del controlador ubicado.
    $cfile = null;

    $application = null;
    $controller = null;

    // El fichero o callable de la ruta fue encontrado?
    foreach (['target', 'alt_target'] as $pt) {
        if (!isset($route[$pt])){
            continue;
        }

        $target = $route[$pt];

        // Es un callable?
        if (isset($route['is_callable']) && $route['is_callable']) {
            // No _debería_ exister un callable en alt_target...
            if (is_callable($route['target'], false, $callable_name)) {
                $found = true;
                $is_callable = true;
                $callable = $route['target'];
                break;
            } else {
                // Si decimos que es un callable, y no existe, fallamos
                throw new Vendimia\ControllerNotFound ("Callable '$callable_name' doesn't exists.");
            }
        }
        if (is_string($target) || is_array($target)) {
            if (is_string($target)) {
                $application = $target;
                $controller = 'default';
            } else {
                $application = $target[0];
                $controller = $target[1];
            }

            $cfile = new Vendimia\Path\FileSearch($controller, 'controllers');
            $cfile->search_app = $application;

            if ($cfile->found()) {
                $found = true;
                break;
            } else {
                // Si la regla de ruteo lo permite, probamos un controlador
                // 'default'
                if (isset($route['try_default_controller'])) {
                    $controller = 'default';
                    $cfile = new Vendimia\Path\FileSearch($controller, 'controllers');
                    if ($cfile->found()) {
                        $found = true;
                        break;
                    }
                }
            }
        }
    }


    // Si hay una ruta, y no encontró el controlador, BOOM!
    if (!$found) {
        // Quizas hemos probado el alt_target
        if (isset($route['target'])) {
            $target = $route['target'];
        } else {
            $target = $route['alt_target'];
        }
        $ac = $target[0] . ':' . $target[1];
        throw new Vendimia\ControllerNotFound("Routing rule matched, but the controller '$ac' was not found.", $route_process->getMatchedRule());
    }
}

if ($found) {
    Vendimia::$application = $application;
    Vendimia::$controller = $controller;
    if (isset($route['args'])) {
        Vendimia::$args->append($route['args']);
    }
} else {
    // 404 baby!
    Vendimia\Http\Response::notFound();
}

// Antes de ejecutar el controlador, cargamos los ficheros 'initialize';
$initialize_routes = [
    'base/initialize.php',
    "apps/" . Vendimia::$application . "/initialize.php"
];

foreach ($initialize_routes as $init) {
    $target = Vendimia\Path::join(Vendimia\PROJECT_PATH , $init);
    if (file_exists($target)) {
        require $target;
    }
}

if ($is_callable) {
    $response = $callable();
    $appcontroller = $callable_name;
} else {
    // Cargamos el controlador
    $response = require $cfile->get();

    // Nombre de la aplicación/controlador, por si tenemos que mostrar un
    // mensaje de error
    $appcontroller = Vendimia::$application . '/' . Vendimia::$controller;

}

// Si el controlador retorna algo, debe ser un Vendimia\Http\Response, o un array con
// variables para la vista por defecto
$invalid_response = false;
$view = new Vendimia\View;

if ($response) {
    if (is_array($response)) {
        $view->addVariables($response);

        $response = null;
    } elseif (is_object($response)) {
        // Sólo aceptamos objetos del tipo Vendimia\Http\Response
        if (!$response instanceof Vendimia\Http\Response) {
            $invalid_response = true;
        }
    } else {
        // Ok, aceptamos un 1
        if ( $response !== 1 ) {
            $invalid_response = true;
        } else {
            $response = null;
        }
    }
}
if ($invalid_response) {
    throw new Vendimia\Exception ("Controller '$appcontroller' must return an array, a Vendimia\\Http\\Response instance, or false. Returned " . gettype($response) . " instead." );
}

// Si en este punto no hay un response, es por que el controlador no
// devolvió uno. Lo generamos de la vista por defecto
if (!$response) {

    // Si la vista no tiene un fichero, le colocamos el nombre del controlador
    if (!$view->getFile()) {
        $view->setFile(Vendimia::$controller);
    }

    // Si no tiene un layout, buscamos uno.
    if (!$view->getLayout()) {
        // Siempre existe un default
        $layoutFile = new Vendimia\Path\FileSearch('default', 'views/layouts');
        $view->setLayout($layoutFile);
    }

    $response = $view->renderToResponse();
}

// Enviamos el Response al cliente
$response->send();
