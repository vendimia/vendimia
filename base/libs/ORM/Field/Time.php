<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;
use Vendimia\DateTime;

/**
 * Time field.
 */
class Time extends DateTime
{
    public function setValue($value)
    {
        $this->value = new DateTime\Time($value);
    }

    public static function getDatabaseFieldType()
    {
        return DBField::Time;
    }
}
