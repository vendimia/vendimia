<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;
use Vendimia\DateTime as DT;

/**
 * BigInt field.
 */
class BigInt extends Integer
{
    public static function getDatabaseFieldType()
    {
        return DBField::BigInt;
    }
}