<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;

class FixChar extends Char
{
    public static function getDatabaseFieldType()
    {
        return DBField::FixChar;
    }
}