<?php
namespace Vendimia\Phpss;

use Vendimia;

/**
 * Nodo CSS
 *
 * Cada nodo tiene un nombre y un valor. También guarda la relación con sus
 * hermanos, y sus hijos.
 *
 * @author Oliver Etchebarne
 */
class Node implements \Iterator, \Countable
{

    // El generador de los IDs
    static $last_id = 0;

    // Es un nodo raiz?
    public $is_root = false;

    // El ID de este nodo
    public $id = 0;

    // true si es un contenedor, aunque no tenga hijos
    public $is_container = false;

    // El nombre completo del nodo
    public $name = '';

    // Cada elemento del nombre
    private $name_segments = [];

    // Los namespaces. Esto se añade antes de cada elemento del nombre,
    // separado por un espacio
    private $namespaces = [];

    // El valor de este nodo
    public $value = '';

    // true si este nodo no debe ser dibujado.
    public $ignore = false;

    // I CAN HAZ CHILDREN. true si no se namespacea sus hijos.
    public $can_haz_children = false;

    public $first = null;
    public $last = null;

    // Cantidad de hijos
    private $count = 0;

    public $parent = null;
    public $prev = null;
    public $next = null;

    private $iter_node = null;

    public function __construct($root = false)
    {
        // Le asignamos un ID
        $this->id = self::$last_id++;

        $this->is_root = $root;
    }

    /**
     * Sets the node name
     */
    public function setName ($name)
    {
        // Sacamos los elementos que afecta este nodo
        $this->name_segments = array_map ( function ($e) {
            return trim($e);
        }, explode(',', $name ) );

        $this->name = join(',' , $this->name_segments);

        return $this;
    }

    /**
     * Adds a single name segment to this node
     */
    public function addName ($name)
    {
        $this->name_segments[] = trim($name);

        $this->name = join(',' , $this->name_segments);
        return $this;
    }

    /**
     * Sets the namespaces to this node;
     *
     * @param array $namespaces This nodes namespaces.
     */
    public function setNamespaces (array $namespaces)
    {
        $this->namespaces = $namespaces;
        return $this;
    }

    /**
     * Adds a single namespace to this node.
     */
    public function addNamespace ($namespace)
    {
        $this->namespaces[] = $namespace;
        return $this;
    }

    /**
     * Returns al the name segments with namespaces.
     */
    public function getNamespacedSegments()
    {
        $names = [];
        foreach ( $this->name_segments as $e )  {
            if ( $this->namespaces ) foreach ( $this->namespaces as $n ) {
                if ( strpos ( $e, '&') !== false )
                    $names[] = str_replace ( '&', $n, $e );
                else
                    $names[] = $n . ' ' . $e;
            }
            else {
                $names[] = $e;
            }
        }

        return $names;
    }

    /**
     * Gets the node name, combining all the segments, and namespaced
     */
    public function getName ($namespaced = true, $pretty_space = ' ')
    {
        if ( $namespaced && $this->namespaces ) {
            // Si existe un caracter '&' en el nombre, lo reemplazamos por el
            // namespace
            $names = $this->getNamespacedSegments();
        }
        else {
            $names = $this->name_segments;
        }

        return join ( ',' . $pretty_space, $names );
    }

    /**
     * Gets this node name segments.
     */
    public function getSegments()
    {
        return $this->name_segments;
    }

    /**
     * Gets all the node children.
     */
    public function getChildren()
    {
        $children = [];

        $node = $this->first;

        while ( $node ) {
            $children[] = $node;
            $node = $node->next;
        }

        return $children;
    }

    /**
     * Adds a child at the end
     */
    public function addChild (Node $node)
    {
        $node->parent = $this;

        if (is_null($this->first)) {
            // Si no tiene hijos anteriormente
            $this->first = $node;
            $this->last = $node;
        } else {
            $node->prev = $this->last;
            $this->last->next = $node;

            $this->last = $node;

        }

        $this->count++;

        return $this;
    }

    /**
     * Destroys a child node
     */
    public function deleteChild (Node $node)
    {
        // El nodo debe ser hijo
        if ( $node->parent !== $this ) {
            throw new \RuntimeException ( "\$node is not a child of this node.i");
        }

        $node->detach();

        // Adios
        unset ($node);

        return null;
    }

    /**
     * Move this node next to $target, and make its sibiling.
     */
    public function moveNextTo($target)
    {

        // Nos desaparecemos de la cadena actual
        $this->detach();

        // Si target era el último hijo, le quitamos el lugar
        if (is_null($target->next)) {
            $this->next = null;
            $target->parent->last = $this;
        }
        else {
            // No, no es el último.
            $this->next = $target->next;
            $target->next->prev = $this;
        }

        // Nos anclamos al lado de target
        $this->prev = $target;
        $target->next = $this;

        $this->parent = $target->parent;

        return $this;
    }

    /**
     * Reemplaza este nodo por otros hermanos
     */
    public function replace(Node $node, $count = 0) {

        //var_dump ( "REPLACEING {$this}" );
        $orig_prev = $this->prev;
        $orig_next = $this->next;

        // Anclamos el 1er nodo al nexte de previous, si hay
        if ($orig_prev) {
            $orig_prev->next = $node;
        } else {
            // Si no hay, cambiamos el first del padre
            $this->parent->first = $node;
        }

        $node->prev = $orig_prev;

        // Repasamos los nodos que siguen, hasta el final

        if ( $node->next ) {
            $last = $next = $node->next;
        }
        else {
            $next = null;
            $last = $node ;
        }

        while ( $next ) {
            //var_dump ( "  UPDATING $next" );
            // Cambiamos los padres
            $next->parent = $this->parent;

            // Si ya no hay siguiente, salimos del bucle
            if ( is_null ( $next->next ) ) {
                $last = $next;
                break;
            }
            $next = $next->next;
        }

        // Anclamos el siguiente original al último
        $last->next = $orig_next;
        $orig_next->prev = $last;

        // Nos borramos -- YA NO ES POSIBLE ESTO EN PHP 7.1+
        //unset ($this);
    }

    /**
     * Añade un hermano, al final de la cadena de hijos.
     */
    public function add_sibling ( $node ) {
        $parent = $this->parent;

        $last = $parent->last;

        if ( $last ) {
            $last->next = $node;
            $node->prev = $last;
            $parent->last = $node;
        }
        else {
            // Este padre no tiene hijos
            $parent->first = $node;
            $parent->last = $node;
        }

        $node->parent = $this->parent;

    }

    /**
     * Devueve true si tiene al menos un hijo
     */
    public function hasChildren() {
        return $this->count > 0;
    }

    /**
     * Retorna el CSS de este nodo
     *
     * @param bool $format        Formato de dibujo: 'normal', 'condensed', 'ugly'
     * @param integer $indent   Nivel de identación de este nodo.
     * @param integer $tab_size Tamaño de la tabulación, en espacios.
     */
    public function buildCss( $format = 'condensed', $indent = 0, $tabsize = 4 ) {

        if ( $this->ignore ) {
            return '';
        }

        switch ( $format ) {
            case 'ugly':
                $str_tab = '';
                $str_space = '';
                $str_eol = '';
                $str_property_eol = '';
                break;
            case 'condensed':
                $str_tab = '';
                $str_space = ' ';
                $str_property_eol = '';
                $str_eol = PHP_EOL;

                break;
            default:
                $str_tab = str_repeat ( ' ', $tabsize * $indent );
                $str_space = ' ';
                $str_eol = PHP_EOL;
                $str_property_eol = PHP_EOL;
            }

        $css = $str_tab;

        // Si es un contenedor, y NO tiene hijos, no dibujamos esto
        if ( !$this->is_container ||
            ( $this->is_container && $this->hasChildren() ))  {


            $css .= $this->getName();

            // los comandos @ no usan : para el valor
            if ( $this->name {0} == '@') {



                if ( $this->value ) {
                    $css .= ' ' . $this->value;
                }
            }
            else {
                if ( !$this->hasChildren() && $this->value ) {
                    $css .= ':' . $str_space . $this->value;
                }
            }

            if ( $this->is_container ) {
                $css .= $str_space . '{' . $str_property_eol;

                foreach ( $this as $child ) {
                    $css .= $child->buildCss( $format, $indent + 1, $tabsize );
                }
                $css .= $str_tab . '}' . $str_eol;
            }
            else  {
                $css .= ';' . $str_property_eol;
            }

        }

        return $css;
    }

    /**
     * Dettatch this node from the node tree
     *
     * @return self
     */
    public function detach ()
    {
        $parent = $this->parent;
        $prev = $this->prev;
        $next = $this->next;

        // Movemos el next del prev, para que apunte al next de este nodo
        if ( $prev && $next ) {
            // En medio de la cadena
            $this->next->prev = $prev;
            $this->prev->next = $next;
        } elseif ( $prev && !$next ) {
            // Al final de la cadena
            $prev->next = null;
            $parent->last = $prev;
        } elseif ( !$prev && $next ) {
            // Al inicio de la cadena
            $next->prev = null;
            $parent->first = $next;
        } else {
            // Padre sin hijos
            $parent->first = null;
            $parent->last = null;

            $parent->count = 0;
        }

        $this->next = null;
        $this->prev = null;
        $this->parent = null;

        return $this;
    }


    function __toString() {
        $id = $this ->is_root?'ROOT':$this->id;
        $first = $this->first?$this->first->id:'-';
        $last = $this->last?$this->last->id:'-';
        $prev = $this->prev?$this->prev->id:'-';
        $next = $this->next?$this->next->id:'-';
        $parent = $this->parent ? $this->parent->id:'-';

        return "<ID$id P$parent {$this->name}:{$this->value}[F$first P$prev N$next L$last]>";
    }

    function count() {
        return $this->count;
    }

    function rewind() {
        $this->iter_node = $this->first;
    }
    function current() {
        return $this->iter_node;
    }
    function key() {
        return spl_object_hash( $this->iter_node );
    }
    function next() {
        if ( $this->iter_node )
            $this->iter_node = $this->iter_node->next;
    }
    function valid() {
        return !is_null ( $this->iter_node );
    }
}
