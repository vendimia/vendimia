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
        'app_name' => 'Name of the new controller class.',
    ];

    public function run()
    {
        if (!$this->project->isValid()) {
            $this->console->fail("Vendimia project not found.");
        }

        try {
            $app_name = $this->getAppName();
        } catch (InvalidArgumentException $e) {
            $this->console->fail($e->getMessage());
        }

        $entity_name = $this->args->pop();

        if (!$this->checkValidPHPLabel($entity_name)) {
            $this->console->fail('Invalid entity name.');
        }

        $target_file = $this->project->getAppPath($app_name) . '/Entity/' . $entity_name . '.php';

    }
}
