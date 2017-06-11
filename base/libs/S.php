<?php
namespace Vendimia;

/**
 * String object implementation
 */
class S implements \ArrayAccess, \Iterator
{

	/** The actual string */
	private $string;

    /** String encoding */
    private $encoding;

    /** Iterator index */
    private $iter_index;

	/** Function mapping for simple PHP functions */
	private $functions = [
		'toUpper' => 'mb_strtoupper',
        'toLower' => 'mb_strtolower',
        'slice' => 'mb_substr',
        'length' => 'mb_strlen',
        'indexOf' => 'mb_strpos',
        'pad' => 'str_pad',
        'find' => 'mb_strstr',
	];

	/**
	 * Constructor from a string
     *
     * @param string $string String literal
     * @param string $encoding Optional string encoding. Default autodetects.
	 */
	function __construct($string = '', $encoding = false)
    {
        $this->string = (string)$string;

        if (!$encoding) {
            $this->encoding = mb_detect_encoding($string);
        }
        else {
            $this->encoding = $encoding;
        }
	}

	function __call($function, $args = [])
    {
        return $this->callFunction($function, $args);
	}

    /**
     * Call PHP functions over the string.
     *
     * @return S New object with function result
     */
    private function callFunction($function, $args = [])
    {
        // Si existe un equivalente en $thid->_functions, lo
        // usamos.
        if (isset($this->functions[$function])) {
            $real_function = $this->functions[$function];
        } else {
            $real_function = $function;
        }

        // Existe la función?
        if (!is_callable($real_function)) {
            throw new \BadFunctionCallException("'$function' is not a valid method.");
        }

        // Colocamos la cadena como primer paráemtro
        array_unshift($args, $this->string);

        // Cambiamos la $encoding interna de las funciones mb_
        if (!mb_internal_encoding($this->encoding)) {
            throw new \RuntimeException("Error colocando la $encoding a {$this->encoding}");
        }

        // Llamamos a la función
        $res = call_user_func_array($real_function, $args);
        
        // Si lo que devuelve es un string, lo reencodeamos
        if (is_string($res)) {
            return new self($res);
        } else {
            return $res;
        }
    }

    /**
     * Appends a string
     *
     * @param string $string String to append
     */
    public function append($string)
    {
        return new self($this->string . (string)$string);
    }

    /**
     * Prepends a string
     *
     * @param string $string String to prepend
     */
    public function prepend($string)
    {
        return new self((string)$string . $this->string);
    }

    /**
     * Insert a string in a position
     *
     * @param integer $position Character where to insert
     * @param string $string String to insert 
     */
    public function insert($position, $string)
    {
        return new self((string)($this(0, $position) . $string . $this($position)));
    }

    /**
     * Shortcut for left-padding the string
     *
     * @param int $length Padding lenght
     * @param string $fill Fill character
     */
    public function pad_left($length, $fill = " ")
    {
        return $this->pad($length, $fill, STR_PAD_LEFT);
    }

    /**
     * Shortcut for right-padding the string
     *
     * @param int $length Padding lenght
     * @param string $fill Fill character
     */
    public function pad_right($length, $fill = " ")
    {
        return $this->pad($length, $fill, STR_PAD_RIGHT);
    }

    /**
     * Shortcut for both-padding (centering) the string
     *
     * @param int $length Padding lenght
     * @param string $fill Fill character
     */
    public function pad_both($length, $fill = " ")
    {
        return $this->pad($length, $fill, STR_PAD_BOTH);
    }

    /**
     * Replace a substring for another
     *
     * @param string $from Cadena a buscar
     * @param string $to Cadena a reemplazar
     */
    public function replace($from, $to, $count = null)
    {
        return new self(str_replace($from, $to, $this->string, $count));
    }

    /**
     * Applies a sprintf() function to the string
     *
     * @param mixed Variadic positional replace values
     */
    public function sprintf(...$args)
    {
        return new self(sprintf($this->string, ...$args));
    }

    /**
     * Magic method to perform a substringargumentos
     */
    public function __invoke(...$args)
    {
        return $this->callFunction('mb_substr', $args);
    }

	/**
	 * Returns the string
     *
	 * @return string
	 */
	function __toString() {
		return $this->string;
	}

    /***** IMPLEMENTACIÓN DEL ARRAYACCESS *****/
    public function offsetExists($offset)
    {
        return $offset >= 0 && $offset < mb_strlen($this->string);
    }

    public function offsetGet($offset)
    {
        return new self(mb_substr($this->string, $offset, 1 ));
    }

    public function offsetSet($offset, $value)
    {

        // $value debe ser un string
        $value = (string)$value;

        $this->string = 
            mb_substr($this->string, 0, $offset ) .
            $value .
            mb_substr($this->string, $offset + 1);
    }

    public function offsetUnset($offset)
    {
        $this->string = 
            mb_substr($this->string, 0, $offset ) .
            mb_substr($this->string, $offset + 1);
        
    }
    

    public function current()
    {
        return $this[$this->iter_index];
    }

    public function key()
    {
        return $this->iter_index;
    }

    public function next()
    {
        $this->iter_index++;
    }

    public function rewind()
    {
        $this->iter_index = 0;
    }

    public function valid ()
    {
        return $this->offsetExists($this->iter_index);
    }
}