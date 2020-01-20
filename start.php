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

// Esto tiene que suceder nuevamente.
if (PHP_SAPI == 'cli-server') {
    // Siempre activamos el modo debug
    Vendimia::$debug = true;
}

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

// Antes de ejecutar el controlador, cargamos los ficheros 'initialize';
// FIXME: OBSOLETO
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




// Procesamos las rutas
$routing_rules = new Vendimia\Routing\Rules(
    require Vendimia\PROJECT_PATH . '/config/routes.php'
);
$route_matching = new Vendimia\Routing\Match($routing_rules);
$rule = $route_matching->against(Vendimia::$request);

$returned_data = null;

// En caso el controlador no retorne un Http\Response, creamos uno vacío, donde
// añadiremos la vista luego.
$response = new Vendimia\Http\Response();
$response->setHeader('Content-Type', 'text/html');

$view_variables = [];

if ($rule->matched) {
    // Una regla hizo match, directa o indirectamente
    if ($rule->target_type == 'class') {
        $controller_object = new $rule->target[0](
            Vendimia::$request,
            $response,
            $rule
        );

        // FIXME: Esto ya debería desaparecer
        Vendimia::$application = $rule->target_app;

        // Ejecutamos el método
        $returned_data = $controller_object->executeMethod($rule->target[1]);


        //$returned_data = $controller_object->{$rule->target[1]}();

    } elseif ($rule->target_type == 'callback') {
        // TODO:
    } elseif ($rule->target_type == 'legacy') {

        // FIXME: Esto ya debería desaparecer
        Vendimia::$application = $rule->target[0];
        Vendimia::$controller = $rule->target[1];
        Vendimia::$args->append($rule->args);

        $cfile = new Vendimia\Path\FileSearch($rule->target[1], 'controllers');
        $cfile->search_app = $rule->target[0];

        if ($cfile->notFound()) {
            throw new Vendimia\Exception("'Legacy' route matched, but controller file '{$rule->target[1]}' not found", [
                'Matched rule' => $rule->asArray(),
            ]);
        }

        $returned_data = require $cfile->get();

    } elseif ($rule->target_type == 'view') {
        (new Vendimia\View($rule->target))->renderToResponse()->send();
    }
} else {
    // 404!
    if (Vendimia::$debug) {
        throw new Vendimia\Exception("No routing rule matched requested URL", [
            'Rules' => $routing_rules->getHumanList(),
        ]);
    }

    Vendimia\Http\Response::notFound();

}

// Si el controlador retorna algo, solo puede ser un array con variables para
// la vista, o un objecto de clase Vendimia\Http\Response
$invalid_return = false;
if ($returned_data) {
    if (is_object($returned_data)) {
        if ($returned_data instanceof Vendimia\Http\Response) {
            // La enviamos directo al cliente
            $returned_data->send();
        }

        $invalid_return = true;
    } elseif (is_array($returned_data)) {
        $view_variables = array_merge($view_variables, $returned_data);
    } else {
        // Aceptamos un 1
        if ($returned_data !== 1) {
            $invalid_return = true;
        }
    }
}
if ($invalid_return) {
    throw new Vendimia\Exception(
        "Controller for rule '{$rule->target_name}' must return an Array, a Vendimia\\Http\\Response instance, or null. Returned a " . gettype($returned_data) . " instead."
    );
}

$view = new Vendimia\View();
$view->setApplication($rule->target_app);

foreach($rule->target_resources as $view_file) {
    try {
        $view->setFile($view_file);
    } catch (Vendimia\Exception $e) {
        continue;
    }
}

// Si no hay fichero, fallamos
if (!$view->getFile())  {
    throw new Vendimia\Exception(
        "View file cannot be found for controller {$rule->target_name}",
    [
        'Rule matched' => $rule->rule,
        'Searched view names' => $rule->target_resources,
    ]);
}

// Si no tiene un layout por defecto, buscamos uno.
if (!$view->getLayout()) {
    $view->setLayout('default');
}


// Insertamos la vista, con las variables, dentro del response.
$view->addVariables($view_variables);

$body = new Vendimia\Http\Stream('php://temp');
$body->write($view->renderToString());
$response->setBody($body);
$size = $response->getBody()->getSize();
if ($size) {
    $response->setHeader('Content-Length', $size);
}
$response->send();
