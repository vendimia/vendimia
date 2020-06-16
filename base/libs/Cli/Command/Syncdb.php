<?php
namespace Vendimia\Cli\Command;

use Vendimia;
use Vendimia\Cli\ProjectManager;
use Vendimia\Cli\TemplateManager;
use Vendimia\Database\Database;
use Vendimia\ORM\Configure\Configure;

class Syncdb extends CommandAbstract
{
    public const DESCRIPTION = 'Synchronize the database definition.';
    public const CLI_ARGS = '[<app>] [--drop]';
    public const HELP_OPTIONS = [
        'app' => [
            'optional' => true,
            'description' => 'Only process the tabledefs from this app'
        ],
        '--drop' => [
            'optional' => true,
            'description' => 'Allows destructive commands.'
        ],
    ];

    public function run()
    {
        $this->checkProject();

        $target_app = $this->args->get();

        if ($target_app) {
            $applist = [$target_app];
        } else {
            $applist = array_map(function($value) {
                return basename($value);
            }, glob($this->project->getFullPath() . '/apps/*', GLOB_ONLYDIR));
        }

        $defname = $this->args->get();
        if ($defname) {
            $defname .= '.php';
        } else {
            $defname = '*.php';
        }

        $manager = Database::getManager();

        foreach ($applist as $app) {
            $base_path = $this->project->getFullPath() . '/apps/' . $app;

            if (!is_dir($base_path)) {
                $this->console->warning("App '$app' doesn't exists.");
                continue;
            }

            $namespace = 'Entity';
            // Por cada app verificamos si existe la carpeta Entity o la
            // obsoleta 'orm'
            if (is_dir($base_path . '/orm')) {
                $namespace = 'orm';
            }

            $orm_files = glob($base_path . "/{$namespace}/" . $defname);

            if (!$orm_files && $defname != '*.php') {
                Console::warning("ORM entity file '$defname' not found.");
                continue;
            }

            $this->console->write("* Application {:app $app}");
            foreach ($orm_files as $orm_file) {
                $orm_name = basename($orm_file, '.php');
                $this->console->write("  - {:tabledef $orm_name}:");

                require $orm_file;
                $entity_name = "$app\\{$namespace}\\$orm_name";

                try {
                    foreach ($manager->sync($entity_name) as $action) {
                        // Tab!
                        echo '    ';
                        $this->console->fromStatus(...$action);
                    }
                } catch (\Vendimia\Database\QueryException $e) {
                    $this->console->fail('SQL command fail: ' . $e->getMessage());
                }
            }


        }
    }
}
