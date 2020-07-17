<?php
namespace Vendimia\Cli;

use Vendimia;
use Vendimia\Path;

use Exception;

/**
 * Class to manage CLI file templates, and some other small file operations
 */
class TemplateManager
{
    private $console;
    private $template_name = null;
    private $template_filename = null;
    private $args = [];

    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    public function setTemplate($template_name, $args = [])
    {
        // La plantilla debe existir
        $template_filename = Vendimia\BASE_PATH . '/bin/FileTemplates/' . $template_name . '.php';
        if (!file_exists($template_filename)) {
            throw new Exception("CLI template file not found ($template_filename).");
        }
        $this->template_name = $template_name;
        $this->template_filename = $template_filename;
        $this->args = $args;
    }

    /**
     * Process and save the template.
     */
    public function build($target_filename, $add_php_tag = true)
    {
        ob_start();
        extract($this->args);
        include $this->template_filename;
        $data = ob_get_clean();

        if ($add_php_tag) {
            $data = '<?php ' . $data;
        }

        file_put_contents($target_filename, $data);

        $this->console->fromStatus('CREATE', $target_filename);

    }

    /**
     * Wrapper to Vendima\Path::makeDir. It sends the mkdir status to the console
     */
    public function makeTree($base_path, array $tree)
    {
        foreach (Path::makeTree($base_path, $tree) as $status) {
            $this->console->fromStatus('MKDIR', $status[1], $status[0]);
        }
    }
}
