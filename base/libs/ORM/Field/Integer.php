<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\DateTime as DT;

/**
 * Date field.
 */
class Integer extends FieldBase
{
    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return intval($this->value);
    }    
}
