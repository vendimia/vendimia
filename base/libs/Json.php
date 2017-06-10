<?php
namespace Vendimia;

/**
 * Relaxed JSON decoder
 *
 * This decoder always takes the JSON string as a object, and returns
 * an associative array. Unquoted strings are allowed.
 */
class Json
{
    /** The JSON string */
    private $json;

    /** JSON string length */
    private $length;

    /** Pointer to the character being analyzed in the JSON string */
    private $pointer = 0;

    public function __construct($json)
    {
        $this->json = $json . ',';
        $this->length = mb_strlen($json);
    }

    /**
     * Returns a PHP value from a string
     */
    public function valueToPHP($data)
    {
        if ($data === '') {
            return null;
        }

        if (is_string($data)) {
            // Nos fijamos si es un quoted-string
            $quote_char = $data{0};
            if ($quote_char == '"' || $quote_char == "'") {
                return trim($data, $quote_char);
            }
        } 

        if (is_numeric($data)) {
            $return = $data + 0;
        } elseif ($data == 'null') {
            $return = null;
        } elseif ($data == 'true') {
            $return = true;
        } elseif ($data == 'false') {
            $return = false;
        } else {
            $return = $data;
        }

        return $return;
    }

    public function decode($end_char = null)
    {
        $var = '';
        $val = '';
        $associative = false;
        $index = 0;
        $chunk = '';

        $in_string = false;
        $string_quote = '';
        $result = [];
        $escaped = false;

        // True cuando un value empieza con @. Obtenemos su FQCN
        $class_value = false;

        // True cuando hemos acabado de analizar un token (como , o :)
        // Los espacios siguientes serán ignorados hasta que aparezca
        // un caracter cualquiera
        $ignore_spaces = true;

        while (true) {
            $char = mb_substr($this->json, $this->pointer, 1);

            if ($escaped) {
                $chunk .= $char;
                $this->pointer++;
                $escaped = false;
                continue;
            }

            if ($char == '@' && $chunk == '') {
                $chunk .= $char;
                $this->pointer++;
                $class_value = true;
                continue;
            }

            if ($char == '\\' && !$class_value) {
                $escaped = true;
                $this->pointer++;
                continue;
            }

            if ($in_string) {
                if ($char == $string_quote) {
                    $chunk .= $string_quote;

                    $in_string = false;
                    $string_quote = '';
                    $ignore_spaces = true;

                } else {
                    $chunk .= $char;
                }
            } else {
                if (($char == ' ' || $char == "\n") && $ignore_spaces) {
                    $this->pointer++;
                    continue;
                } 

                $ignore_spaces = false;
                if ($char == '"' || $char == "'") {
                    $in_string = true;
                    $string_quote = $char;

                    continue;
                } elseif ($char == ',' || ($end_char && $char == $end_char)) {
    
                    // Analizamos todo lo anterior
                    if (!$associative) {
                        $var = $index++;
                    }

                    $val = $this->valueToPHP($chunk);
                    $result[$var] = $val;

                    $chunk = '';
                    $associative = false;
                    $val = '';
                    $var = '';

                    $class_value = false;

                    if ($char == $end_char) {
                        break;
                    }

                    $ignore_spaces = true;
                } elseif ($char == ':') {
                    $var = $this->valueToPHP($chunk);
                    $associative = true;
                    $chunk = '';
                    $val = '';
                    $ignore_spaces = true;
                } elseif ($char == '{' || $char == '[') {
                    // Redecode!
                    if ($char == '{') {
                        $end_char = '}';
                    } else {
                        $end_char = ']';
                    }
                    $this->pointer++;

                    $chunk = $this->decode($end_char);

                } else {
                    $chunk .= $char;
                }
            }

            $this->pointer++;
            if ($this->pointer > $this->length) {
                break;
            }
        }
        return $result;
    }
} 