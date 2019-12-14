<?php
namespace Vendimia\Cli\Command\NewSubcommand;

use Vendimia\Cli\Command\CommandAbstract;
use Vendimia\Cli\ProjectManager;
use Vendimia\Cli\TemplateManager;

use InvalidArgumentException;

class View extends CommandAbstract
{
    public const DESCRIPTION = 'Creates a view template.';
    public const CLI_ARGS = '[for] <app_name> <view_name>';
    public const HELP_OPTIONS = [
        'app_name' => 'Name of the app where the view file will be created.'
    ];

    public function run()
    {
        try {
            $app_name = $this->getAppName();
        } catch (InvalidArgumentException $e) {
            $this->console->fail($e->getMessage());
        }

        // Existe la app?
        if (!$this->appExists($app_name)) {
            $this->console->fail("{:app {$app_name}} app doesn't exists on this project.");
        }
        var_dump($app_name);


    }

}
