<?php
namespace Vendimia\Cli;

/**
 * Class for write in console in ANSI colors if available.
 */
class Console {
    const COLORS = [
        'black' => 0,
        'red' => 1,
        'green' => 2,
        'yellow' => 3,
        'blue' => 4,
        'magenta' => 5,
        'cyan' => 6,
        'white' => 7,
    ];

    const MODULES = [
        'app' => 'white',
        'controller' => 'green',
        'form' => 'blue',
        'project' => 'white',
        'path' => 'cyan',
        'param' => 'white',
        'table' => 'green',
        'model' => 'cyan',
        'tabledef' => 'magenta',
    ];

    /**
     * These vars will be filled with \001 and \002 for hinting the readline
     * functions.
     */
    private static $rl_on = '';
    private static $rl_off = '';

    /** Used for cutting long lines, if not null */
    private static $term_width = null;

    /** Disable ANSI color printing */
    private static $disable_colors = false;

    /** */

    /**
     * Checks terminal capabilities
     */
    public function __construct()
    {
        if (!posix_isatty(STDOUT)) {
            $this->disable_colors = true;
        }
        else {
            // TODO: cross-platform support
        }
    }

    /**
     * Parse string with ANSI colors
     */
    public function parse($string)
    {
        $result = preg_replace_callback('/{(.+?) +(.+?)}/', function($matches) {
            list($dummy, $color, $text) = $matches;

            if ($color[0] == ':') {
                $tipo = substr($color, 1);
                if (key_exists($tipo, self::MODULES)) {
                    $color = self::MODULES[$tipo];

                } else {
                    $color = 'white';
                }
            }

            return $this->color($color, $text);
        }, $string);

        return $result;
    }

    /**
     * Returns a string with an ANSI color
     */
    public function color($color, $text)
    {
        $result = self::$rl_on;
        $result .= "\x1b[" . (30+self::COLORS[$color]) . ";1m";
        $result .= self::$rl_off;
        $result .= $text;
        $result .= self::$rl_on . "\x1b[0m" . self::$rl_off;

        return $result;
    }

    /**
     * Writes to the console, parse the string first, and adds a \n afterwards
     */
    public function write($text)
    {
        echo $this->parse($text) . "\n";
    }

    /**
     * Fails with and error, and exit
     */
    public function fail($message, $exitcode = 1)
    {
        $this->error($message);
        exit($exitcode);
    }

    public function error($message, $error_label = 'ERROR')
    {
        $this->write("{red $error_label}: $message");
    }

    /**
     * Shows a warning
     */
    public static function warning($message, $warning_label = 'Warning')
    {
        $this->write("{green $warning_label}: $message");
    }

    /**
     * Converts $status to ANSI-colored messages
     */
    public function fromStatus($command, $extra, $status = '')
    {
        switch ($status) {
            case 'overwrite':
                $this->write("{green OVERWRITE $command} $extra");
                break;
            case 'fail':
                $this->write("{red FAIL $command} $extra");
                break;
            case 'omit':
                $this->write("{black OMITTING $command} $extra");
                break;
            default;
                $this->write("{white $command} $extra");
                break;
        }
    }

    /**
     * Activate the readline hinting
     */
    public function readline_on()
    {
        static::$rl_on = "\001";
        static::$rl_off = "\002";
    }

    /**
     * Deactivate the readline hinting
     */
    public function readline_off()
    {
        static::$rl_on = '';
        static::$rl_off = '';
    }

    /**
     * Writes definitions in a fancy way.
     */
    public function writeDefinitions($definitions)
    {
        // Primero, ubicamos el padding adecuado
        $max_length = 0;
        foreach (array_keys($definitions) as $def) {
            $lenght = mb_strlen($def);
            if ($lenght > $max_length) {
                $max_length = $lenght;
            }
        }

        $base_padding = 5;
        $descr_padding = $max_length + 5;

        foreach ($definitions as $def => $descr) {

            $options = [];
            if (is_array($descr)) {
                $options = $descr;
                $descr = $descr['description'];

                if (isset($options['optional'])) {
                    $descr = $descr . ' (optional)';
                }
            }

            $var = $this->color('white', $def);

            $values = explode('\n', $descr);

            $line = str_repeat(' ', $base_padding) . $var;
            $line .= str_repeat(' ', $descr_padding - mb_strlen($def));

            $line .= array_shift($values);

            $newline_padding = str_repeat(' ', $descr_padding + $base_padding);

            while ($data = array_shift($values)) {
                $line .= PHP_EOL . $newline_padding . $data;
            }
            $this->write($line);
        }

    }

    /**
     * Prints a common header for CLI commands.
     */
    public function writeHeader()
    {
        $this->write('{white Vendima Framework} administration utility script.');
        $this->write('Version ' . VENDIMIA_VERSION);
        echo PHP_EOL;
    }

    /**
     * Static magic method for return a ANSI-colored text
     */
    public function __call($function, $args)
    {
        return self::color($function, $args[0]);
    }
}
