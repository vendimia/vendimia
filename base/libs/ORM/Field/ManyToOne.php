<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ValueInterface;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;

/**
 * Many-to-one relationship
 */
class ManyToOne extends FieldBase
{
    /** Class of the 'one' entity */
    private $fk_class;

    /** Foreign key field name */
    private $fk_field;

    public function __construct(Entity $entity, $field_name, array $properties)
    {
        parent::__construct($entity, $field_name, $properties);

        // Le sacamos la @, y lo expandimos
        $class = $entity->getClass();
        $fqcn = $class::getFQCNBuilder();
        $this->fk_class = $fqcn->get(trim($this->properties['target_class'], '@'));

        $fk_class = $this->fk_class;
    }

    public function getKeyField()
    {
        return $this->value;
    }

    /**
      *  Default setter
      */
    public function setValue($value)
    {
        if ($value instanceof Entity) {
            $this->value = $value;
            $this->fk_value = $value->pk();
        } else {
            $this->fk_value = $value;
            $class = $this->fk_class;
            $this->value = $class::get($value);
        }
    }

    public static function getDatabaseFieldType()
    {
        return DBField::ForeignKey;
    }

    public static function validateProperties($field_name, array $properties)
    {

        // Char requiere la propiedad 0 o 'length'
        $in_index_0 = isset($properties[0]);
        $as_property = isset($properties['target_class']);

        if (!$in_index_0 && !$as_property) {
            throw new \InvalidArgumentException("Class '" . static::class .  "' of field '" . $field_name . "' requires a 'target_class' (or first) argument" );
        }

        if (!$as_property) {
            $properties['target_class'] = $properties[0];
        }

        if (!isset($properties['database_field'])) {
            $properties['database_field'] = $field_name . '_id';
        }        

        return $properties;
    }
}