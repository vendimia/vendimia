<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ValueInterface;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;

/**
 * One-to-Many relationship
 */
class OneToMany extends FieldBase
{
    private $target_class;
    private $entity_set;

    protected static $is_database_field = false;

    public function __construct(Entity $entity, $field_name, array $properties)
    {
        parent::__construct($entity, $field_name, $properties);

        $class = $entity->getClass();
        $fqcn = $class::getFQCNBuilder();
        $this->target_class = $fqcn->get(trim($this->properties['target_class'], '@'));

    }
    public static function getDatabaseFieldType()
    {
        return DBField::ForeignKey;
    }

    public static function validateProperties(Entity $entity = null, $field_name, array $properties)
    {
        $in_index_0 = isset($properties[0]);
        $as_property = isset($properties['target_class']);

        if (!$in_index_0 && !$as_property) {
            throw new \InvalidArgumentException("Class '" . static::class .  "' of field '" . $field_name . "' requires a 'target_class' (or first) argument" );
        }

        if (!$as_property) {
            $properties['target_class'] = $properties[0];
        }


        if ($entity)  {
            if (!isset($properties['foreign_key'])) {
                $properties['foreign_key'] = $entity->getClass(true) . '_id';
            }
        }

        return $properties;
    }
    public function setValue($value)
    {
        throw new \UnexpectedValueException("Can't assing a value to this field '{$this->field_name}'.");
    }

    public function isDependant()
    {
        // Este campo depende del contenido del campo padre
        return true;
    }

    public function getValue()
    {
        if (is_null($this->entity_set)) {
            $class = $this->target_class;

            if (!class_exists($class)) {
                throw new \RuntimeException("Class '$class' required for field '{$this->field_name}' doesn't exists.");
            }

            $this->entity_set = $class::newConstrained([
                $this->properties['foreign_key'] => $this->entity->pk()
            ]);
        }
        return $this->entity_set;
    }
}
