<?php
namespace Vendimia\View;

use Vendimia;

/**
 * Process a HTML/PHP view file.
 *
 * @author Oliver Etchebarne
 */
class Php implements \ArrayAccess
{
    /** View name, obtained from the base filename */
    public $name;

    /** View file */
    protected $file = null;

    /** Layout file */
    protected $layout_file = null;

    /** Array with this view variables */
    protected $variables = [];

    /** Pre-rendered blocks, used for the layouts */
    protected $blocks = [];

    /** Vendimia\Html object */
    protected $html;

    public function __construct($file = null, $variables = null)
    {
        if ($file) {
            $this->setFile($file);
        }
        if ($variables) {
            $this->addVariables($variables);
        }

        // Le inyectamos un objeto html
        $this->html = new Vendimia\Html;
    }

    /**
     * Converts a file name to a full file path.
     *
     * This will be overloaded by Vendimia for include the Vendimia\Path\FileSearch result;
     */
    protected function getCanonicalFilePath($file, $type = 'view',
        &$application = null)
    {
        return $file;
    }

    /**
     * Sets the view file.
     */
    public function setFile($file)
    {
        $file = $this->getCanonicalFilePath($file);

        if (!file_exists($file)) {
            throw new \RuntimeException("View file '{$this->file}' not found.");
        }

        $this->file = $file;
        $this->name = basename($this->file, '.php');
        return $this;
    }

    /**
     * Returns the view file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sets the layout file
     */
    public function setLayout($layout_file)
    {
        $this->layout_file = $this->getCanonicalFilePath($layout_file, 'layout');
        return $this;
    }

    /**
     * Returns the layout file
     */
    public function getLayout()
    {
        return $this->layout_file;
    }

    /**
     * Adds variables to this view
     */
    public function addVariables(array $variables)
    {
        $this->variables = array_merge($this->variables, $variables);
    }

    /**
     * Renders this view applying the layout.
     *
     * @return string
     */
    public function renderToString()
    {

        // Esta vista la guardamos en el bloque por defecto
        $this->blocks['__MAIN_BLOCK'] = $this->processFile(
            $this->file,
            $this->variables
        );

        // Ahora procesamos el layout, si hay.
        if ($this->layout_file) {
            $result = $this->processFile($this->layout_file, $this->variables);
        } else {
            // Si no, devolvemos el main block
            $result = $this->blocks['__MAIN_BLOCK'];
        }

        // Retornamos el resultado
        return $result;
    }

    /**
     * Process a view file. Returns a string with the processed HTML
     *
     * @return string
     */
    public function processFile($view_file, array $variables = [])
    {
        if (!file_exists($view_file)) {
            throw new \RuntimeException("View file '$view_file' doesn't exist.");
        }

        ob_start();

        extract($variables);
        include $view_file;
        $result = ob_get_contents();

        ob_end_clean();

        return $result;
    }

    /**
     * Returns a previously rendered block
     */
    public function content($block = null)
    {
        if (is_null($block)) {
            $block = '__MAIN_BLOCK';
        }

        // No hay problema al usar echo, esta función sólo debe ejecutarse
        // dentro del ob_start() de self::process()
        echo $this->blocks[$block];
    }

    /**
     * Inserts a view file insider this view.
     */
    public function insert($view_file, array $variables = [])
    {
        extract($variables);
        include $this->getCanonicalFilePath($view_file);
    }

    /**
     * Returns an URL relative to the static URL
     */
    public function staticUrl($url) {
        return Vendimia\Url::parse(Vendimia::$settings['static_url'], $url);
    }

    public function offsetExists($offset)
    {
        return key_exists($offset, $this->variables);
    }
    public function offsetGet($offset)
    {
        if (key_exists($offset, $this->variables)) {
            return $this->variables[$offset];
        } else {
            return null;
        }
    }
    public function offsetSet($offset, $value)
    {
        $this->variables[$offset] = $value;
    }
    public function offsetUnset($offset) {
        unset ($this->variables[$offset]);
    }
}
