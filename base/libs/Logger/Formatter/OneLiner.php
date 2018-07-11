<?php
namespace Vendimia\Logger\Formatter;

/**
 * Writes the message and the context in one line, with optional date/time and channel.
 */
class OneLiner implements FormatterInterface
{
    private $date_format = 'Y-m-d H:i:s';

    /**
     * Sets or disables the date format in the log line
     */
    public function setDateFormat($date_format)
    {
        $this->date_format = $date_format;
    }

    public function format($message, array $context = [])
    {
        $logname = $context['_logger_name'];

        if (is_null($logname)) {
            $logname = strtoupper($context['_level']);
        } else {
            $logname .= '.' . strtoupper($context['_level']);
        }

        $parts = [];

        if ($this->date_format) {
            $parts[] = date($this->date_format);
        }

        $parts[] = $logname;
        $parts[] = $message;


        // Si hay un null, lo removemso
        $parts = array_filter($parts);

        return join (' ', $parts);
    }
}
