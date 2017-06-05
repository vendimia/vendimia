<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;

class Char extends FieldBase
{
    public function __construct(Entity $entity, array $properties)
    {
        // Char requiere la propiedad 0 o 'length'
        $length_in_index_0 = key_exists(0, $properties);
        $length_as_property = key_exists('length', $properties);

        if (!$length_in_index_0 && !$length_as_property) {
            throw new \InvalidArgumentException("'" . static::class .  "' requires a 'length' (or first) argument" );
        }

        if (!$length_as_property) {
            $properties['length'] = $properties[0];
        }

        parent::__construct($entity, $properties);
    }

    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return $connector->escape($this->value);
    }
}