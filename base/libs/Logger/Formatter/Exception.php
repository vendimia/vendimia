<?php
namespace Vendimia\Logger\Formatter;

use Throwable;

/**
 * Generates a detailed report about an exception, and its environment.
 */
class Exception implements FormatterInterface
{
    public function format($message, array $context = [])
    {
        if ($message instanceof Throwable) {
            $t_class = get_class($message);
            $t_description = $message->getMessage();
            $t_file = $message->getFile();
            $t_line = $message->getLine();
            $t_trace = $message->getTrace();
        } else {
            $t_class = 'simple string';
            $t_description = (string)$message;
            $t_file = __FILE__;
            $t_line = __LINE__;
            $t_trace = debug_backtrace();
        }

        $html = "<p>An unhandled <strong>{$t_class}</strong> exception has occurred:</p>\n\n";

        $html .= "<p><strong>{$t_description}</strong></p>\n\n";

        $html .= "<p>On file <strong>{$t_file}</strong>:{$t_line}</p>\n\n";

        $html .= "<h2>Stack trace</h2>\n\n";

        $html .= "<ol>";

        foreach ($t_trace as $t) {
            $html .= "<li><tt>{$t['file']}:{$t['line']}</tt></li>\n";
        }

        $html .= "</ol>";

        $html .= "<h2>\$_SERVER</h2>\n\n";

        $html .= "<ul>";

        foreach ($_SERVER as $var => $val) {
            $val = print_r($val, true);
            $html .= "<li><tt>$var: $val</tt></li>\n";
        }
        $html .= "</ul>\n\n";

        return $html;
    }
}