<?php
namespace Vendimia;

/**
 * Helper functions for path manipulation
 */
class Path
{
    /**
     * Join several string or array paths.
     * @return string Joined path
     */
    public static function join(...$paths)
    {
        $sep = DIRECTORY_SEPARATOR;
        $first_part = true;
        $absolute = '';

        // Si no hay argumentos, retornamos vacío.
        if (!$paths) {
            return '';
        }

        $return_parts = [];
        foreach ($paths as $path) {
            // Si $path es un array, nos llamamos recursivamente
            if (is_array($path)) {
                $path = call_user_func_array([__CLASS__, __METHOD__], $path);
            }

            // Si el primer elemento empieza con $sep, entonces toda la ruta
            // es absoluta, debe empezar con $sep
            if ($first_part && $path && $path[0] == $sep) {
                $absolute = $sep;
            }
            $first_part = false;

            $return_parts = array_merge($return_parts, array_filter(explode($sep, $path)));
        }


        return $absolute . join($sep, $return_parts);
    }

    /**
     * Create a directory.
     *
     * @param string $dirpath full path to create
     * @return string Status of the creation
     *
     */
    public static function makeDir ($dirpath)
    {
        if (file_exists($dirpath)) {
            return 'omit';
        } else {
            $res = mkdir ($dirpath, 0775, true);
            if ($res) {
                return 'ok';
            } else {
                return 'error';
            }
        }
    }

    /**
     * Makes a directory tree structure inside $base_path, yielding every directory
     *
     * @param string $base_path Base path for all the new directories.
     * @param array $tree New directory listing to create. If the value is an array,
     *      then it's created recursively.
     * @yield array Status of every directory it's being created, and its path.
     */
    public static function makeTree ($base_path, array $tree)
    {
        foreach ($tree as $base => $path) {
            if (is_array($path)) {
                $new_base_path = self::join($base_path, $base);
                foreach (self::makeTree($new_base_path, $path) as $path) {
                    yield $path;
                };
            } else {
                $path = self::join($base_path, $path);
                $status = self::makeDir($path);
                yield [$status, $path];
           }
        }
    }
}
