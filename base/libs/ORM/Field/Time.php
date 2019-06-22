<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;
use Vendimia\DateTime\Time as T;

/**
 * Time field.
 */
class Time extends DateTime
{
    public function setValue($value)
    {
        $this->value = new T($value);
    }

    public static function getDatabaseFieldType()
    {
        return DBField::Time;
    }
}
