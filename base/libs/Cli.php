<?php
/**
 * 
 */
namespace Vendimia\Cli;

use Vendimia\Path;
use Vendimia\Console;

/**
 * Wrapper to Vendimia\Path\MakeDir for sending to console the mkdir status.
 */
function makeTree ($base_path, array $tree)
{
    foreach (Path\MakeTree($base_path, $tree) as $status ) {
        $path = $status[1];
        Console::fromStatus('MKDIR', $status[0], $status[1]);
    }

}


/**
 * Saves data to a file
 */
function fileSave ($file, $data, $options = null)
{

    if (is_string($options)) {
        $options = [
            'base_path' => $options,
        ];
    }

    // Podemos sobreescribir?
    $overwrite = isset ($options['overwrite']);
    
    if (isset($options['base_path'])) {
        $file = Path\join($options['base_path'], $file);
    }

    if (!file_exists($file)) {
        
        // Creamos el directorio silenciosamente, si no existe
        $filepath = dirname($file);
        Path\makeDir($filepath);

        // Y grabamos el fichero
        file_put_contents ($file, $data);
        Console::fromStatus('FILE', 'ok', $file);
    }
    else {
        Console::fromStatus('FILE', 'omit', $file);
    }
}

/**
 * Crea una vista
 */
function createView ($base_path, $app, $view, $content = false) {
    // Vista por defecto

    if ( $app != ":" ) {
        // El Namespace tiene el slash invertido
        $ns = strtr ( $app, '/', '\\' );
        $data = "<?php namespace $ns; use Vendimia as V, "; 
    } else {
        $data = "<?php use ";
    }

    $data .= "Vendimia\\Html;?>\n\n";

    if ( $content ) {
        $data .= $content;
    } else {
        $data .= "<!-- Delete this line and write your own view. -->\n";
    }

    if ( $app == ":") {
        fileSave ("base/views/$view.php", $data, $base_path);
    } else {
        fileSave ("apps/$app/views/$view.php", $data, $base_path);
    }
    
}

/**
 * Crea un controlador
 */
function createController ($base_path, $app, $controller, $content = false) {
    // Controlador por defecto

    if ( $app == ":") {
        // No hay controladores base
        fail ( "Can't create a base controller" );
    }

    // El Namespace tiene el slash invertido
    $namespace = strtr ( $app, '/', '\\' );
    $data = <<<EOF
<?php
namespace $namespace;

use Vendimia as V;


EOF;
    if ( $content ) {
        $data .= $content;
    }
    else {
        $data .= "// Your code goes here.\n";
    }

    fileSave ("apps/$app/controllers/$controller.php", $data, $base_path);
}

/**
 * Creates a ORM entity
 */
function createORM ($base_path, $namespace, $classname)
{
    $file = <<<EOF
<?php
namespace $namespace\\orm;

use Vendimia as V;
use Vendimia\\ORM\\Entity;
use Vendimia\\ORM\\Field;

class $classname extends Entity
{
}
EOF;

    fileSave($classname . '.php', $file, [
        'base_path' => [$base_path, 'apps', $namespace,  'orm'],
    ]);
}