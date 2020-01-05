<?php
namespace Vendimia\Phpss;

use Vendimia;
use Vendimia\Http;
use Vendimia\FilePath;

trait FunctionsTrait {
    /**
     * Aclara un color
     */
    function css_contrast($node, $params)
    {
        extract($this->getParams($params, 'color', 'percent') );

        // Convertimos los datos en valores procesables
        $color = $this->from_color($color );
        $percent = $this->from_percent($percent );

        if(is_null ($color) || is_null($percent) )
            return '';

        $upval = [];
        if($percent > 0){
            // Por cada elemento del color, lo elevamos hasta 255.
        foreach($color as $c){
                $upval[] = $c + (255 - $c) * $percent;
            }
        }
        elseif($percent == 0)
            $upval = $color;
        else {
            foreach($color as $c){
                $upval[] = $c * (1 + $percent);
            }
        }

        // No tocamos el alpha
        if(isset($color[3]) )
            $upval[3] = $color[3];

        return $this->to_color($upval );
    }


    /**
     * Obtiene un fichero desde la ruta pública
     */
    function css_static_url($node, $params)
    {

        // Removemos las comillas
        $params = trim($params, '\'"');

        // Si public_url no tiene un scheme, entonces le añadimos
        // la url de este proyecto

        //$server_name = $_SERVER['SERVER_NAME']?$_SERVER['SERVER_NAME']:$_SERVER['HTTP_HOST'];

        $pu = Vendimia::$settings['static_url'];
        if (substr($pu, 0, 2) != "//" ||
            strpos($pu, '://') !== false) {

            // Añadimos la raiz de la web
            $pu = Vendimia::$base_url . $pu;
        }

        $url = $pu . $params;
        return "url('$url')";
    }

    function at_media($node)
    {
        $node->can_haz_children = true;
    }
}
