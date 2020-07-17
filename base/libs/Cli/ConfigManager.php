<?php
namespace Vendimia\Cli;

use Vendimia;

use Exception;
use InvalidArgumentException;

/**
 * Manages a array config file
 */
class ConfigManager
{
    private $config_file;
    private $config = [];
    private $console;
    
    public function __construct(Console $console, $config_file)
    {
        $this->console = $console;

        if (!file_exists($config_file)) {
            throw new Exception("Config file '$config_file' not found");
        }

        $this->config_file = $config_file;
        $this->config = file($config_file, FILE_IGNORE_NEW_LINES);

        // La última línea debe ser un '];', de lo contrario, esto no funcionará.
        if (end($this->config) != '];') {
            throw new InvalidArgumentException("Array config file doesn't end on '];'");
        }
    }

    /**
     * Adds lines at the end of the array
     */
    public function addLines(...$lines)
    {
        $offset = count($this->config) - 1;
        array_splice($this->config, $offset, 0, $lines);
    }

    public function save()
    {
        file_put_contents($this->config_file, join("\n", $this->config));
        $this->console->fromStatus('MODIFY', $this->config_file);

    }
}
