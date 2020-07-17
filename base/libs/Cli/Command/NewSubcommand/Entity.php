<?php
namespace Vendimia\Cli\Command\NewSubcommand;

use Vendimia\Cli\Command\CommandAbstract;
use Vendimia\Cli\ProjectManager;
use Vendimia\Cli\TemplateManager;

use InvalidArgumentException;

class Entity extends CommandAbstract
{
    public const DESCRIPTION = 'Creates a new database entity.';
    public const CLI_ARGS = '[for] <app_name> <entity_name>';
    public const HELP_OPTIONS = [
        'for' => [
            'optional' => true,
            'description' => 'Syntactic-sugar word'
        ],
        'app_name' => 'Name of the app where the entity will be created.',
        'entity_name' => 'Name of the new database entity.',
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

        if (file_exists($target_file)) {
            $this->console->fromStatus('CREATE', $target_file, 'omit');
            return;
        }

        $template = new TemplateManager($this->console);

        $template->setTemplate('entity', compact('app_name', 'entity_name'));
        $template->build($target_file);

        $this->console->fromStatus('CREATE', $target_file);

    }
}
