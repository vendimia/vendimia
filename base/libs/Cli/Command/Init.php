<?php
namespace Vendimia\Cli\Command;

use Vendimia\Cli\ProjectManager;
use Vendimia\Cli\TemplateManager;

class Init extends CommandAbstract
{
    public const DESCRIPTION = 'Creates the skeleton directory for a new Vendimia project.';
    public const CLI_ARGS = '<directory> [<options>]';
    public const HELP_OPTIONS = [
        'directory' => [
            'optional' => true,
            'description' => 'New project path. Default is the current\ndirectory.'
        ],
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
        $project = $this->project;
        $console = $this->console;

        $project_path = $this->args->pop();

        if (!$project_path) {
            $console->fail('Project path is missing. Try --help');
        }

        $public_path = $project_path;
        $use_webroot = (bool)$this->args->getOption('webroot');
        $bare = (bool)$this->args->getOption('bare');
        $static_path = 'static/';

        if ($use_webroot) {
            $public_path = $this->args->getOption('webroot');
            $static_path = $public_path . '/static/';
        }

        // Creamos las carpetas.
        if (!file_exists($project_path)) {
            mkdir($project_path);
        }
        if (!file_exists($public_path)) {
            mkdir($public_path);
        }

        // Obtenemos el real path de ambos
        $project_path = realpath($project_path);
        $public_path = realpath($public_path);

        if ($bare) {
            $msg = 'Creating new bare Vendimia project ';
        } else {
            $msg = 'Creating new Vendimia project ';
        }

        // Recreamos $project
        $project = new ProjectManager($project_path);

        // No puede existir un proyecto en el path
        if ($project->isValid()) {
            $console->fail("Project {:project {$project->getName()}} already exists on {:path {$project->getFullPath()}}");
        }


        $msg .="{:project {$project->getName()}} in {:path {$project->getBasePath()}}";
        $console->write($msg);

        if ($use_webroot) {
            $console->write("Notice: Using {:path $public_path} as public web directory.");
        }

        // Estructura de directorios
        $template = new TemplateManager($console);

        if (!$bare) {
            $template->makeTree($project_path, [
                'apps',
                'config',
                'tmp',
                'base' => [
                    'Model',
                    'orm',
                    'views' => [
                        'layouts'
                    ],
                    'Form',
                    'assets' => [
                        'css', 'js'
                    ],
                ]
            ]);
        }

        $template->makeTree($public_path, [
            'static' => [
                'assets' => [
                    'css', 'js',
                ],
            ]
        ]);

        // Configuración inicial
        $appid = hash('sha256', mt_rand());

        $template->setTemplate('config', compact(
            'project', 'use_webroot', 'public_path', 'appid', 'static_path'
        ));

        $template->build($project_path . '/config/settings.php');

        // Configuración para el ambiente de trabajo 'development'
        if (!$bare) {
            $template->setTemplate('config.development');
            $template->build($project_path . '/config/settings.development.php');
        }

        // Rutas por defecto
        if (!$bare) {
            $template->setTemplate('routes');
            $template->build($project_path . '/config/routes.php');
        }

        // El Index
        $template->setTemplate('index', compact('use_webroot', 'project_path'));
        $template->build($public_path . '/index.php');

        // Un .htaccess para Apache
        $template->setTemplate('htaccess', compact('use_webroot'));
        $template->build($public_path . '/.htaccess');

        // El layout por defecto
        $template->setTemplate('default-layout');
        $template->build($project_path . '/base/views/layouts/default.php');

        // Un .gitignore para GIT
        $template->setTemplate('gitignore');
        $template->build($project_path . '/.gitignore');

    }
}
