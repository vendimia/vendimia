<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;

class Boolean extends FieldBase
{
    public static function getDatabaseFieldType()
    {
        return DBField::Boolean;
    }    

    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return $connector->escape($this->value);
    }    
}