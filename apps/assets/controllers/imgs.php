<?php
namespace assets;

use Vendimia;
use Vendimia\Path;
use Vendimia\Http;

// Si no hay argumentos, 500!
if ( !isset ( Vendimia::$args[0] ) || trim( Vendimia::$args[0] ) == "" ) {
    throw new VENDIMIA_HTTP_Exception ( 500, "You must specify at leaste one image asset filename.");
}

// Buscamos la imágen
$path = Path::join (Vendimia::$args->get());

// Hay una app por defecto?
$colon = strpos ($path, ':' );
if ( $colon !== false ) {
    // Cambiamos el nombre de la app. No creo que haya problemas...
    Vendimia::$APPLICATION = substr ( $path, 0, $colon );
    $path = ( substr ( $path, $colon + 1) );

}

$fp = new Vendimia\Path\FileSearch($path, 'assets/imgs' );
$fp->ext = false;

// Si no existe, 404!
if ( $fp->notFound() ) {
    #Vendimia\http_code(404, 'Not found');
    Vendimia\Http\Response::notFound ( "Image asset file '$path' not found.", [
        'Search Path' => $fp->searched_paths,
    ]);
}

// TODO: Crear una verificación usando el etag y last modified
$response = Http\Response::fromFile($fp->get());

$response->send();

# Finalizamos la ejecución
exit;