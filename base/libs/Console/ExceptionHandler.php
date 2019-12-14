<?php
namespace Vendimia\Console;

use Vendimia;

/**
 * Text-mode exception handler.
 *
 * This is used on the CLI, and when saving exceptions on log files
 */
class ExceptionHandler
{
    /**
     * Processs an Exception, and returns a string
     */
    public static function process($exception)
    {
        $class = get_class($exception);
        $file = $exception->getFile();
        $line_width = 70;

        $console = new Vendimia\Cli\Console();


        $o = "Unhandled exception " . $console->green($class) . " raised on file ";

        // Oh teh beauty...
        if (42 + strlen($file . $class) > 75 ) {
            $o .= "\n";
        }
        $o .= $console->parse("{blue $file}, line {$exception->getLine()}\n\n");

        $message = $exception->getmessage();
        if ($message) {
            $o .= $console->white($exception->getmessage()) . "\n\n";
        }

        // Hay más información?
        if (isset($exception->__EXPORTED_DATA)) {
            $o .= "Extra information:\n\n";
            foreach ($exception->__EXPORTED_DATA as $name => $value) {
                $o .= '- ' . Console::white($name) . ": $value\n\n";
            }
        }

        $o .= "Stack trace:\n\n";

        $trace = $exception->getTrace();

        // Añadimos el fichero original
        array_unshift ( $trace, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        $c = count($trace);

        foreach ($trace as $id => $data) {
            // Reparamos algunas cosas
            if (!isset($data['file'])) {
                $data['file'] = 'unknow';
            }
            if (!isset($data['line'])) {
                $data['line'] = 'unknow';
            }

            $o .= str_pad($c, 2, ' ', STR_PAD_LEFT);

            $o .= '· ' . $console->yellow($data['file']) . ":{$data['line']}\n";

            // Mostramos la línea de código
            if (file_exists($data['file'])) {
                $file = file($data['file']);
                $line = $data['line'] - 1;
                for ($l = $line - 1; $l <= $line + 1; $l++) {
                    if (!isset($file[$l])) {
                        continue;
                    }

                    $o .= '    ';
                    $o .= $console->green(str_pad ($l+1, 3, '0', STR_PAD_LEFT)) . ' ' ;

                    $dotdotdot = '';
                    $fileline = trim($file[$l], "\n");
                    if (strlen($fileline) > $line_width) {
                        $fileline = substr($fileline, 0, $line_width - 3);
                        $dotdotdot = '...';
                    }

                    if (intval($l) == intval($line))  {
                        $o .= $console->red($fileline) . $dotdotdot;
                    } else {
                        $o .= $fileline . $dotdotdot;
                    }
                    $o .= "\n";
                }
            }

            $o .= "\n";
            $c--;
        }

        return $o;
    }

    /**
     * Process and shows the text exception
     */
    public static function handler($exception)
    {
        echo self::process($exception);
        exit;
    }

}
