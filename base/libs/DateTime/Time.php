<?php
namespace Vendimia\DateTime;

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
    }

    public function format($format = 'H:i:s')
    {
        return parent::format($format);
    }
}
