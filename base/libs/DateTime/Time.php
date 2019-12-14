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

        if (!is_null($source)) {
            // Eliminamos la fecha
            $this->year = 0;
            $this->month = 0;
            $this->day = 0;

            $this->buildTimestampFromParts();
        }
    }

    public function format($format = 'H:i:s')
    {
        return parent::format($format);
    }
}
