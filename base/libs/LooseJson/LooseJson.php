<?php
namespace Vendimia\LooseJson;

/**
 * Loose JSON decoder.
 *
 * This decoder always parse the JSON as if it is an object, and always returns
 * an array.
 *
 * Elements are separated by comma, variable and value are separated  by colon.
 * CR, LF characters are ingnored.

 * {} and [] are the same, and they always return an array. As in PHP arrays,
 * elements with and without index can be mixed.
 *
 * Bare words are allowed, and they're trim()ed.  'null', 'true' and 'false'
 * barewords are replaced by its PHP constant equivalent.
 *
 */
class LooseJson
{
    const T_STRING = 0;
    const T_NUMBER = 1;
    const T_TRUE = 2;
    const T_FALSE = 3;
    const T_NULL = 4;
    const T_COLON = 5;
    const T_COMMA = 6;
    const T_ARRAY_START = 7;
    const T_ARRAY_END = 8;
    const T_END = 9;

    const TOKEN_NAME = [
        self::T_STRING => 'T_STRING',
        self::T_NUMBER => 'T_NUMBER',
        self::T_TRUE => 'T_TRUE',
        self::T_FALSE => 'T_FALSE',
        self::T_NULL => 'T_NULL',
        self::T_COLON => 'T_COLON',
        self::T_COMMA => 'T_COMMA',
        self::T_ARRAY_START => 'T_ARRAY_START',
        self::T_ARRAY_END => 'T_ARRAY_END',
        self::T_END => 'T_END',
    ];


    /** The JSON string */
    private $json;

    /** Last string chunk to be analyzed */
    private $buffer = '';

    /** JSON string length */
    private $length;

    /** Tokens */
    private $tokens = [];

    /** Resulting array */
    private $result = null;

    /** Pointer to the character being analyzed in the JSON string */
    private $pointer = 0;

    /** Token index to parse */
    private $t_index = 0;

    public function __construct($json)
    {
        $this->json = $json;
        $this->length = mb_strlen($json);

        if ($json == '') {
            $this->result = [];
        }
    }

    /**
     * Register a token from the buffer.
     */
    private function addToken($t_class, $value = null)
    {
        if (!$value) {
            $value = $this->buffer;
            $this->buffer = '';
        }
        $this->tokens[] = [$t_class, $value];
    }

    /**
     * Parses a bare string from the buffer
     */
    private function parseBareString()
    {
        $this->buffer = trim($this->buffer);

        // Solo añadimos bare strings que no sean vacíos
        if (!$this->buffer) {
            return;
        }

        // Algunos bare words son especiales
        if (is_numeric($this->buffer)) {
            $this->addToken(self::T_NUMBER);
        } elseif ($this->buffer == 'null') {
            $this->addToken(self::T_NULL);
        } elseif ($this->buffer == 'true') {
            $this->addToken(self::T_TRUE);
        } elseif ($this->buffer == 'false') {
            $this->addToken(self::T_FALSE);
        } else {
            $this->addToken(self::T_STRING);
        }

    }

    /**
     * Split JSON into tokens
     */
    private function tokenize()
    {
        $in_string = false;
        $quote_char = '';
        $escaped = false;
        $tokens = [];
        $block_close_char = null;

        // PARTE 1: Dividimmos el json en todos sus elementos
        while (true) {
            $char = mb_substr($this->json, $this->pointer, 1);
            if ($escaped) {
                $this->buffer .= $char;
                $escaped = false;
            } elseif ($char == '\\') {
                $escaped = true;
            } elseif ($in_string) {
                if ($char == $quote_char) {
                    $in_string = false;
                    $this->addToken(self::T_STRING);
                } else {
                    $this->buffer .= $char;
                }
            } else {
                if ($char == "'" || $char == '"') {
                    $quote_char = $char;
                    $in_string = true;

                    // Si hay algo en el buffer, lo tokenizamos como bare string
                    if ($this->buffer) {
                        $this->parseBareString();
                    }
                } elseif ($char == "\n" || $char == "\r") {
                    // Nada
                } elseif ($char == ',') {
                    // Lo que queda debe ser un bare string
                    $this->parseBareString();

                    // Añadimos la coma como token
                    $this->addToken(self::T_COMMA, $char);


                } elseif ($char == ':') {
                    // Lo que queda debe ser un bare string
                    $this->parseBareString();

                    // Añadimos la coma como token
                    $this->addToken(self::T_COLON, $char);
                } elseif ($char == "{" || $char == "[") {

                    if ($char == "{") {
                        $block_close_char = "}";
                    } else {
                        $block_close_char = "]";
                    }
                    // Lo que queda debe ser un bare string
                    $this->parseBareString();

                    $this->addToken(self::T_ARRAY_START, $char);

                } elseif ($char == $block_close_char) {
                    // Lo que queda debe ser un bare string
                    $this->parseBareString();

                    // Acabamos un bloque
                    $this->addToken(self::T_ARRAY_END, $char);
                } else {
                    $this->buffer .= $char;
                }
            }

            $this->pointer++;
            if ($this->pointer >= $this->length) {
                break;
            }
        }

        // Si queda algo en el buffer, debe ser un string
        if ($this->buffer) {
            $this->parseBareString();
        }

        // Para ayudar al parser, añadimos un end
        $this->addToken(self::T_END, '');
    }

    /**
     * Convert special tokens into PHP values
     */
    private function tokenToPHP($token)
    {
        if ($token[0] == self::T_TRUE) {
            return true;
        } elseif ($token[0] == self::T_FALSE) {
            return false;
        } elseif ($token[0] == self::T_NULL) {
            return null;
        } else {
            return $token[1];
        }
    }

    /**
     * Analyze the tokens and build an array
     */
    private function parse($in_array = false)
    {
        $index = 0;
        $name = null;
        $value = null;

        // True cuando hay un dos puntos, y tiene un indice stirng
        $numeric_index = true;

        $result = [];

        // Donde va el valor del token: name o value
        $target = 'name';

        while ($this->t_index < count($this->tokens)) {
            $token = $this->tokens[$this->t_index];
            [$t_code, $t_value] = $token;

            if ($t_code == self::T_COLON) {
                if (is_null($name)) {
                    throw new \UnexpectedValueException('Unexpected value when element name is null.');
                }
                $numeric_index = false;
                $target = 'value';
            } elseif ($t_code == self::T_COMMA ||
                    $t_code == self::T_END ||
                    $t_code == self::T_ARRAY_END) {

                if ($numeric_index) {
                    $result[$index] = $name;
                    $index++;
                } else {
                    $result[$name] = $value;
                }
                $name = null;
                $value = null;
                $target = 'name';

                if ($t_code == self::T_ARRAY_END) {
                    if (!$in_array) {
                        throw new \UnexpectedValueException('Unexpected token T_ARRAY_END.');
                    }
                    return $result;
                }
            } elseif ($t_code == self::T_ARRAY_START) {
                if ($target != 'value') {
                    throw new \UnexpectedValueException('Unexpected token T_ARRAY_START.');
                }

                $this->t_index++;
                $value = $this->parse(true);
            } else {
                if ($target == 'name') {
                    $name = $t_value;
                } else {
                    $value = $this->tokenToPHP($token);
                }
            }

            $this->t_index++;
        }

        $this->result = $result;
    }

    public function decode()
    {
        if (!is_null($this->result)) {
            return $this->result;
        }

        $this->tokenize();
        $this->parse();

        return $this->result;
    }

    public function getTokens()
    {
        return $this->tokens;
    }
}
