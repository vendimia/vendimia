<?php
namespace Vendimia\Cli\Command;

use Vendimia;
use Throwable;
use ParseError;

class Shell extends CommandAbstract
{
    public const DESCRIPTION = 'Run a expression-only light PHP shell.';
    public const CLI_ARGS = '';
    public const HELP_OPTIONS = [
    ];

    private $prompt = '> ';
    private $command_line;

    private function readLines()
    {
        $line = readline($this->prompt);

        if ($line === false) {
            return [null];
        }

        readline_add_history($line);

        $lines[] = "return {$line};";

        return $lines;
    }

    public function run()
    {
        $this->checkProject();
        $this->prompt = $this->project->getName() . '> ';

        while(true) {
            foreach ($this->readLines() as $this->command_line) {
                try {
                    if (strpos($this->command_line, '$this')) {
                        throw new ParseError('Use of $this is forbidden.');
                    }
                    if (is_null($this->command_line)) {
                        break 2;
                    }

                    var_dump(eval($this->command_line));
                } catch (Throwable $e) {
                    $this->console->error($e->getMessage(), get_class($e));
                }
            }
        }

        $this->console->write('Exiting.');
    }
}
