<?php
namespace Vendimia\Cli;

use Vendimia;

/**
 * Manages the arguments pased in the command line
 */
class ArgumentsManager
{

    /** Word list of arguments */
    private $args = null;

    /** Options with the -- prefix */
    private $options = null;

    /**
     * Parses the argument list.
     */
    public function __construct($argv)
    {
        $this->args = new Vendimia\Collection();
        $this->options = new Vendimia\Collection();

        foreach ($argv as $arg) {
            if (substr($arg, 0, 2) == '--') {
                $option = substr($arg, 2);
                $value = true;
                if ($equal_pos = strpos($option, '=')) {
                    $value = substr($option, $equal_pos + 1);
                    $option = substr($option, 0, $equal_pos);
                }

                $this->options->add($value, $option);
                continue;
            }
            $this->args->add($arg);
        }
    }

    /**
     * Returns and remove THE FIRST argument
     */
    public function pop()
    {
        return $this->args->shift();
    }

    /**
     * Obtains an argument using an index
     */
    public function get($index = 0)
    {
        return $this->args[$index];
    }

    /**
     * Returns an option value, or null if it doesn't exists.
     */
    public function getOption($name)
    {
        return $this->options[$name];
    }

    public function hasOption($option_name)
    {
        return $this->options->hasKey($option_name);
    }
}
