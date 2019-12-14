<?php
namespace Vendimia\Cli\Command;

use Vendima;
use Vendimia\Cli;

use InvalidArgumentException;

abstract class CommandAbstract
{
    protected $console;
    protected $project;
    protected $args;

    public function __construct(Cli\ProjectManager $project, Cli\ArgumentsManager $args, Cli\Console $console)
    {
        $this->project = $project;
        $this->args = $args;
        $this->console = $console;
    }

    /**
     * Shows this module information.
     */
    public function writeHelp()
    {
        $console = $this->console;

        if (defined("static::COMMAND_NAME")) {
            $command = static::COMMAND_NAME;
        } else {
            $command = mb_strtolower(array_values(array_slice(
                explode('\\', get_class($this)), -1))[0]
            );
        }

        $console->writeHeader();
        $console->write("{white $command}: " . static::DESCRIPTION);
        echo PHP_EOL;
        $console->write("Usage: vendimia $command " . static:: CLI_ARGS);

        echo PHP_EOL;
        $console->writeDefinitions(static::HELP_OPTIONS);
    }

    /**
     * Checks for a valid project in $project. Fails otherwise
     */
    public function checkProject()
    {
        if ($this->project->notValid()) {
            $this->console->fail('Vendimia project not found or not specified.');
        }
    }

    public function appExists($app_name) {
        return $this->project->appExists($app_name);
    }

    /**
     * Checks for a valid PHP name
     */
    public function checkValidPHPLabel($label)
    {
        return preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $label);
    }

    /**
     * Obtains a app name from params
     */
    public function getAppName()
    {
        $app_name = $this->args->pop();

        // Ignoramos el 'for'
        if ($app_name == 'for') {
            $app_name = $this->args->pop();
        }

        if (is_null($app_name)) {
            throw new InvalidArgumentException('App name is missing.');
        }

        // El app_name debe ser un nombre válido.
        if (!$this->checkValidPHPLabel($app_name)) {
            throw new InvalidArgumentException('Invalid app name.');
        }

        return $app_name;
    }

    /**
     * Execution start for this command
     */
    public abstract function run();
}
