return [

// Vendimia settings file for '<?=$project->getName()?>' project.

<?php if ($use_webroot):?>
// Public web root path
'webroot_dir' => '<?=$public_path?>',
<?php endif?>
// Set this to 'true' for entering in debug mode. Exception are shown in
// detail, Vendimia\Email\send sends every email to the administrators, cache is
// disabled.
'debug_mode' => false,

// Administrator's email. On development mode, any email sent with
// Vendimia\\Email\\send will be send to this address(es). On production mode,
// this addresses will be used for sending error alerts.

//'administrators' => ['john@doe.org', 'perico@delospalotes.com'],

// APPID is the unique identifier for this project. Keep it secret.
'APPID' => '<?=$appid?>',

// Project version, in format [major, minor, revision]
'version' => [0, 0, 1],

// Time zone. Refeer to http://www.php.net/manual/timezones.php for a
// complete time zone list.
'time_zone' => 'America/Lima',

// Locale configuration for all except LC_NUMERIC, which remains as C.
'locale' => 'es_PE.utf8',

// Language used in some messages or default function names.
'language' => 'en',

// Database definitions.
'databases' => [
    'default' => ['sqlite',
        'host' => 'database.sqlite',
    ],
],

// Session cookie extra parameters, sent to session_start(). Refer to
// https://www.php.net/manual/en/function.session-start.php for more info.
'session_cookie_parameters' => [
<?php if (version_compare(PHP_VERSION, '7.3.0', '>=')):?>
    'cookie_samesite' => 'Strict',  // Default with PHP >= 7.3
<?php endif?>
],

// Default logger
'logger' => [
    'level' => Vendimia\Logger\LogLevel::ERROR,
    'target' => Vendimia\Logger\Target\ErrorLog::class,
],

// Directory for static content. Can be an absolute path, o relative to this
// project path.
'static_dir' => '<?=$static_path?>',

// URL for static content. Can be absolute, o relative to this project URL.
'static_url' => 'static/',

];
