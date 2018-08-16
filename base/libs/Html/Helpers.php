<?php
namespace Vendimia\Html;

/**
 * Various HTML helpers
 */
class Helpers
{
    /**
     * Returns a set of <OPTION> tags
     */
    public static function options(array $options, $enable = [])
    {
        $html = '';

        // Si $enable no es array, lo convertimos a uno, para usar
        // el mismo código para las listas múltiples.
        if (!is_array ($enable)) {
            $enable = [ $enable ];
        }

        foreach ($options as $id => $value) {
            if (is_array ($value)) {

                // Creamos un grupo
                $html .= Tag::optgroup (
                    self::options ($value, $enable),
                    ['label' => $id])->noEscapeContent() // Sin entidades
                    . PHP_EOL;
            }  else {
                $vars = [
                    'value' => $id,
                ];

                // Si viene $enable, activamos. Debe ser un array, puesto
                // que algunos options son multiselect
                if ($enable) {
                    if (in_array ($id, $enable)) {
                        $vars ['selected'] = 'true';
                    }
                }
                $html .= Tag::option ($value, $vars)->noEscapeContent() . PHP_EOL;
            }
         }
         return $html;
    }
}
