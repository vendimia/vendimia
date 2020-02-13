<?php
namespace Vendimia;

/**
 * Mutable collection. Every method modifies the array in-place.
 */
class MutableCollection implements \ArrayAccess, \Iterator, \Countable, AsArrayInterface
{
    private $array = [];

    /**
     * Creates a collection from a array
     */
    public function __construct(array $array = [])
    {
        $this->array = $array;
    }

    /**
     * Sets the array by reference.
     *
     * Useful for working with a pre-existent array.
     */
    public function setArrayByRef(&$array)
    {
        $this->array = &$array;
    }


    /**
     * Returns a collection value
     */
    public function &get($element, $default_value = null)
    {
        if ($this->hasKey($element)) {
            return $this->array[$element];
        } else {
            return $default_value;
        }
    }

    /**
     * Adds an element at the end of the collection.
     */
    public function add($value, $key = null)
    {
        if (is_null($key)) {
            $this->array[] = $value;
        } else {
            $this->array[$key] = $value;
        }

        return $this;
    }

    /**
     * Returns whether this element value exist in the collection
     */
    public function has($element)
    {
        if (is_null($this->array)) {
            return false;
        }

        return in_array($element, $this->array);
    }

    public function hasKey($key)
    {
        if (is_null($this->array)) {
            return false;
        }

        return key_exists($key, $this->array);
    }

    /**
     * Remove an element by its key
     */
    public function remove($key)
    {
        unset($this->array[$key]);
    }

    /**
     * Remove and returns the first element.
     */
    public function shift()
    {
        return array_shift($this->array);
    }

    /**
     * Remove and returns the first element, in format [key, value];
     */
    public function shiftAssoc()
    {
        reset ($this->array);
        $key = key($this->array);
        if (is_null($key)) {
            return null;
        }

        $value = $this->array[$key];
        unset($this->array[$key]);
        return [$key, $value];
    }

    /**
     * Joins all this collection elements with a glue.
     *
     * @param string $glue Joining glue.
     * @return string The joined collection.
     */
    public function join($glue = '')
    {
        return implode($glue, $this->array);
    }


    public function asArray(): array
    {
        return $this->array;
    }

    /**
     * Creates a Collection from variadic arguments
     */
    public static function fromArgs(...$args)
    {
        return new static($args);
    }

    /**
     * Magic function for accessing elements as object properties
     */
    public function &__get($element) {
        return $this->get($element);
    }

    public function __set($element, $value) {
        $this->add($value, $element);
        return $this;
    }


    /**
     * Magic function helper for serialize this Collection
     */
    public function __sleep()
    {
        return ["array"];
    }

    /**
     * Magic function helper for unserialize a serialized Collection.
     */
    public function __wakeup()
    {

    }

    /**
     * Shortcut to $this->get
     */
    public function __invoke($element)
    {
        return $this->get($element);
    }


    // ArrayAccess implementation
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->add($value, $offset);
    }
    public function offsetUnset($offset) {}

    // Iterator implementation
    public function current()
    {
        return current($this->array);
    }
    public function key()
    {
        return key($this->array);
    }
    public function next()
    {
        next($this->array);
    }
    public function rewind()
    {
        reset($this->array);
    }
    public function valid()
    {
        return current($this->array) !== false;
    }

    // Countable implementation
    public function count()
    {
        return count($this->array);
    }
}
