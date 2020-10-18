<?php
namespace Vendimia;

use Vendimia;

/**
 * Session variables handler
 */
class Session extends MutableCollection {
    public function __construct($base_url)
    {
        // Cuando llamamos desde el CLI o del servidor de development, esta
        // variable no existe
        if (!key_exists('HTTP_HOST', $_SERVER)) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }

        // No iniciamos la sesión si venimos por la CLI
        if (Vendimia::$execution_type != 'cli') {
            session_start(array_merge([
                'cookie_path' => $base_url,
            ], Vendimia::$settings['session_cookie_parameters'] ?? []));
        }

        parent::__construct();
        $this->setArrayByRef($_SESSION);
    }
}
