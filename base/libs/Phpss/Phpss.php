<?php
namespace Vendimia\Phpss;

use Vendimia;
use Vendimia\Http;

/**
 * PHPSS, a PHP CSS optimizer for Vendimia. OMG, right? #sheldoncooper.
 *
 * @author Oliver Etchebarne
 */
class phpss {
    use FunctionsTrait;


    /**
     * Listado de ficheros incluidos en esta instancia de phpss
     */
	private $files_includes = [];

    /**
     * El nodo raiz
     */
    private $root = null;

    /**
     * Todos los nodos de los elementos CSS
     */
    private $elements = [];

    /**
     * Variables globales
     */
    private $variables = [];

    /**
     * Obtiene parámetros con nombres, o por orden.
     */
    public function getParams()
    {
        $result = [];

        $variables = func_get_args();

        // Sacamos el 1er elemento, que es un string con las variables
        $str_params = trim(array_shift($variables));
        $result = [];

        // Procesamos
        $id = 0;
        foreach (explode(',', $str_params) as $p) {
            $p = trim($p);
            // Buscamos un ":", que es el separador
            $parts = explode(':', $p, 2);

            if (isset($parts[1])) {
                // Es un valor con nombre:
                $index = $parts[0];
                $value = $parts[1];
            } else {
                $index = ifset ( $variables[$id], $id );
                $value = $parts[0];
            }

            // Solo guardamos los índices que existan en $variables
            if (in_array($index, $variables))
                $result [ $index ] = $value;

            $id++;
        }
        return  $result;
    }


    /**
     * Obtiene un número decimal desde un porcentaje
     */
    public function from_percent($percent)
    {

        $int = intval($percent);

        if ( $int < -100 ) {
            $int = -100;
        }
        if ( $int > 100 ) {
            $int = 100;
        }

        return $int / 100;
    }


    public function at_import ($node)
    {

        // Importamos un fichero
        $file = $node->value;

        // Removemos la función "url()", si existe, y comillas de los lados
        $file = trim ( preg_replace ( '/url\((.+)\)/', '$1', $file ), '"\'' );

        // Ubicamos el fichero
        $path = new Vendimia\Path\FileSearch($file, "assets/css", 'css');

        // Si no existe, explotamos.
        if ($path->notFound()) {
            Http\Response::notFound("CSS asset file '$file' not found", [
                'Search paths' => $path->searched_paths,
            ]);
        }

        // Guardamos este fichero
        $this->files_includes[] = $path->get();

        // Creamos una nueva raíz para este include
        $newroot = new Node(true);

        // Parseamos el CSS
        $css = new Parser($newroot, $path->get());

        // Luego interpretamos las variables
        $this->parse ( $newroot );

        // Solo en caso que este fichero importado esté vacío...
        if ($newroot->first) {
            // Reemplazamos este nodo con el 1er hijo de este root, y sus hermanos
            $node->replace($newroot->first);
        }
    }

    public function resolve_variables($string)
    {

        $regexp = '/(\$([a-zA-Z0-9\-\_]+)|\[\$([a-zA-Z0-9\-\_]+)\])/';

        $matches = [];
        $c = preg_match_all ( $regexp, $string, $matches );

        $replace = [];
        if ( $c ) {
            foreach ( $matches[0] as $m ) {
                // Borramos corchetes a los lados, si tuviera
                // para la búsqueda
                $clean_var = trim ( $m , '[]');
                if ( isset ( $this->variables [ $clean_var ]) ) {
                    $replace [ $m ] = $this->variables [ $clean_var ];
                }
                else
                    // Las variables inexistentes las ignoramos.
                    $replace [ $m ] = '';
            }
            $string = strtr ( $string, $replace );
        }

        return $string;
    }
    /**
     * Busca variables y funciones.
     */
    function resolve_functions($string, $node)
    {

        $regexp = '/([a-zA-Z][a-zA-Z0-9\_]*?)\((.*?)\)/';
        $c = preg_match_all ( $regexp, $string, $matches, PREG_SET_ORDER );

        if ($c) {
            foreach ($matches as $m) {

                $origin = $m[0];
                $function_name = 'css_' . $m[1];
                $parameters = $m[2];

                // Existe la funcion?
                if (method_exists($this, $function_name)) {

                    $res = $this->$function_name($node, $parameters);
                    // Y reemplazamos

                    $string = str_replace($origin, $res, $string);
                }
            }
        }

        return $string;
    }

    /**
     * Analiza los nodos hijos de $parent, ejecutando funciones
     */
    public function parse($parent)
    {

        $node = $parent->first;

        /*
        $children = $parent->get_children();
        $ids = [];
        foreach ( $children as $c) {
            $ids[] = $c->id;
        }
        var_dump ( "START $parent({$parent->parent}):" . join(',', $ids) ); /**/

        while($node) {
            // Al final del bucle, movemos a $node ->next si
            // $next_node es null
            $next_node = false;

            $start_char = $node->getName(false)[0];


            // Resolvemos variables y valores
            $node->value = $this->resolve_variables ( $node->value );
            $node->value = $this->resolve_functions ( $node->value, $node  );

            // Primero buscamos por variables.
            if ( $start_char == "$" ) {
                // Simple.
                $this->variables [ $node->name ] = $node->value;

                $next_node = $node->next;

                // Borramos el nodo
                $parent->deleteChild($node);

                // Avanzamos al siguiente,
                $node = $next_node;
                continue;
            }

            // Comandos at
            if ($start_char == "@") {

                // Si existe un método con este nombre, lo ejecutamos
                $method_name = 'at_' . substr ( $node->name, 1 );
                if ( method_exists( $this, $method_name ) ) {

                    $this->$method_name ( $node );
                }


                // Avanzamos al siguiente,
                // NOP. Algunos @ tienen bloques de contenido. Debemos analizarlos
                /*$node = $node->next;
                continue;*/


            }

            // Si tiene hijos, los parseamos
            if ($node->hasChildren()) {
                // En este punto, ya no deben existir bloques válidos de CSS.
                // Asi que todos los demás, estarán namespaceados

                // El último nodo procesado, para los namespace.. Los nuevos
                // nodos se añadirán al lado de este.
                if (!isset($last)) {
                    $last = $parent;
                }

                // Si este nodo tiene hijos, y su padre no es root, es un contenedor.
                // Lo movemos a la raiz, y le activamos el namespace, salvo que
                // indiquemos que puede tener hijos.

                if ($parent && !$parent->is_root && !$parent->can_haz_children) {



                    /*var_dump ( " Moviendo $node /{$node->parent}/ al lado de $last /{$last->parent}/");/**/

                    // Para que siga la cadena de proceso, guardamos
                    // el siguiente nodo antes de moverlo
                    $next_node = $node->next;

                    /*if ( !is_null ( $next_node ) )
                        var_dump ( " -- new Next: $next_node->id");
                    else
                        var_dump ( " -- new Next: NULL");
                    /**/



                    $node->setNamespaces($parent->getNamespacedSegments());
                    $node->moveNextTo($last);

                    $last = $node;


                }
                //else {

                $this->parse($node);
//                }
            }
            /*else {
                var_dump ( " Procesando $node /{$node->parent}/");
            }/**/


            if ($next_node === false) {
                $next_node = $node->next;
            }


            $node = $next_node;
        }
        /* var_dump ( "END $parent" ); /**/

    }

    /**
     * Constructor. Recibe varios ficheros CSS
     */
    public function __construct()
    {
        $files = func_get_args();

        // Si el 1er parámetro es un array, entonces lo usamos
        if ( is_array ( $files[0] ) ) {
            $files = $files[0];
        }

        // Creamos la raiz de todos los males. Digo, de todos los nodos
        $this->root = new Node(true);

        // Analizamos cada fichero
        foreach($files as $f) {

            $path = new Vendimia\Path\FileSearch($f, "assets/css" );
            $path->ext = 'css';

            // Si no existe, explotamos.
            if ( $path->notFound() ) {
                throw new Vendimia\Exception ( "CSS Asset '$f' not found", [
                    'Searched paths' => $path->searched_paths,
                ]);
            }

            // Guardamos este fichero
            $this->files_includes[] = $path->get();

            // Procesamos el CSS
            $css_struct = new Parser( $this->root, $path->get());
        }

        // Parseamos toda la estructura
        $this->parse ( $this->root );

    }

    public function getCss()
    {

        $css = '';
        foreach ( $this->root as $n ) {
            $css .= $n->buildCss();
        }

        return $css;
    }
}
