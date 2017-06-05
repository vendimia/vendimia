<?php
use Vendimia\Cli;
use Vendimia\Console;

bin::help('Creates a new app for this project.', 
    'app_name [--without-view]',
    [
        'app_name' => "Name of the new app. Avoid use PHP keywords, or 'Vendimia'.",
        '--without-view' => [
            'optional' => true,
            'description' => "Don't create a default view.",
        ],
    ]
);
// Si ya existe la app, fallamos
if (isset(bin::$module->app_exists) && bin::$module->app_exists) {
    Console::fail ("Application '{:app " . bin::$module->app . "}' already exist.");
}
// Necesitamos un nombre de módulo
if (!bin::$module->app) {
    console::fail ('New app name is missing. Try --help.');
}

// Hay algunos nombres prohibidos
if (in_array(bin::$module->app, ['static', 'Vendimia', 'for'])) {
    Console::fail("'" . bin::$module->app . "' is a invalid name for an app.");
}

// Hay un controlador que hay que crear, en vez de default?
$controller = array_shift ($argv);
if ( !$controller ) {
    $controller = 'default';
}

Console::write ( "Creating new app {:app " . bin::$module->app . "}...");
Cli\makeTree(bin::$project_path, [
    "apps/" . bin::$module->app => [
        'controllers', 
        'models', 
        'forms',
        'orm',
        'assets' => [
            'css',
            'js',
            'imgs'
        ],
        'views' => [
            'layouts'
        ],
    ]
]);


// oh, php...
$APPLICATION = bin::$module->app;

Cli\createController (bin::$project_path, bin::$module->app, $controller);

// Creamos una vista, a menos que no querramos
if (!isset(bin::$args['without-view'])) {

    $content = <<<EOF
<!-- Delete this and the lines below, and write your own view. -->

<h1>Hi! I'm the <em>$controller</em> controller from <em>$APPLICATION</em> application!</h1>
<p>Now, edit the view file <strong>apps/$APPLICATION/views/default.php</strong> for 
changing this text, or the file <strong>apps/$APPLICATION/controllers/default.php</strong>
for changing the controller behavior.</p>

EOF;
    Cli\createView (bin::$project_path, $APPLICATION, $controller, $content);
}
