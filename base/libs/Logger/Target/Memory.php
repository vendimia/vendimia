<?php
namespace Vendimia\Logger\Target;

use Vendimia\Logger;

/**
 * Saves the log to memory and returns the lines as an array
 */
class Memory extends TargetBase implements TargetInterface
{
    private $storage = [];

    public function __construct()
    {
        // Por defecto, usa el OneLiner
        $this->formatter = new Logger\Formatter\OneLiner;
    }

    public function write($message, array $context)
    {
        $this->storage[] = $this->formatter->format($message, $context);
    }

    public function getMessages()
    {
        return $this->storage;
    }
}
