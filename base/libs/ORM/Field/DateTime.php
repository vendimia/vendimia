<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;
use Vendimia\DateTime\DateTime as DT;

/**
 * DateTime field. Uses the Vendimia\DateTime class
 */
class DateTime extends FieldBase
{
    public function setValue($value) {
        $this->value = new DT($value);
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
