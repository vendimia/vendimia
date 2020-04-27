<?php
namespace Vendimia\Phpss;

/**
 * Analizador estructural de CSS
 *
 * Convierte los bloques estructurales de un CSS en phpss\node
 * para luego poder ser redibujados
 *
 * @author Oliver Etchebarne <oliver@x10.pe>
 */
class Parser
{

    /**
     * Los espacios en blanco
     */
    const SPACES = " \t\n\r";

    /**
     * El CSS original
     */
    private $raw;

    /** @type int Puntero al caracter analizado en $this->raw */
    private $raw_ptr;

    /** @type object Nodo padre de todos los nodos */
    public $root;

    /**
     * Analiza una línea. Usualmente es la declaración de un selector o
     * propiedad.
     *
     * @param string $char      Último caracter analizado.
     * @param int $mark         Marca desde donde debemos empezar a analizar.
     *
     * @return object           Nodo que representa a la línea.
     */
    private function processLine($char, $mark)
    {

        // Obtenemos la línea a procesar
        $line = trim ( strtr ( mb_substr (
            $this->raw,
            $mark,
            $this->raw_ptr - $mark - 1
        ), ["\n" => ""] ));

        // Creamos un nodo vacío
        $node = new node();

        // Si el caracter es un {, entonces analizamos este nuevo bloque
        if ($char == "{") {
            $node->setName ( $line);

            $this->parseBlock ( $node);
        }
        else {
            // Dividimos la línea por el :
            $colon = strpos ( $line, ':');

            if ($colon !== false) {
                $sname = trim(substr ( $line, 0, $colon ));
                $svalue = trim(substr ( $line, $colon + 1 ));
            }
            else {
                $sname = $line;
                $svalue = '';
            }

            $node->setName ( $sname);
            $node->value = $svalue;
        }

        // Si la línea empieza con @, entonces reajustamos el nombre y el valor
        if ($line[0] == "@") {
            $parts = explode ( ' ', $line, 2);

            $node->setName ( $parts[0]);
            if (isset ( $parts[1])) {
                $node->value = $parts[1];
            }
        }
        return $node;
    }

    /**
     * Analiza un bloque CSS.
     *
     * El análisis empieza en la posición actual de $this->raw, y acaba
     * en una llave, o en el fin del fichero.
     *
     * @param object $parent Nodo padre de todos los nodos de este bloque
     */
    function parseBlock(Node $parent)
    {
        // Estado actual del parser.
        $state = 'nothing';

        // Marca desde donde debemos empezar a procesar una línea
        $mark = 0;

        // true cuando estamos enmedio de un comentario
        $at_comment = false;

        while ($this->raw_ptr < mb_strlen ($this->raw)) {
            $char = mb_substr($this->raw, $this->raw_ptr, 1);
            $char_next = mb_substr($this->raw, $this->raw_ptr + 1, 1);

            // Avanzamos el puntero
            $this->raw_ptr++;

            // Ignoramos los comentarios
            if ($char == "/" && $char_next == "*") {
                $at_comment = true;
                continue;
            }
            if ($char == "*" && $char_next == "/" && $at_comment) {
                $at_comment = false;

                // Aumentamos uno, para evitar el "/"
                $this->raw_ptr++;
                continue;
            }

            if ($at_comment) {
                continue;
            }

            // Si encontramos un { o un ;, entonces procesamos la línea
            // empezando en la última marca.
            if ($char == "{" || $char == ";") {
                $node = $this->processLine ($char, $mark);

                // Añadimos el nodo al padre
                $parent->addChild($node);

                // Si el caracter es un {, es un contenedor. Aunque no
                // tenga hijos.
                if ($char == "{") {
                    $node->is_container = true;
                }

                // Regresamos al estado nada
                $state = 'nothing';
            }
            elseif ($char == '}') {
                // Acabó el bloque. Hay algo por procesar?
                if ($state != 'nothing') {
                    $node = $this->processLine($char, $mark);

                    // Añadimos el nodo al padre
                    $parent->addChild($node);
                }
                return;
            }
            // Si no es un espacio
            elseif (strpos(self::SPACES, $char) === false) {
                if ($state == 'nothing') {
                    $state = 'line';
                    $mark = $this->raw_ptr - 1;
                }
            }
        }
    }

    /**
     * Dibuja todos los nodos hijos de root
     */
    function getCss($ugly = false) {
        $css = '';
        foreach ($this->root as $node) {
            $css .= $node->getCss($ugly);
        }
        return $css;
    }

    function __construct(Node $root, $file) {
        $this->root = $root;
        $this->raw = file_get_contents($file);
        $this->parseBlock($this->root);
    }
}
