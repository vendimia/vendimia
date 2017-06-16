<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;

class Blob extends FieldBase
{
    public static function getDatabaseFieldType()
    {
        return DBField::Blob;
    }
}