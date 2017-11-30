<?php
use Vendimia\Cli;
use Vendimia\Path;
use Vendimia\Console;

if (count($argv) == 0) {
    // Solo ejecutamos este help si viene con un único parámetro
    bin::help('Creates new elements for an existing Vendimia project.', 
        '[base] element [for] app_name element_name [extra_parameters]',
        [
            'base' => [
                'optional' => true,
                'description' => 'Creates a element inside base/ directory.',
            ],
            'element' => 'Name of the new element to be created. Valid options are:' .
                "\n- app\n- model\n- view\n- controller\n- form\n- tabledef",
            'for' => [
                'optional' => true,
                'description' => 'Syntactic sugar word.'
            ],
            'app_name' => 'Name of an existent app.',
            'extra_parameters' => 'Parameters for the new element.',
        ], true);
}

// Debe existir el proyecto
if (!bin::$project_exists) {
    Console::fail ( "Vendimia project not found." );
}

// el módulo 'new' a ejecutar
$module = strtolower (array_shift ($argv));

// Verificamos el térmimo  'base'
bin::$module->is_base = false;
if ( $module == 'base' ) {
    bin::$module->is_base = true;

    // La siguiente palabra es el módulo
    $module = strtolower(array_shift($argv));
} else {
    // Si no es base, necesitaremos el nombre de la aplicación donde se
    // ejecutará el módulo. Ignoramos la palabra 'for'

    $app = strtolower (array_shift($argv));

    if ($app == 'for') {
        $app = strtolower(array_shift($argv));
    }

    // Si no hay app, no fallamos.
    if ($app) {
        bin::$module->app = $app;

        // Existe?
        bin::$module->app_exists = is_dir(Vendimia\Path::join(bin::$project_path, "apps/$app"));
    }

}
bin::$module->name = $module;

// La siguiente palabra es el nuevo nombre del elemento
bin::$module->element = strtolower (array_shift($argv));

// Ejecutamos el sub-script
$module_file = Vendimia\BASE_PATH . "/bin/vendimia-new-" . bin::$module->name . ".php";
if (file_exists($module_file)) {

    // El módulo 'app' es distinto. No mostramos mensajes 
    if (bin::$module->name != 'app' && !isset(bin::$args['help'])) {
        // Si no existe la app, fallamos
        if (!isset(bin::$module->app)) { 
            Console::fail ( 'You must specify an application name.');
        }

        // Necesitamos el nombre del nuevo elemento siepre
        if (!bin::$module->element) {
            Console::fail('New element name missing.');
        } 
        
        if (!(bin::$module->is_base || bin::$module->app_exists)) {
            Console::fail ("Application {:app " . bin::$module->app . "} doesn't exist." );
        }

        // Colocamos un mensaje de lo que vamos a hacer
        if ( bin::$module->is_base ) {
            Console::write ( "Creating new {white base} " . bin::$module->name .
            " {:" . bin::$module->name . ' ' . bin::$module->element . "}..." );
        }
        else {
            Console::write ( "{:app " . bin::$module->app . "}: Creating new " . 
                bin::$module->name . " {:" . bin::$module->name . " " . 
                bin::$module->element . "}..." );
        }
    }

    // Y ejecutamos el módulo
    require $module_file;
}
else {
    
}