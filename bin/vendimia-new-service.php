<?php
use Vendimia\Cli;

if (!defined ( "VENDIMIA_BASE_INCLUDED")) {
    require 'vendimia/bin/base.php';    
}

bin::help('Creates a new service trait.', 
    '[for] app_name service_name', [
        'for' => [
                    'optional' => true,
                    'description' => 'Syntactic sugar word.'
                ],
        'app_name' => "Name of the app where the new form will be created.",
        'service_name' => "New service trait name.",
    ]);

// Debe existir el proyecto
if (!bin::$project_exists) {
    Console::fail ( "Vendimia project not found." );
}

// Creamos el formulario
$app = bin::$module->app;
$traitname = bin::$module->element;
$service = <<<EOF
<?php
namespace {$app}\\services;

use Vendimia as V;

trait $traitname
{
    // Write your service methods
}
EOF;

$file = Vendimia\Path::join('apps', bin::$module->app, 'services', bin::$module->element .'.php');
cli\fileSave ($file, $service);