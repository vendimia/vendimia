<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;
use Vendimia\DateTime as DT;

/**
 * DateTime field. Uses the Vendimia\DateTime class
 */
class DateTime extends FieldBase
{
    /**
     * Overloading setter for Vendimia\DateTime
     */
    public function setValue($value) {
        if ($value instanceof DT) {
            $this->value = $value;
        } else {
            $this->value = new DT($value);
        }
    }

    public static function getDatabaseFieldType()
    {
        return DBField::DateTime;
    }


    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return $this->value->getDatabaseValue($connector);
    }    
}