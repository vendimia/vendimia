<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;

class Decimal extends FieldBase
{
    public static function getDatabaseFieldType()
    {
        return DBField::Decimal;
    }

    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return $connector->escape($this->value);
    }

    public static function validateProperties(Entity $entity = null, $field_name, array $properties)
    {
        // Char requiere la propiedad 0 o 'length'
        $length_in_index_0 = key_exists(0, $properties);
        $length_as_property = key_exists('length', $properties);

        if (!$length_in_index_0 && !$length_as_property) {
            throw new \InvalidArgumentException("Class '" . static::class .  "' of field '" . $field_name . "' requires a 'length' (or first) argument" );
        }
        $decimals = key_exists(1, $properties) ? $properties[1] : false;

        if (!$length_as_property) {
            $properties['length'] = $properties[0];
        }

        if ($decimals) {
            $properties['length'] .= ',' . $decimals;
        }

        return $properties;
    }

}