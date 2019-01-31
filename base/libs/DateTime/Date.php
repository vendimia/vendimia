<?php
namespace Vendimia\DateTime;

use Vendimia\Database\ConnectorInterface;

/**
 * Date manupulation class.
 */
class Date extends DateTime
{
    public function __construct($source = null)
    {
        parent::__construct($source);

        // Eliminamos la hora
        $this->hour = 0;
        $this->minute = 0;
        $this->second = 0;

        $this->buildTimestampFromParts();
    }

    public function format($format = 'Y-m-d')
    {
        return parent::format($format);
    }

    /**
     * Returns the year day part
     */
    public function getYearDay()
    {
        return $this->yearday;
    }

    /**
     * Creates a Date object with today
     */
    public static function today()
    {
        return new static(time());
    }

    /**
     * Returns the most common date-time value for databases
     */
     public function getDatabaseValue(ConnectorInterface $connector)
     {
         return $connector->escape($this->format('Y-m-d'));
     }
}
