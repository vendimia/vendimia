<?php
namespace Vendimia\ORM\Parser;

use Vendimia\ORM\Entity;
use Vendimia\ORM\Field;
use Vendimia\Database\Field as DBField;
use ReflectionClass;
use ReflectionProperty;

/**
 * Analyze a entity and gets its configuration from annotations
 */
class EntityParser
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

    public function __construct($entity)
    {
        if (!is_subclass_of($entity, Entity::class)) {
            throw new \InvalidArgumentException('An ORM entity class or object is requiered.');
        }
        $reflection = new ReflectionClass($entity);

        // Obtenemos el namespace y el alias
        $aliases = new PHPAliasesParser(
            $reflection->getFileName(), 
            $reflection->getStartLine() - 1
        );

        $this->namespace = $reflection->getNamespaceName();
        $this->aliases = $aliases->asArray();

        // Ahora, obtenemos la información de los campos

        $fields = [];
        $primary_key_defined = false;
        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE) as $property) {

            $data = (new AnnotationParser($property->getDocComment()))->asArray();

            if (!$data['class']) {
                throw new \InvalidArgumentException("Class for '{$property->getName()}' field is missing.");
            }

            // Si hay una longitud en campo 0 y 1, la colocamos en 
            // el campo length
            if (key_exists(0, $data['properties'])) {
                $length = $data['properties'][0];

                if (key_exists(1, $data['properties'])) {
                    $length .= ', ' . $data['properties'][1];
                }
                $data['properties']['length'] = $length;
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

            // Si ya existe al menos una llame primaria, no definimos otra.
            if (isset($data['properties']['primary_key'])) {
                $primary_key_defined = true;
            }

            $fields[$property->getName()] = [
                $fqcn, $data['properties']
            ];
        }

        // Si no existe el campo definido en static::$primary_key, 
        // Lo creamos.
        if (!$primary_key_defined) {
            $fields[$entity::getPrimaryKeyField()] = [
                Field\Integer::class,
                [
                    'primary_key' => true,
                    'auto_increment' => true,
                ]
            ];
        }

        $this->fields = $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Small helper for emulating the ?? PHP7+ operator
     */
    public static function ifKey($array, $index, $default_value = null)
    {
        if (key_exists($index, $array)) {
            return $array[$index];
        } else {
            return $default_value;
        }
    }


    /**
     * Parses the field data and returns only the database-related information
     *
     * @return array Database info, as [fields, indexes, primary_keys]
     */
    public function getDatabaseInfo()
    {
        $fields = [];
        $indexes = [];
        $primary_keys = [];
        $renamed_fields = [];

        foreach ($this->fields as $fieldname => $parameters) {
            $class = $parameters[0];
            $properties = $parameters[1];

            $data = [
                'type' => $class::getDatabaseFieldType(),
                'null' => static::ifKey($properties, 'null', false),
                'default' => static::ifKey($properties, 'default', null),
            ];

            if ($d = static::ifKey($properties, 'primary_key')) {
                $data['primary_key'] = $d;
            }
            if ($d = static::ifKey($properties, 'auto_increment')) {
                $data['auto_increment'] = $d;
            }

            $length = false;

            if ($length = static::ifKey($properties, 'length')) {
                $data['length'] = $length;

            }

            // Algunos campos _necesitan_ una longitud
            if (!$length && DBField::needLength($data['type'])) {
                throw new \RuntimeException("Field '$fieldname' needs a length.");
            }

            if ($ai = static::ifKey($properties, 'auto_increment')) {
                $data['auto_increment'] = $ai;
            }

            $fields[$fieldname] = $data;

            // ** INDEXES **
            if (key_exists('index', $properties)) {
                $indexdef = $properties['index'];

                $indexname = $fieldname;
                if (is_bool($indexdef)) {
                    $indexdef = [];
                } elseif (is_array($indexdef)) {
                    if (key_exists('name', $indexdef)) {
                        $indexname = $indexdef['name'];
                        unset ($indexdef['name']);
                    }
                }

                if (!key_exists($indexname, $indexes)) {
                    $indexes[$indexname] = [
                        'unique' => false,
                        'fields' => [$fieldname],
                    ];
                }

                // Añadimos el nombre del campo a un índice, si no existe
                if (!in_array($fieldname, $indexes[$indexname]['fields'])) {
                    $indexes[$indexname]['fields'][] = $fieldname;
                }

                // Mezcamos la definición
                $indexes[$indexname] = array_replace(
                    $indexes[$indexname], 
                    $indexdef
                );
            }

            // ** PRIMARY KEYS **
            if (key_exists('primary_key', $properties)) {
                $primary_keys[] = $fieldname;
            }

            // ** RENAMED FIELDS **
            if (key_exists('renamed_from', $properties)) {
                $renamed_fields[$properties['renamed_from']] = $fieldname;
            }
        }

        return (object)compact('fields', 'indexes', 'primary_keys', 'renamed_fields');
    }
}