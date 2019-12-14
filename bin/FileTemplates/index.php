<?php if ($use_webroot): ?>
const Vendimia\PROJECT_PATH = '<?=$project_path?>';

<?php endif ?>
/*
The 'VENDIMIA\ENVIRONMENT' constant value is used to load an extra
'config/settings.{ENVIRONMENT}.php' file, if found, for updating the main
settings.

By default, there is a 'config/settings.development.php' file, which only
enables the debug mode. The following condition sets the VENDIMIA\ENVIRONMENT
constant to 'development' (thus loading the aforementioned config file) when
this project is accessed via localhost.

You can have as many environments as you want, just set the right value
of the constant here:
*/

if (isset($_SERVER['SERVER_ADDR'])) {
    $host = strtolower($_SERVER['SERVER_ADDR']);
    if ($host == 'localhost' || $host == '127.0.0.1') {
        define('VENDIMIA\ENVIRONMENT', 'development');
    }
}

/* End environment setting. Please don't modify the rest of lines. */

$base_path = 'vendimia/';
if (getenv('VENDIMIA_BASE_PATH')) {
    $base_path = getenv('VENDIMIA_BASE_PATH') . '/';
}

// Let's the magic begins :-)
return require $base_path . 'start.php';
