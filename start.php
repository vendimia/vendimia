<?php

// Directorio base del framework
defined ('Vendimia\\BASE_PATH') || define('Vendimia\\BASE_PATH', __DIR__);

// Directorio del proyecto
defined('Vendimia\\PROJECT_PATH') || define('Vendimia\\PROJECT_PATH', getcwd());

// Ambiente de trabajo. Por defecto, es production.
defined('Vendimia\\ENVIRONMENT') || define('Vendimia\\ENVIRONMENT', 'production');

// Cargamos las funciones base, y los helpers. Son necesarios para el autoloader.
require_once Vendimia\BASE_PATH . '/base/libs/helpers.php';
require_once Vendimia\BASE_PATH . '/base/libs/Path.php';
require_once Vendimia\BASE_PATH . '/base/libs/Path/FileSearch.php';
require_once Vendimia\BASE_PATH . '/base/libs/Autoloader.php';

// Registramos el auto-cargador de clases
Vendimia\Autoloader::register();

// Forzamos la carga de excepciones, ya que hay varias dentro del mismo fichero
class_exists('Vendimia\Exception');

// La clase Vendimia está en el namespace raiz. El autocargador no podrá
// ubicarla, asi que la cargamos a mano.
require_once Vendimia\BASE_PATH . '/base/libs/Vendimia.php';

// Venimos del servidor de pruebas?
if (PHP_SAPI == 'cli-server') {
    // Siempre activamos el modo debug
    Vendimia::$debug = true;

    // Si pedimos algo con static/, entonces lo enviamos directo
    $request_uri = trim($_SERVER['REQUEST_URI'], '/');
    if (substr($request_uri, 0, 7) == 'static/' &&
        is_dir('static/')) {
        return false;
    }

    // Cambiamos el PATH de la sesion al directorio tmp
    if (!is_dir('tmp')) {
        mkdir('tmp');
    }
    session_save_path('tmp');
}

// Inicializamos las variables de la aplicación
Vendimia::init();

// Registramos el atrapador de excepciones sueltas.
Vendimia\ExceptionHandler::register();

if (isset(Vendimia::$settings['databases'])) {
   // Cargamos las excepciones
    //class_exists ('Vendimia\\ActiveRecord\\Exception');

    Vendimia\Database\Database::initialize(Vendimia::$settings['databases']);
}

// Si viene por la CLI, salimos.
if (Vendimia::$execution_type == 'cli') {

    // Habilitamos mostrar los errores
    ini_set('display_errors', '1');

    return;
}

$routing_rules = require Vendimia\PROJECT_PATH . '/config/routes.php';
$route_matching = new Vendimia\Routing\Match($routing_rules);
$rule = $route_matching->against(Vendimia::$request);

$target_found = false;
$application = null;
$controller = null;
$controller_object = null;

if ($rule) {
    // Una ruta hizo match.

    if ($rule['callable']) {
        // $callable_name tendrá el nombre del callable, usado para mensajes
        // de error, entre otras cosas.
        if (is_callable($rule['target'], false, $callable_name)) {
            $target_found = true;
        }
    } else {
        // Es un array [app, controller]
        list($controller_class, $controller) = $rule['target'];

        // Si la regla trae una aplicación, la usamos
        $application = $controller_class;

        // Buscamos una clase controller
        if (class_exists($controller_class)) {
            // Existe. Es una instancia de Vendimia\ControllerBase?
            if (!is_subclass_of($controller_class, Vendimia\ControllerBase::class)) {
                throw new Exception("Class '$controller_class' doesn't extends Vendimia\ControllerBase class.");
            }

            // Creamos la instancia
            $controller_object = new $controller_class();

            $application = substr($controller_class, 0, strrpos($controller_class, '\\'));

            $target_found = true;
        }

        // Ahora buscamos la ruta tradicional
        if (!$target_found) {
            list($application, $controller) = $rule['target'];
            if ($rule['fallback_target'] ?? false) {
                list($application, $controller) = $rule['fallback_target'];
            }

            $cfile = new Vendimia\Path\FileSearch($controller, 'controllers');
            $cfile->search_app = $application;

            if ($cfile->found()) {
                $target_found = true;
            }
        }

        // Si forzamos una aplicación, lo usamos
        if ($rule['application'] ?? false) {
            $application = $rule['application'];
        }

        // Reintentamos con el alterno
        /*if (!$target_found) {
            list($application, $controller) = $rule['target'];
        }*/
    }
} else {
    if (Vendimia::$debug) {
        throw new Exception("No rule matched this URL.");
    }
    Vendimia\Http\Response::notFound();
}

if ($target_found) {
    Vendimia::$application = $application;
    Vendimia::$controller = $controller;

    if ($rule['args'] ?? false) {
        Vendimia::$args->append($rule['args']);
    }
} else {
    if (Vendimia::$debug) {
        throw new Exception("No routing rule matched requested URL.");
    }

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

if ($controller_object) {
    $response = $controller_object->$controller();
} elseif ($rule['callable']) {
    $callable = $rule['target'];
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
