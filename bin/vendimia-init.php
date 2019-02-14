<?php
use Vendimia\Cli;
use Vendimia\Console;

if (!defined('VENDIMIA_BASE_INCLUDED')) {
    require 'vendimia/bin/base.php';
}
bin::help("Creates the skeleton directory for a new Vendimia project.", 
    "[directory] <options>",
[
    'directory' => [
        'optional' => true,
        'description' => 'New project path. Default is the current\ndirectory.'
    ],
    '--webroot=<dir>' => [
        'optional' => true,
        'description' => 'Path to the public web root directory.\nDefault is the project directory.'
    ],
    '--bare' => [
        'optional' => true,
        'description' => 'Creates the bare minimun directory\nstructure. Hey Fabian! :)'
    ],
]);

// Evitamos sobreeescribir
if (bin::$project_exists) {
    $pn = bin::$project_name;
    $pp = bin::$project_path;

    Console::fail ( "Project {:project $pn} already exists in {:path $pp}.");
}

$target_dir = array_shift($argv);

if(!$target_dir) {
    Console::fail('You must specify the new project directory.');
}


// Verificamos la raiz de la web
$using_webroot = false;
if (isset(bin::$args['webroot'])) {
    $webroot_dir = bin::$args['webroot'];

    if ($webroot_dir != $target_dir) {
        $using_webroot = true;
    }
} 

if (!$using_webroot) {
    $webroot_dir = $target_dir;
}

// Esto es extraño... realpath de PHP no funciona en ficheros/directorios
// que no existen. Como igual tenemos que crearlos, sacamos el path real
// despues de los mkdirs

if(!file_exists($target_dir)) {
    mkdir ($target_dir);
}
if(!file_exists($webroot_dir)) {
    mkdir ($webroot_dir);
}

// Ahora si, sacamos el path absoluto
$target_dir = realpath($target_dir);
$webroot_dir = realpath($webroot_dir);

$project_name = basename($target_dir);
$project_path = dirname($target_dir);

// Si llamamos con el parametro --bare, entonces no creamos el arbol vacío.
// En honor a github.com/fabian818 ;-)
$bare = false;
$bare_word = '';
if (isset(bin::$args['bare'])) {
    $bare = true;
    $bare_word = 'bare ';
}

Console::write ( "Initializing {$bare_word}Vendimia project {:project $project_name} in {:path $target_dir}" );

if ($using_webroot) {
    Console::write ("Notice: Using {:path $webroot_dir} as web root directory.");
}

echo PHP_EOL;

if(!$bare) {
    // Estructura del proyecto
    Cli\makeTree( $target_dir, [
        'apps',
        'config',
        'tmp',
        'base' => [
            'controllers',
            'models',
            'orm',
            'libs',
            'views' => [
                'layouts',
            ],
            'forms',
            'assets' => [
                'css', 'js', 'imgs'
            ]
        ],        
    ]);

    // Por defecto, la carpeta de elementos estáticos está dentro de la raiz
    // de la web
    Cli\makeTree($webroot_dir, [
        'static' => [
            'assets' => [
                'css',
                'js',
                'imgs',
            ]
        ]        
    ]);
}

// Generamos un APPID
$APPID = hash('sha256', rand() + rand());

// Si hay un webroot, guardamos la ruta en la configuración
$webroot_config = "\n";
if ( $using_webroot ) {
    $webroot_config = "\n// Path for public web root\n'webroot_dir' => '$webroot_dir',\n";
}
$settings = <<<EOF
<?php return [

// Vendimia settings file for '$project_name' project.
$webroot_config
// Set this to 'true' for entering in debug mode. Exception are shown in 
// detail, Vendimia\Email\send sends every email to the administrators, cache is
// disable
'debug_mode' => false,

// Administrator's email. On development mode, any email sent with
// Vendimia\\Email\\send will be send to this address(es). On production mode,
// this addresses will be used for sending error alerts.

//'administrators' => ['john@doe.org', 'perico@delospalotes.com'],

// APPID is the unique identifier for this project. Keep it secret.
'APPID' => '$APPID',

// Project version, used as [major, minor, revision]
'version' => [0, 0, 1],

// Time zone. Refeer to http://www.php.net/manual/timezones.php for a
// complete time zone list.
'time_zone' => 'America/Lima',

// Locale configuration for all except LC_NUMERIC, which remains as C.
'locale' => 'es_PE.utf8',

// Language used in some messages or default function names.
'language' => 'en',

// Database definitions. 
/*
'databases' => [
    'default' => ['mysql',
        'username' => 'oliver',
        'password' => 'pikachu123',
        'host' => 'localhost',
        'database' => 'vendimia_database',
    ],
],
/**/

// Default logger
'logger' => [
    'level' => Vendimia\Logger\LogLevel::ERROR,
    'target' => Vendimia\Logger\Target\ErrorLog::class,
],

// Directory for static content. Can be an absolute path, o relative to this
// project path.
'static_dir' => 'static/',

// URL for static content. Can be absolute, o relative to this project URL.
'static_url' => 'static/',

];
EOF;
Cli\fileSave('config/settings.php', $settings, $target_dir);

// Creamos una configuración para el ambiente 'development'
if (!$bare) {
    $config_dev = <<<EOF
<?php

// Configuration file for the default 'development' working environment.
// All the values here will replace the ones from config/settings.php.
//
// You can set the working environment in the 'index.php' from your project.

return [

'debug_mode' => true,

'logger' => [
    'level' => Vendimia\Logger\LogLevel::DEBUG,
],

];
EOF;

    Cli\fileSave('config/settings.development.php', $config_dev, $target_dir);
}
// Rutas

if (!$bare) {
    $routes = <<<EOF
<?php 
use Vendimia\\Route\\Rule; return [
    // Routing rules definition file.
    
];
EOF;
    Cli\fileSave('config/routes.php', $routes, $target_dir);
}

// index.php

$define_project_path = '';
if ($using_webroot) {
    $define_project_path = "define ('Vendimia\PROJECT_PATH', '$target_dir');" 
        . PHP_EOL . PHP_EOL;
}

$index = <<<EOF
<?php
$define_project_path
/*
Each 'environment' loads an extra settings.{ENVIRONMENT}.php file (where 
{ENVIRONMENT} is the value of  VENDIMIA\\ENVIRONMENT constant), overwriting 
the main settings. By default, the file 'config/settings.development.php' 
only enables the debug mode.

Change this conditions as you see fit for changing the working environment.
*/

if (isset(\$_SERVER['SERVER_ADDR'])) {
    \$host = strtolower(\$_SERVER['SERVER_ADDR']);
    if (\$host == 'localhost' || \$host == '127.0.0.1') {
        define('VENDIMIA\\ENVIRONMENT', 'development');
    }
}

\$base_path = 'vendimia/';
if (getenv('VENDIMIA_BASE_PATH')) {
    \$base_path = getenv('VENDIMIA_BASE_PATH') . '/';
}

// Let's the magic begin :-)
return require \$base_path . 'start.php';
EOF;

Cli\fileSave('index.php', $index, $webroot_dir);

// .htaccess para Apache

$public_access = '';
if (!$using_webroot) {
    $public_access = "RewriteCond %{REQUEST_FILENAME} !static/ [NC]";
}
$htaccess = <<<EOF
RewriteEngine On
Options -Indexes
$public_access
RewriteRule ^(.*)$           index.php?q=$1 [L,QSA]
EOF;
cli\fileSave('.htaccess', $htaccess, $webroot_dir);

// Layout por defecto
$default_layout = <<<EOF
<?php use Vendimia\\View;
/*
--------------------------------------------------------------------------------
Basic default view layout. When you modify this layout, or create a new one, 
remember to add at least this "\$this->content()" instruction on it.

The files 'vendimia_default_header' and 'vendimia_default_footer' are located in
the 'base/views' directory inside the Vendimia installation path.
--------------------------------------------------------------------------------
*/

// The 'vendimia_default_header' view file contains all the standard HTML 
// headers, up to the <body> tag. 
\$this->insert('vendimia_default_header');

// The 'vendimia_message' view file contains the message <div> tag for the 
// Vendimia\\Message functions, like message() o warning().
\$this->insert('vendimia_messages');

// This draws the actual view. If you create your own layout, 
// make sure you put this method with no parameters.
\$this->content();

// The 'vendimia_default_footer' contains a small footer, the </body> and 
// </html> closing tags.
\$this->insert('vendimia_default_footer');
EOF;
Cli\fileSave('base/views/layouts/default.php', $default_layout, $target_dir);

$git = <<<EOF
# Files we don't want in our production server, if you use GIT VCS

tmp/
cache/
static/assets/
EOF;
Cli\fileSave ('.gitignore', $git, $target_dir);
