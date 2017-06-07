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


        //var_dump($config);
    }
}

exit;

// Empezamos.
try {
    $manager = Database::getManager();
} catch (RuntimeException $e) {
    Console::fail($e->getMessage());
}

foreach ($applist as $app) {
    $base_path = 'apps/' . $app;

    if (!is_dir($base_path)) {
        Console::warning("App '$app' doesn't exists.");
        continue;
    }

    $deffiles = glob($base_path . '/db/' . $defnamesearch);

    if (!$deffiles && $defnamesearch != '*.php') {
        Console::warning("DB definition '$defnamesearch' not found.");
        continue;
    }

    Console::write ("* Application {:app $app}");
    foreach ($deffiles as $deffile) {
        $defname = basename($deffile, '.php');
        Console::write ("  - {:tabledef $defname}:");

        //$tabledef = include $deffile;
        //var_dump($dbdef);
        // GO!

        require $deffile;
        $classname = "$app\\db\\$defname";
        $tabledef = new $classname;

        foreach ($manager->sync($tabledef) as $action) {
            // Tab!
            echo '    ';
            Console::fromStatus(...$action);
        }
    }
}









exit;


if ( ! defined ( "VENDIMIA_BASE_INCLUDED" ) )
    require 'vendimia/bin/base.php';    

if ( in_array( '--help', bin::$args ) ) {
    return "Generate and/or synchronize data base definitions.";
}

if ( ! bin::$project_exists ) {
    Console::fail ( 'Vendimia project directory not found or not specified.' ); 
    exit;
}

// Sacamos el tipo de base de datos, para incluir una 
// librería suya
if  ( ! isset ( Vendimia::$settings['databases']) ||
    !Vendimia::$settings['databases'] ) {
    throw new VENDIMIA_Exception ('No database has been configured for this project.');
}

$db = Vendimia::$settings['databases']['uri'];
$dbtype = parse_url ( $db ) ['scheme'];

require Vendimia\BASE_PATH . "/base/libs/ar/$dbtype/sync_structure.php";

// La aplicación a sincronizar
$application_base = strtolower ( array_shift ( $argv ) );

// La tabla a sincronizar
$table_base = strtolower ( array_shift ( $argv ) );

// Si no hay aplicación, es todo!
if ( $application_base ) {
    $applications = [ $application_base ];
}
else {
    $applications = glob ( 'apps/*', GLOB_ONLYDIR );
}

$db = Vendimia\ar\config::db();

foreach ( $applications as $application ) {

    $application = basename ( $application );

    // Existe?
    if ( !is_dir ( "apps/$application") ) {
        Console::fail ( "Application {:app $application} doesn't exist.");
        exit(1);
    }
    // Colocamos la aplicación en Vendimia
    Vendimia::$APPLICATION = $application;

    // Si no hay $table, usamos todas!
    if ( $table_base ) {
        $tables = [ $table_base ];
    }
    else {
        $tables = glob ( "apps/$application/db/*.php" );
    }


    $draw_title = false;

    foreach ( $tables as $table ) {
        $table = basename ( $table, '.php' ); 

        $file = "apps/$application/db/$table.php";

        // Exitste?
        if ( !file_exists ( $file ) ) {
            Console::fail ( "Table definition {white $table} for {:app $application} doesn\'t exist.");
            exit(1);
        }

        $st = new Vendimia\ar\sync_structure ( 
            $file,
            $application, 
            $table, 
            $db
        );
        $actions = $st->get_sync();

        if ( $actions && !$draw_title  ) {
            Console::write ("\nOn application {:app $application}:");
            $draw_title  = true;
        }

        foreach ( $actions as $action ) {
            if ( isset ( $action['drop'] ) && !isset ( $VENDIMIA_ARGS['drop']) ){
                Console::fail ( "{:app $application}:{:table $table} needs the {:param --drop} argument for drop column {:column {$action['on']}}." );
                continue;
            } 

            Console::write ("* {green $action[action]} {$table}:$action[on]");

            if ( isset ( $VENDIMIA_ARGS['show'] ) ) {
                // Imprimimos
                echo $action['sql'] . "\n";
            }
            else {
                // Pa'dentro!
                //
                $res = $db->execute ( $action['sql'] );
                if ( $res === false ) {
                    throw new Vendimia\Exception ( $db->last_error(), [ 'Query SQL' => $action['sql'] ]);
                }
            }


        }
    }
}