<?php
namespace Vendimia\Logger;

use Psr;

/**
 * Import loglevels from PSR specification.
 */
class LogLevel extends Psr\Log\LogLevel
{
    /**
     * Level numeric priorities.
     */
    const PRIORITY = [
        'emergency' => 0,
        'alert' => 1, 
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];
}
