<?php
namespace assets;

use Vendimia;
use Vendimia\Http;

$asset = services\Asset::getNamesFromArgs();

// Cambiamos la aplicación. No creo que pase nada
Vendimia::$application = $asset[0];

$files = $asset[1];

// Simplemente unimos todos los js en un solo fichero
$js = '';
foreach ($files as $file) {
    $fp = new Vendimia\Path\FileSearch($file, 'assets/js', 'js');

    // Si no existe, 404!
    if ($fp->notFound()) {
        Http\Response::notFound ( "Javascript asset file '$file' not found", [
            'Search paths' => $fp->searched_paths,
        ]);
    }
   
    $file = $fp->get();
    $js .= file_get_contents($file) . ";\n";
}

(new Http\Response($js, 'application/javascript'))
    ->send();