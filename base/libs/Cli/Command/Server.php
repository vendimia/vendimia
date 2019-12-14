<?php
namespace Vendimia\Cli\Command;

use Vendimia;

class Server extends CommandAbstract
{
    public const DESCRIPTION = 'Starts a simple web server on the project.';
    public const CLI_ARGS = '[host[:port]]';
    public const HELP_OPTIONS = [
        'host[:port]' => [
            'optional' => true,
            'description' => 'Starts the server on the specified host and port. Default is localhost:8888'
        ],
    ];

    public function run()
    {
        // No puede existir un proyecto en el path
        if ($this->project->notValid()) {
            $this->console->fail("Vendimia project not found or not specified.");
        }

        $host = 'localhost';
        $port = '8888';

        $serverinfo = $this->args->pop();

        if ($serverinfo) {
            $parts = explode(':', $serverinfo);

            if (isset($parts[0])) {
                $host = $parts[0];
            }
            if (isset($parts[1])) {
                $port = $parts[1];
            }
        }

        $root_path = $this->project->getFullPath();
        $index_path = Vendimia\Path::join($root_path, 'index.php');

        $this->console->write("Launching development server for {:project {$this->project->getName()}} project on {white http://{$host}:{$port}/}");

        chdir($root_path);
        $cmdline = "php -S {$host}:{$port} index.php";
        passthru($cmdline);
    }

}
