<?php
namespace Vendimia\Database;

use Vendimia\Database\ConnectorInterface;
/**
 * Interface for objects which can convert itself to a database representation.
 */
interface ValueInterface 
{
    /**
     * Function to convert this object in a valid database value.
     * 
     * The value won't be automagically escaped.
     */
    public function getDatabaseValue(ConnectorInterface $connector);
}