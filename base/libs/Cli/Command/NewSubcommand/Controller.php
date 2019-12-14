<?php
namespace Vendimia\Cli\Command\NewSubcommand;

use Vendimia\Cli\Command\CommandAbstract;
use Vendimia\Cli\ProjectManager;
use Vendimia\Cli\TemplateManager;

use InvalidArgumentException;

class Controller extends CommandAbstract
{
    public const DESCRIPTION = 'Creates a new controller with an optional view for an app.';
    public const CLI_ARGS = '[for] <app_name> <controller_name> [--no-view]';
    public const HELP_OPTIONS = [
        'for' => [
            'optional' => true,
            'description' => 'Syntactic-sugar word'
        ],
        'app_name' => 'Name of the app where the controller will be created.',
        '--webroot=<dir>' => [
            'optional' => true,
            'description' => 'Path to the public web root directory.\nDefault is the project directory.'
        ],
        '--bare' => [
            'optional' => true,
            'description' => 'Creates the bare minimun directory\nstructure. Hey Fabian! :)'
        ],
    ];

    public function run()
    {
        try {
            $app_name = $this->getAppName();
        } catch (InvalidArgumentException $e) {
            $this->console->fail($e->getMessage());
        }

        
    }
}
