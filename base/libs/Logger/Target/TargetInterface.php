<?php
namespace Vendimia\Logger\Target;

interface TargetInterface
{
    /**
     * Write a log message to this target.
     *
     * This method should format first the message using a
     * Vendimia\Logger\Formatter instance.
     */
    public function write($message, array $context);
}
