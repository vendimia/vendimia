<?php
namespace Vendimia\ORM\Parser;

/**
 * Class for extrapolate a classname into a FQCN
 */
class FQCNBuilder
{
    /** Target class namespace */
    public $namespace;

    /** Target class aliases */
    public $aliases;

    public function __construct($namespace, $aliases)
    {
        $this->namespace = $namespace;
        $this->aliases = $aliases;
    }

    public function get($classname)
    {
        // Si empieza con un \, no hacemos nada
        if ($classname[0] == '\\') {
            return $classname;
        }

        $parts = explode('\\', $classname);

        // La 1ra parte es clave
        if (key_exists($parts[0], $this->aliases)) {
            $alias = $this->aliases[$parts[0]];
            $slice = array_slice($parts, 1);

            $fqcn = $alias;

            if ($slice) {
                $fqcn .= '\\' . join('\\', $slice);
            }
        } else {
            // Si no existe el alias, le añadimos el namespace
            $fqcn = $this->namespace . '\\' . $classname;
        }
        return $fqcn;
    }
}
