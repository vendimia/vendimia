<?php
namespace Vendimia;

use ReflectionClass;
use ReflectionProperty;
use ArrayAccess;
use Iterator;
use LogicException;

/**
 * Simple DTO implementation.
 */
abstract class DataContainer implements ArrayAccess, Iterator, AsArrayInterface
{
    private $properties = [];
    private $required = [];

    public function __construct(array $data = [])
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $r) {
            $this->properties[] = $r->name;

            if (!strpos($r->getDocComment(), '@optional') !== false) {
                $this->required[] = $r->name;
            }
        }
    }

    /**
     * Sets properties in batch
     */
    public function fill(array $args)
    {
        foreach ($args as $property => $value) {
            $this->$property = $value;
        }
    }

    /**
     * Returns if all non-optional fields are not null.
     */
    public function isComplete()
    {
        foreach ($this->required as $field) {
            if (is_null($this->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the required fields with null value.
     */
    public function missingFields()
    {
        $res = [];
        foreach ($this->required as $field) {
            if (is_null($this->$field)) {
                $res[] = $field;
            }
        }

        return $res;
    }

    /**
     * Magic method to avoid setting undeclared properties.
     */
    public function __set($name, $value)
    {
        throw new LogicException("Trying to set an undeclared property '$name'.");
    }

    /**
     * Magic method to avoid getting undeclared properties.
     */
    public function __get($name)
    {
        throw new LogicException("Trying to access an undeclared property '$name'.");
    }

    /**
     * Magic method to show only public properties when var_export and friends
     * are executed in this object.
     */
    public function __debugInfo()
    {
        return $this->asArray();
    }

    public function offsetExists($offset)
    {
        return in_array($this->properties, $offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        throw new LogicException("DataContainer object can't unset a property.");
    }

    public function current()
    {
        return $this->{current($this->properties)};
    }

    public function key()
    {
        return current($this->properties);
    }

    public function next()
    {
        next($this->properties);
    }

    public function rewind()
    {
        reset($this->properties);
    }

    public function valid()
    {
        return current($this->properties) !== false;
    }

    public function asArray()
    {
        $props = [];
        foreach ($this->properties as $prop) {
            $props[$prop] = $this->$prop;
        }
        return $props;
    }
}
