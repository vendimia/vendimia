<?php
namespace assets\services;

use Vendimia;
use Vendimia\Http\Response;

/**
 * Static class for various assets utilities.
 */
class Asset
{
    /**
     * Build a single file name for all the assets, plus the controller name.
     */
    static public function buildUri(array $assets)
    {
        $uri = '';

        if (Vendimia::$application) {
            $uri = Vendimia::$application . '::';
        }

        $uri .= join(',', $assets);

        return $uri;
    }

    /**
     * Return all the files associated to an asset, from Vendimia::$Args.
     *
     * @return array [application, asset_names]
     */
    public static function getNamesFromArgs()
    {
        $arg = Vendimia::$request->get['source'];

        if (!$arg) {
            Response::serverError("You must specify at least one CSS asset filename.");
        }

        $application = Vendimia::$application;
        $names = explode (',', $arg);

        $colon = strpos($names[0], '::');
        if ($colon !== false) {
            $application = substr($names[0], 0, $colon);
            $names[0] = substr($names[0], $colon + 2);
        }

        return [$application, $names, $arg];
    }
}
