<?php
namespace Vendimia\Cli\Command;

use Vendimia\Cli\ProjectManager;
use Vendimia\Cli\TemplateManager;

class NewWrapper extends CommandAbstract
{
    public const COMMAND_NAME = 'new';
    public const DESCRIPTION = 'Creates various Vendimia elements.';
    public const CLI_ARGS = '<subcommand> [<args>]';
    public const HELP_OPTIONS = [
        'subcommand' => [
            'description' => "Subcommand to be executed.",
        ],
    ];

    public function writeSubcommandHelp()
    {
        $this->console->writeHeader();
        $this->console->write('Available "new" subcommands:');
        $this->console->writeDefinitions([
           'controller' => 'Creates a new controller file.',
        ]);
        echo PHP_EOL;
        $this->console->write('Use "vendimia new <subcommand> --help" to get more information about a subcommand.');

    }

    public function run()
    {
        $subcommand = $this->args->pop();

        if (is_null($subcommand)) {
            $this->writeSubcommandHelp();
            exit;
        }

        // Pasamos la posta a la nueva clase
        $subcommand_class = "\\Vendimia\\Cli\\Command\\NewSubcommand\\" . ucfirst($subcommand);

        if (!class_exists($subcommand_class)) {
            $this->console->fail("Invaild 'new' subcommand '$subcommand'. Use 'vendimia new' for more info.");
        }

        $subcommand = new $subcommand_class($this->project, $this->args, $this->console);

        $subcommand->run();

    }
}
