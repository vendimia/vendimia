<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;
use Vendimia\DateTime as DT;

use Vendimia\Form\Control\ControlAbstract;

/**
 * Integer field.
 */
class Integer extends FieldBase
{
    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return intval($this->value);
    }

    public function setValue($value)
    {
        if (is_object($value)) {
            if ($value instanceof ControlAbstract) {
                $this->value = intval($value->getValue());
            } else {
                throw new \InvalidArgumentException("Object of class '" . get_class($value) . "' cannot be implicitly converted to integer.");
            }
        } else {
            $this->value = intval($value);
        }        
    }

    public static function getDatabaseFieldType()
    {
        return DBField::Integer;
    }
}
