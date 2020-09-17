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

    /** FQCN builder */
    private $fqcn;

    /** Fields */
    private $fields;

    public function __construct($entity)
    {
        if (!is_subclass_of($entity, Entity::class)) {
            throw new \InvalidArgumentException("'$entity' is not an ORM Entity class or object.");
        }
        $reflection = new ReflectionClass($entity);

        // Obtenemos el namespace y el alias
        $aliases = new PHPAliasesParser(
            $reflection->getFileName(),
            $reflection->getStartLine() - 1
        );

        $this->fqcn = new FQCNBuilder(
            $reflection->getNamespaceName(),
            $aliases->asArray()
        );

        // Ahora, obtenemos la información de los campos

        $fields = [];
        $primary_key_defined = false;
        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE) as $property) {

            $data = (new AnnotationParser($property->getDocComment()))->asArray();

            // Si una propiedad no tiene clase, la ignoramos silenciosamente.
            if (!$data['class']) {
                continue;
            }

            // Obtenemos el FQDN de la clase
            $fqcn = $this->fqcn->get($data['class']);

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

    public function getFQCNBuilder()
    {
        return $this->fqcn;
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

            // Este clase tiene un campo en la db?
            if (!$class::isDatabaseField()) {
                continue;
            }

            // El 'null' es para la entidad. No la necesitamos en este punto.
            $properties = $class::validateProperties(null, $fieldname, $properties);

            // Si hay un database_field, lo usamos
            if (isset($properties['database_field'])) {
                $fieldname = $properties['database_field'];
            }

            $data = [
                'type' => $class::getDatabaseFieldType(),
                'null' => static::ifKey($properties, 'null', true),
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
