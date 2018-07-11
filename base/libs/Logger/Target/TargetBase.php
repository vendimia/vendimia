<?php
namespace Vendimia\Logger\Target;

use Vendimia;
use Vendimia\Logger;

abstract class TargetBase
{
    protected $formatter = null;

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
