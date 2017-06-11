<?php
use Vendimia\Cli;
use Vendimia\Console;
use Vendimia\Database\Database;
use Vendimia\ORM\Configure\Configure;

if (!defined('VENDIMIA_BASE_INCLUDED')) {
    require 'vendimia/bin/base.php';
}
bin::help("Synchronize the database definition.", 
    "[app] [--drop]",
    [
    'app' => [
        'optional' => true,
        'description' => 'Only process the tabledefs from this app'
    ],
    '--drop' => [
        'optional' => true,
        'description' => 'Allows destructive commands.'
    ],
]);

// Debe existir el proyecto
if (!bin::$project_exists ) {
    Console::fail ("Vendimia project not found.");
}

// Necesitamos una base de datos configurada

// Aplicación a sincronizar
if ($argv) {
    $applist = [array_shift($argv)];
} else {
    $applist = array_map(function($value){
        return basename($value);
    }, glob('apps/*', GLOB_ONLYDIR));
}

// Definición a sincronizar.
if ($argv) {
    $defnamesearch = array_shift($argv) . '.php';
} else {
    $defnamesearch = '*.php';
}

foreach ($applist as $app) {
    $base_path = 'apps/' . $app;

    if (!is_dir($base_path)) {
        Console::warning("App '$app' doesn't exists.");
        continue;
    }

    $orm_files = glob($base_path . '/orm/' . $defnamesearch);

    if (!$orm_files && $defnamesearch != '*.php') {
        Console::warning("ORM entity file '$defnamesearch' not found.");
        continue;
    }

    Console::write ("* Application {:app $app}");

    $manager = Database::getManager();
    foreach ($orm_files as $orm_file) {
        $orm_name = basename($orm_file, '.php');
        Console::write ("  - {:tabledef $orm_name}:");

        require $orm_file;
        $entity_name = "$app\\orm\\$orm_name";

        try {
            foreach ($manager->sync($entity_name) as $action) {
                // Tab!
                echo '    ';
                Console::fromStatus(...$action);
            }
        } catch (\Vendimia\Database\QueryException $e) {
            Console::fail('SQL command fail: ' . $e->getMessage());
        }
    }
}