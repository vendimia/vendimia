<?php
use Vendimia\Cli;

if (!defined ("VENDIMIA_BASE_INCLUDED")) {
    require 'vendimia/bin/base.php';    
}
bin::help("Creates a ORM entity definition.", 
    "[for] app_name model_name", 
    [
        'for' => [
            'optional' => true,
            'description' => 'Syntactic sugar word.'
        ],    
        'app_name' => 'Name of the app where the new ORM entity will be created',
        'model_name' => 'New entity name.'
    ]
);


// Debe existir el proyecto
if (!bin::$project_exists) {
    Console::fail ( "Vendimia project not found." );
}

$namespace = bin::$module->app;
$classname = bin::$module->element;

Cli\createORM(bin::$project_path, $namespace, $classname);