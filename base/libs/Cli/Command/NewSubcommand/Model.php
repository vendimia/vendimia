<?php
namespace Vendimia\Cli\Command\NewSubcommand;

use Vendimia\Cli\Command\CommandAbstract;
use Vendimia\Cli\ProjectManager;
use Vendimia\Cli\TemplateManager;

use InvalidArgumentException;

class Model extends CommandAbstract
{
    public const DESCRIPTION = 'Creates a new base model for this app.';
    public const CLI_ARGS = '<model_name>';
    public const HELP_OPTIONS = [
        'model_name' => 'Name of the new model.'
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

        $model_name = $this->args->pop();

        if (!$this->checkValidPHPLabel($model_name)) {
            $this->console->fail('Invalid model name.');
        }

        $target_file = $this->project->getAppPath($app_name) . '/Model/' . $model_name . '.php';

        if (file_exists($target_file)) {
            $this->console->fromStatus('CREATE', $target_file, 'omit');
            return;
        }

        $template = new TemplateManager($this->console);

        $template->setTemplate('model', compact('app_name', 'model_name'));
        $template->build($target_file);

        $this->console->fromStatus('CREATE', $target_file);

    }
}
