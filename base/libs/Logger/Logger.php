<?php
namespace Vendimia\Logger;

use Psr;

class Logger implements Psr\Log\LoggerInterface
{

    private $target = [];

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

    public function log($level, $message, array $context = [])
    {
        $priority = LogLevel::PRIORITY[$level];
        foreach ($this->target as $target) {
            list($target_object, $target_priority) = $target;

            if ($priority <= $target_priority ) {
                $target_object->write($message, $context);
            }
        }
    }

    /**
     * Adds a target to this logger.
     */
    public function addTarget(Target\TargetInterface $target, $level)
    {
        $this->target[] = [$target, LogLevel::PRIORITY[$level]];

        return $this;
    }
}
