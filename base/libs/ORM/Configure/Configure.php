<?php
namespace Vendimia\ORM\Configure;

use Vendimia\ORM\Entity;
use ReflectionClass;
use ReflectionProperty;

/**
 * Create objects for each entity field
 */
class Configure
{
    /** Target class namespace */
    public $namespace;

    /** Target class aliases */
    public $aliases;

    /** Fields */
    public $fields;

    public function getFQCN($classname)
    {
        // Si empieza con un \, no hacemos nada
        if ($classname{0} == '\\') {
            return $classname;
        }

        $parts = explode('\\', $classname);

        // La 1ra parte es clave
        if (key_exists($parts[0], $this->aliases)) {
            $alias = $this->aliases[$parts[0]];
            $slice = array_slice($parts, 1);

            $fqcn = '\\' . $alias;

            if ($slice) {
                $fqcn .= '\\' . join('\\', $slice);
            }
        } else {
            // Si no existe el alias, le añadimos el namespace
            $fqcn = '\\' . $this->namespace . '\\' . $classname;
        }
        return $fqcn;
    }

    public function __construct(Entity $entity)
    {
        $reflection = new ReflectionClass($entity);

        // Obtenemos el namespace y el alias
        $aliases = new ParsePHPAliases(
            $reflection->getFileName(), 
            $reflection->getStartLine() - 1
        );

        $this->namespace = $reflection->getNamespaceName();
        $this->aliases = $aliases->asArray();

        // Ahora, obtenemos la información de los campos

        $fields = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE) as $property) {

            $data = (new ParseAnnotation($property->getDocComment()))->asArray();

            if (!$data['class']) {
                throw new \InvalidArgumentException("Class for '{$property->getName()}' field is missing.");
            }

            // Obtenemos el FQDN de la clase
            $fqcn = $this->getFQCN($data['class']);

            // Si hay un summary, lo colocamos en la propiedad 'caption',
            // si caption está vacío.
            if ($data['summary'] && (
                !key_exists('caption', $data['properties']) ||
                !$data['properties']['caption']
            )) {

                $data['properties']['caption'] = $data['summary'];
            }

            $fields[$property->getName()] = [
                $fqcn, $data['properties']
            ];
        }

        $this->fields = $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }
}