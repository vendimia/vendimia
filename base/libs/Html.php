<?php
namespace Vendimia;

use assets\services\Compiler;
use assets\services\Asset;

/**
 * Handles HTML inside a view.
 */
class Html {
    /** META tags. There is only 3 kinds. */
    private $meta = [
        'name' => [],
        'http-equiv' => [],
        'charset' => '',
    ];

    /** LINK tags */
    private $link = [];

    /** Javascripts, all loaded in one SCRIPT tag. */
    private $script = [];

    /** External Javascript sources */
    private $external_scripts = [];

    /** CSS assets */
    private $css = [];

    /** External CSS sources */
    private $external_css = [];

    /**
     * Add one or more CSS style
     */
    public function addCss(...$assets)
    {
        $this->css = array_merge ($this->css, $assets);
    }

    /**
     * Add one or more CSS style
     */
    public function addExternalCss(...$assets)
    {
        $this->external_css = array_merge($this->external_css, $assets);
    }

    /**
     * Inserts a CSS at the top.
     */
    public function prependCss(...$assets)
    {
        $this->css = array_merge($assets, $this->css);
    }

    /**
     * Add one or more javascripts.
     */
    public function addScript(...$assets)
    {
        $this->script = array_merge($this->script, $assets);
    }

    /**
     * Inserts one o more javascript at the top.
     */
    public function prependScript(...$assets)
    {
        $this->script = array_merge($assets, $this->script);
    }

    /**
     * Add one or more external javascript
     */
    public function addExternalScript($script_url, $extra_params = [])
    {
        /*$this->external_scripts = array_merge(
            $this->external_scripts, $scripts);
        */
        array_unshift($extra_params, $script_url);
        $this->external_scripts[] = $extra_params;
    }

    /**
     * Add a LINK tag
     *
     * @param string|array $rel 'rel' attribute. If it's an array,
     *      it'll be used as attribute origin.
     * @param string $href 'href' attribute.
     * @param array $extra_attr extra html attributes to the link tag
     */
    public function addLink($rel, $href = null, $extra_attr = [])
    {
        if (is_array($rel)) {
            $attributes = $rel;
        } else {
            $attributes = array_merge([
                'rel' => $rel,
                'href' => $href,
            ], $extra_attr);
        }
        $this->link[] = $attributes;
    }

    /**
     * Add a META tag.
     *
     * @param string|array $type META tag type. If it's an array, then the type
     *      is 'name', and this will be used as $attr
     * @param string!array $attr Associative array with name => value, or string
     *      if 'name' is 'charset'
     */
    public function addMeta($type, $attr = null)
    {
        if (is_array($type)) {
            $attr = $type;
            $type = 'name';
        }

        // Tratamos distinto a 'charset'
        if ($type == 'charset') {
            if (!is_string($attr)) {
                throw new \RuntimeException("\$attr must be a String when \$type is 'charset'.");
            }
            $this->meta[$type] = $attr;
        } else {
            if (!is_array($attr)) {
                throw new \RuntimeException("\$attr must be a Array with name => content.");
            }

            foreach ($attr as $var => $val) {
                $this->meta[$type][$var] = $val;
            }
        }
    }

    /**
     * Add some assets, if exists, using the view name.
     */
    public function addDefaultAssets($viewname)
    {
        $css = new Path\FileSearch($viewname, 'assets/css');
        $css->search_base = false;
        $css->search_Vendimia = false;
        $css->ext = 'css';

        $js = new Path\FileSearch($viewname, 'assets/js');
        $js->search_base = false;
        $js->search_Vendimia = false;
        $js->ext = 'js';


        if ($css->found()) {
            $this->addCss($viewname);
        }
        if ($js->found()) {
            $this->addScript($viewname);
        }
    }


    /**
     * Generates a header LINK tags.
     */
    public function drawLink()
    {
        $html = [];

        // Añadimos primero el tag link para los CSS
        $this->link[] = [
            'rel' => 'stylesheet',
            'href' => (new Compiler('css', Asset::buildUri($this->css)))->url(),
            'type' => 'text/css',
        ];

        // Y los links para los CSS externos
        foreach ($this->external_css as $external) {
            $this->link[] = [
                'rel' => 'stylesheet',
                'href' => $external,
                'type' => 'text/css',
            ];
        }

        foreach ($this->link as $link) {
            $html[] = Html\Tag::link($link)->get();
        }

        return join(PHP_EOL, $html) . PHP_EOL;
    }

    /**
     * Generates the SCRIPT tags for all the scripts
     */
    public function drawScripts()
    {
        // Primero, los scripts propios del proyecto.
        $sources = [
            (new Compiler('js', Asset::buildUri($this->script)))->url()
        ];

        // Luego, añadimos los externos
        //$sources = array_merge($sources, $this->external_scripts);

        // Ahora, creamos un SCRIPT tag por cada uno
        $html = '';
        foreach ($sources as $source) {
            $html .= Html\Tag::script([
                    'type' => 'application/javascript',
                    'src' => $source,
            ])->closeTag() . PHP_EOL;
        }

        // Incluimos los externos por separado, por que pueden tener
        // parámetros
        foreach ($this->external_scripts as $script) {
            $source = array_shift($script);

            $script['type'] = 'application/javascript';
            $script['src'] = $source;

            $html .= Html\Tag::script($script)->closeTag() . PHP_EOL;

            /*$html .= Html\Tag::script([
                    'type' => 'application/javascript',
                    'src' => $source,
            ])->closeTag() . PHP_EOL;*/
        }

        return $html;
    }

    /**
     * Generates the META tags.
     *
     * @return string HTML with the META tags.
     */
    public function drawMeta()
    {
        $html = [];
        foreach ($this->meta as $type => $meta) {
            // EL tipo 'charset' lo dibujamos distinto
            if ($type == 'charset') {
                $html[] = Html\Tag::meta([
                    $type => $meta,
                ]);
            } else {
                // Para los demás, usamos la propiedad content
                foreach ($meta as $var => $val) {
                    $html[] = Html\Tag::meta([
                        $type => $var,
                        'content' => $val,
                    ]);
                }
            }
        }

        return join(PHP_EOL, $html) . PHP_EOL;
    }

    /**
     * Creates an <A tag. $target will be parsed with Vendimia\Url
     *
     * @param string|array $target Target URL for the link. It will be parsed using Vendimia\Url.
     * @param string $text Inner HTML for the A tag. If omitted, the original $target will be used.
     * @param array $extras Extra parameters for the A tag.
     */
    public function link($target, $text = false, array $extras = [])
    {
        $parsed_target = Url::parse($target);

        $params = array_merge([
            'href' => $parsed_target,
        ], $extras);

        if ($text === false) {
            $text = $target;
        }

        return (string)Html\Tag::a($params, $text);
    }
}
