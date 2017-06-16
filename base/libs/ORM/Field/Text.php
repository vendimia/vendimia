<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;
use Vendimia\DateTime as DT;

/**
 * SmallInt field.
 */
class Text extends FieldBase
{
    public static function getDatabaseFieldType()
    {
        return DBField::Text;
    }
}