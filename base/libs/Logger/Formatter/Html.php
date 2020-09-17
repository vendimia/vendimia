<?php
namespace Vendimia\Logger\Formatter;

use Throwable;

/**
 * Converts the $contect array in a HTML table
 */
class Html implements FormatterInterface
{
    private $max_depth = 10;

    private function normalize($data, $depth = 0)
    {
        if ($depth > $this->max_depth) {
            return "MAX DEPTH REACHED";
        }

        if (is_array($data)) {
            $newdata = [];
            foreach ($data as $key=>$value)
            {
                $newdata[$key] = $this->normalize($value, $depth + 1);
            }
            return $newdata;
        }
        return $data;
    }

    /**
     * Formats the array passes as $context into HTML
     */
    public function formatContext($context)
    {
        $html = '<table style="border-collapse: collapse">';
        $context = $this->normalize($context);

        foreach ($context as $key=>$value) {
            $html .= '<tr>';
            $html .= '<th style="padding: 5px; border-bottom: 1px solid #eee; vertical-align: top; text-align: left">' . $key . '</th>';

            if (is_array($value)) {
                $value = $this->formatContext($value);
            }

            $html .= '<td style="padding: 5px; border-bottom: 1px solid #eee">' . $value . '</td>';
            $html .= '</tr>';
        }
        $html .= "</table>";

        return $html;
    }


    public function formatThrowable(Throwable $throwable)
    {
        $t_class = get_class($throwable);
        $t_description = $throwable->getMessage();
        $t_file = $throwable->getFile();
        $t_line = $throwable->getLine();
        $t_trace = $throwable->getTrace();

        $html = "<p>An unhandled <strong>{$t_class}</strong> exception has occurred:</p>\n\n";
        $html .= "<p><strong>{$t_description}</strong></p>\n\n";
        $html .= "<p>On file <strong>{$t_file}</strong>:{$t_line}</p>\n\n";
        $html .= "<h2>Stack trace</h2>\n\n";
        $html .= "<ol>";

        foreach ($t_trace as $t) {
            if (!isset($t['file'])) {
                $args = join(', ', $t['args'] ?? []);
                $html .= "<li><tt>{$t['class']}{$t['type']}{$t['function']}({$args})</tt></li>\n";
            } else {
                $html .= "<li><tt>{$t['file']}:{$t['line']}</tt></li>\n";
            }
        }

        $html .= "</ol>";

        return $html;
    }

    public function format($message, array $context)
    {

        // Las excecpiones las tratamos distinto.
        if (key_exists('exception', $context) &&
            $context['exception'] instanceof Throwable) {

            return $this->formatThrowable($context['exception']);
        } else {
            $html = "<h1>" . htmlentities($message) . "</h1>";
        }


        $html .= $this->formatContext($context);

        return $html;
    }
}
