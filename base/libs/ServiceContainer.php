<?php
namespace Vendimia;

/**
 * Object container for ServiceLocator pattern
 */
class ServiceContainer
{
    /** Object builders */
    private $builders = [];

    /** Stored object */
    private $objects = [];

    /**
     * Stores a closure or an object
     */
    public function bind($name, $closure)
    {
        if (is_object($closure)) {
             if ($closure instanceof \Closure) {
                $this->builders[$name] = $closure;
             }
             else {
                $this->objects[$name] = $closure;
             }
        }
    }

    /**
     * Obtains an already created instance of $name. Crates it otherwise.
     */
    public function get($name, ...$args)
    {
        if (isset($this->objects[$name])) {
            return $this->objects[$name];
        } else {
            $object = $this->build($name, ...$args);
            $this->objects[$name] = $object;
            return $object;
        }
    }

    /**
     * Builds and returns an object.
     *
     * @param string $name Builder name
     * @param array $args Variadic arguments for the builder constructor
     */
    public function build($name, ...$args)
    {
        if (!key_exists($name, $this->builders)) {
            throw new \RuntimeException ("Service '$name' undefined.");
        }
        $closure = $this->builders[$name];
        return $closure(...$args);
    }
}