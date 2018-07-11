<?php
namespace Vendimia\Logger\Formatter;

interface FormatterInterface
{
    /**
     * Format $message and $context
     */
    public function format($message, array $context);
}
