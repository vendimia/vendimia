<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;
use Vendimia\DateTime\Date as D;

/**
 * Date field.
 */
class Date extends DateTime
{
    public function setValue($value)
    {
        $this->value = new D($value);
    }

    public static function getDatabaseFieldType()
    {
        return DBField::Date;
    }
}
