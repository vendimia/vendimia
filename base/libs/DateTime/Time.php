<?php
namespace Vendimia\DateTime;

use Vendimia\Database\ConnectorInterface;


/**
 * Date manupulation class.
 */
class Time extends DateTime
{
    public function __construct($source = null)
    {
        parent::__construct($source);

        // Eliminamos la fecha
        $this->year = 0;
        $this->month = 0;
        $this->day = 0;

        $this->buildTimestampFromParts();
    }

    public function format($format = 'H:i:s')
    {
        return parent::format($format);
    }

    /**
     * Returns the most common time value for databases
     */
     public function getDatabaseValue(ConnectorInterface $connector)
     {
         return $connector->escape($this->format('H:i:s'));
     }
}
