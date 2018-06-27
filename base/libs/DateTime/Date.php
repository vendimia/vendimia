<?php
namespace Vendimia\DateTime;

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
    }

    public function format($format = 'Y-m-d')
    {
        return parent::format($format);
    }
}
