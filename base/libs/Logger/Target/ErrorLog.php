<?php
namespace Vendimia\Logger\Target;

use Vendimia\Logger;

class ErrorLog extends TargetBase implements TargetInterface
{
    /**
     * Sets the default formatter to OneLiner without date
     */
    public function __construct()
    {
        $this->formatter = new Logger\Formatter\OneLiner;
        $this->formatter->setDateFormat(null);
    }

    public function write($message, array $context)
    {
        error_log($this->formatter->format($message, $context));
    }
}
