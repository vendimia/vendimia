<?php
namespace Vendimia\Logger\Target;

use Vendimia;
use Vendimia\Logger;

abstract class TargetBase
{
    protected $formatter = null;

    /**
     * Sets the default formatter to OneLiner
     */
    public function __construct() 
    {
        $this->formatter = new Logger\Formatter\OneLiner;
    }

    /**
     * Sets a formatter
     */
    public function setFormatter(Logger\Formatter\FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Returns the formatter
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

}
