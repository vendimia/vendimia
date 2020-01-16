<?php
namespace Vendimia\AnnotationParser;

/**
 * Builds a FQCN from a namespace and a aliases array.
 */
class ClassNameBuilder
{
    private $namespace;
    private $aliases;

    public function __construct($namespace, array $aliases)
    {
        $this->namespace = $namespace;
        $this->aliases = $aliases;
    }

    /**
     * Builds the FQCN of a class. Also accepts static methods callable
     */
    public function getFQCN($class_name)
    {
        // Si empieza con la raiz, no hacemos nada
        if ($class_name[0] == '\\') {
            return $class_name;
        };

        $parts = explode('\\', $class_name);

        $base_ns = $parts[0];

        // Si la primera parte tiene un Paamayim Nekudotayim, lo separamos.
        // No importa si hay un paamayim al final
        $method = '';
        if ($pn_pos = strpos($base_ns, '::')) {
            $method = substr($base_ns, $pn_pos);
            $base_ns = $parts[0] = substr($base_ns, 0, $pn_pos);
        }

        // Verificamos si la primera parte es una alias
        if (key_exists($base_ns, $this->aliases)) {
            // El FQCN empieza con el alias
            $fqcn = $this->aliases[$base_ns];

            // Removemos la 1ra parte
            array_shift($parts);

            // Si aun queda partes, las añadimos al FQCN
            if ($parts) {
                $fqcn .= '\\' . join('\\', $parts);
            }

            if ($method) {
                $fqcn .= $method;
            }

        } else {
            // Si no es un alias, le añadimos el namespace
            $fqcn = $this->namespace . '\\' . $class_name;
        }

        return $fqcn;
    }
}
