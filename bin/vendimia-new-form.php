<?php
use Vendimia\Cli;

if (!defined ( "VENDIMIA_BASE_INCLUDED")) {
    require 'vendimia/bin/base.php';    
}

bin::help('Creates a new form definition file.', 
    '[for] app_name form_name [control_name:control_type [...]]', [
        'for' => [
                    'optional' => true,
                    'description' => 'Syntactic sugar word.'
                ],
        'app_name' => "Name of the app where the new form will be created.",
        'form_name' => "New form name.",
        'control_name' => [
            'optional' => true,
            'description' => 'Adds a new control to the form definition.',
        ],
        'control_type' => [
            'optional' => true,
            'description' => "Type of control. Only specify the class name, not the\nFQCN.",
        ],
    ]);

// Debe existir el proyecto
if (!bin::$project_exists) {
    Console::fail ( "Vendimia project not found." );
}

// Si hay más parámetros, entonces lo usamos como nombres de campos
$fields = [];

foreach ( $argv as $d ) {
    $p = explode ( ':', $d );

    $field = $p[0];

    if ( isset ( $p[1]) ) {
        $type = ucfirst(strtolower ($p[1]));
    }
    else {
        $type = 'Text';
    }

    $fields [ $field ] = $type ;
}

// Creamos el formulario
$app = bin::$module->app;
$classname = bin::$module->element;
$form = <<<EOF
<?php
namespace {$app}\\forms;

use Vendimia as V;
use Vendimia\\Form;
use Vendimia\\Form\\Control;
use Vendimia\\Form\\Validator;

class $classname extends Form
{
EOF;

if ($fields) foreach ($fields as $field => $type) {
    $form .= <<<EOF
    var \$$field = [Control\\{$type}::class,
        // Extra options for $field
    
    ];

EOF;
} else {
    $form .= "\n\n";
}
$form .= '}';

$file = Vendimia\Path::join('apps', bin::$module->app, 'forms', bin::$module->element .'.php');
cli\fileSave ($file, $form);