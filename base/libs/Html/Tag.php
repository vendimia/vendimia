<?php
namespace Vendimia\Html;

use Vendimia;
use Vendimia\assets;

/**
 * Class to create HTML tags
 */
class Tag implements \ArrayAccess
{
    private $options = [
        /**
          * true to force the close tag draw, even if there is no
          * content.
          */
        'closetag' => false,

        // true if only draws the open tag
        'onlyopentag' => false,

        // Executes htmlentities() on the content
        'escapecontent' => true,

        // Execute htmlentitie4s() on the attributes values
        'escapevariables' => true,

    ];

    private $tagname = null;
    private $variables = [];
    private $content = null;

    // true cuando ya fue construido el tag
    private $built = false;

    // El HTML final
    private $html;

    // El construct es con todas las opcions
    public function __construct ($tagname,
        array $variables = [],
        $content = null,
        array $options = []
    ) {
        $this->tagname = $tagname;
        $this->variables = $variables;
        $this->content = $content;

        $this->options = array_replace($this->options, $options);
    }


    /**
     * Builds the HTML tag
     */
    private function build()
    {
        $tag = '<' . $this->tagname;

        // Añadimos las variables
        if ( $this->variables ) {
            $params = [];

            foreach ( $this->variables as $name => $value ) {

                // Si empieza con '@', es una configuración
                if ( $name[0] == '@' ) {

                    $var = substr ($name, 1);

                    // Solo modificamos la configuración si existe
                    if ( isset ( $this->options [$var] ) ) {
                        $this->options [$var] = $value;
                        continue;
                    }
                }

                // Escapamos?
                if ( $this->options ['escapevariables'] ) {
                    $value = addslashes ( htmlentities ( $value ) ) ;
                }

                $params[] = $name . '="' . $value . '"';
            }

            $tag .= ' ' . join (' ', $params );
        }

        // Hay contenido?
        if (!is_null($this->content)) {
            if ( $this->options ['escapecontent'] ) {
                $this->content = htmlspecialchars( $this->content );
            }

            $tag .=  '>' . $this->content . '</' . $this->tagname . '>';
        }
        else {
            // Solo queremos el opentag?
            if (!$this->options['onlyopentag']) {


                // Si no hay contenido, y si forzamos un closetag, lo ... forzamos
                if ($this->options['closetag']) {
                    $tag .= '></' . $this->tagname;
                } else {
                    $tag .= ' /';
                }
            }
            $tag .= '>';
        }

        $this->html = $tag;
        $this->built = true;

        return $tag;
    }

    /**
     * Builds a Tag object with $data values.
     *
     * Numeric index belongs to $tagname, $variables and $content,
     * respectively. Any associative parameter will be added to
     * $variables
     */
    public static function create (array $data)
    {
        $tagname = null;
        $variables = [];
        $content = null;

        $c = 0;
        foreach (['tagname', 'variables', 'content'] as $e ) {
            if ( isset ( $data[ $c ] ) ){
                $$e = $data[ $c ];
                unset ( $data[ $c ] );
            }
            $c++;
        }

        // Si aun quedan elementos en el array, son más variables
        if ( $data ) {
            $variables = array_merge ( $variables, $data );
        }

        return new static ( $tagname, $variables, $content, [] );
    }

    /**
      * Adds content to this tag
      */
    public function content($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * Shortcut to create tags.
     *
     * The method name is the tag name. Each subsequently words
     * separated by "_" are interpreted as attribute and value. E.g.:
     *
     * <pre>Tag::button("Submit")</pre>
     * creates the HTML Tag
     * <pre>&lt;button&gt;Submit&lt;/button&gt;</pre>
     *
     * <pre>Tag::input_type_date(['class' => 'fancy_input'])</pre>
     * creates the HTML Tag
     * <pre>&lt;input type="date" class="fancy_input" /&gt;
     */
    public static function __callStatic ($name, $args)
    {
        $content = null;
        $variables = [];

        // Separamos $function en pedazos divididos por _
        $parts = explode ( '_', $name );

        // El nombre de la funcion es el primero
        $function = array_shift ( $parts );

        // Mientras exista valores, vamos añadiendo
        while ( $var = array_shift ( $parts ) ) {
            // sacamos el valor
            $val = array_shift ( $parts );

            // Si no hay un valor, lo tomamos como que es una variable
            // que no requiere valor. Le añadimos 'true'
            if ( is_null ( $val ) ) {
                $val = 'true';
            }
            $variables [ $var ] = $val;
        }

        // $Args puede ser opciones o contenido
        foreach ( $args as $arg ) {
            if ( is_array ( $arg ) ) {
                $variables = array_merge ( $variables, $arg );
            } else {
                $content = $arg;
            }
        }

        return new self ($function, $variables, $content);
    }

    /**
     * Fancy options setter.
     *
     * Calling a method sets to 'true' its $option entry. Prepending
     * the 'no' word sets to 'false'. The method name is case
     * insensitive.
     *
     * <pre>Tag::select()->closeTag()</pre>
     */
    public function __call($function, $args)
    {
        $value = isset ( $args[0] ) ? $args[0] : true;
        $original_function = $function;
        $function = strtolower($function);

        // Si el nombre empieza con 'no', entonces el valor
        // es falso
        if ( substr ( $function, 0, 2 ) == 'no' ) {
            $function = substr ( $function, 2 );
            $value = false;
        }

        if ( isset ( $this->options [ $function ] ) ) {
            $this->options [ $function ] = $value;

            return $this;
        }
        else {
            throw new Vendimia\Exception ( "Invalid option '$original_function'");
        }
    }

    /**
     * Crea el HTML del tag, y lo devuelve.
     */
    public function get()
    {
        return $this->build();
    }

    /**
     * Método mágico para crear el HTML del tag
     */
    public function __toString()
    {
        return $this->build();
    }

    // ArrayAccess

    /**
     * Añade una variable
     */
    public function offsetSet ( $offset, $value )
    {
        $this->variables [ $offset ] = $value;
    }
    /**
     * Obtiene una variable. No se para qué...
     */
    public function offsetGet ( $offset )
    {
        if ( isset ( $this->variables [ $offset ] ) ) {
            return $this->variables [ $offset ];
        }
        else {
            return null;
        }

    }

    function offsetExists (  $offset ) {}
    function offsetUnset ( $offset ) {}
}
