<?php
namespace Vendimia\Cli\Command\NewSubcommand;

use Vendimia\Cli\Command\CommandAbstract;
use Vendimia\Cli\ProjectManager;
use Vendimia\Cli\TemplateManager;

use InvalidArgumentException;

class App extends CommandAbstract
{
    public const DESCRIPTION = 'Creates a new app.';
    public const CLI_ARGS = '<app_name>';
    public const HELP_OPTIONS = [
        'app_name' => 'Name of the new app.'
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

        // Existe la app?
        if ($this->appExists($app_name)) {
            $this->console->fail("{:app {$app_name}} app already exists on this project.");
        }

        // Creamos la app.
        $template = new TemplateManager($this->console);

        $template->makeTree($this->project->getFullPath(), ['apps/' . $app_name => [
            'Controller',
            'Model',
            'Entity',
            'Form',
            'assets' => [
                'css', 'js',
            ],
            'views' => [
                'layouts',
            ]
        ]]);

        $app_path = $this->project->getFullPath() . '/apps/' . $app_name;
        $project_name = $this->project->getName();

        // Creamos un controlador base.
        $template->setTemplate('default-controller', compact('app_name'));
        $template->build("{$app_path}/Controller/DefaultController.php");

        // Creamos una vista por defecto.
        $controller_name = 'default';
        $template->setTemplate('default-view', compact('project_name', 'app_name', 'controller_name'));
        $template->build("{$app_path}/views/default.php");

        // Y un fichero de rutas
        $template->setTemplate('routes', [
            'routing_comment' => 'Subroutes for "' . strtolower($app_name) . '/" URL path.',
            'namespace' => "namespace {$app_name}; ",
            'default_route' => 'Rule::default()->run(Controller\DefaultController::class),',
        ]);
        $template->build("{$app_path}/routes.php");


        // Añadimos una ruta a esta aplicación


    }
}
