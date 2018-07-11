<?php
namespace Vendimia\Logger;

use Psr;

class Logger implements Psr\Log\LoggerInterface
{
    /** Message targets */
    private $target = [];

    /** This logger name */
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Adds a log registry at a given log level.
     */
    public function log($level, $message, array $context = [])
    {
        // Añadimos el nombre de este logger al $context
        $context['_logger_name'] = $this->name;

        // Añadimos el $level
        $context['_level'] = $level;

        $priority = LogLevel::PRIORITY[$level];
        foreach ($this->target as $target) {
            list($target_object, $target_priority) = $target;

            if ($priority <= $target_priority) {
                $target_object->write($message, $context);
            }
        }
    }

    /**
     * Adds a target to this logger.
     */
    public function addTarget(Target\TargetInterface $target, $level = LogLevel::DEBUG)
    {
        $this->target[] = [$target, LogLevel::PRIORITY[$level]];

        return $this;
    }
}
