<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\DateTime as DT;

/**
 * Date field.
 */
class Date extends DateTime
{
    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return $connector->escape($this->value->format('Y-m-d'));
    }    
}