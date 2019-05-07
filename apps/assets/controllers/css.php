<?php
namespace assets;

use Vendimia;
use Vendimia\Phpss;
use Vendimia\Http;

$asset = services\Asset::getNamesFromArgs();

// Cambiamos la aplicación. No creo que pase nada
Vendimia::$application = $asset[0];

$files = $asset[1];

// El CSS final
$css = '';
$modified = false;

$cache = new services\Compiler('css', $asset[2]);

$cache->lock();

$phpss = new Phpss\Phpss( $files );

$css  = $phpss->getCss();

$cache->save($css);

(new Http\Response($css, 'text/css'))
    -> send();
