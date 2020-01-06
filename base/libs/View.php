<?php
namespace Vendimia;

use Vendimia;
use Vendimia\Http;

/**
 * Decorator-like class to add Vendimia processing classes to the View
 */
class View extends View\Php
{
    private $app;

    protected function getCanonicalFilePath($file, $type = 'view',
        &$application = null)
    {

        if ($type == 'view') {
            $type = 'views';
        } elseif ($type == 'layout') {
            $type = 'views/layouts';
        }

        if ($file instanceof Path\FileSearch) {
            $path = $file;
        } else {
            $path = new Path\FileSearch($file, $type);
        }
        $path->setApplication($this->app);

        if ($path->found()) {
            return $path->get();
        } else {
            throw new Vendimia\Exception("View file '$file' not found.", [
                'Searched path' => $path->searched_paths,
            ]);
        }
    }

    /**
     * Set application for resource searching
     */
    public function setApplication($app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * Render this view applying the layout. Returns a Vendimia\Http\Response
     */
    public function renderToResponse()
    {
        return new Http\Response($this->renderToString());
    }

    /**
     * Overloaded constructor for creating a new security token
     */
    public function __construct($file = null, $variables = null)
    {
        (new Csrf)->generateToken();
        parent::__construct($file, $variables);
    }

    /**
     * Shortcut for rendering a view
     *
     * @return object Vendimia\Http\Response
     */
    public static function render($view_file, array $variables = [])
    {
        $view = new static($view_file, $variables);
        $content = $view->renderToString();
        return new Http\Response($content);
    }

    /** Base name of the view */
    //public $name = '';

    /**
     * Set the view file.
     */
    /*public function file($view)
    {
        $file = new Path\FileSearch($view, 'views');
        if ($file->notFound()) {
            throw new NotFoundException("View file '$view' not found.", [
                'Searched paths' => $file->searched_paths
            ]);
        }
        $this->file = $file->get();
        $this->name = $view;
    } */

    /**
     * Set the layout file
     */
    /*public function layout($layout)
    {
        // Ya que el layout se busca varias veces, $layout puede ser
        // un objeto Vendimia\FilePath. Lo reusamos
        if ($layout instanceof Path\FileSearch) {
            $file = $layout;
            $layout = $file->file;
        } else {
            $file = new Path\FileSearch($layout, 'views/layouts');
        }

        if ($file->notFound()) {
            throw new NotFoundException("Layout '$layout' not found.", [
                'Searched paths' => $file->searched_paths
            ]);
        }

        $this->layoutFile = $file->get();
    }*/

    /*public static function render($view_file, array $variables = [])
    {
        $file = new Path\FileSearch($view_file, 'views');
        if ($file->notFound())  {
            throw new NotFoundException("View '$view_file' not found.", [
                'Searched paths' => $file->searched_paths
            ]);
        }
        $view_file = $file->get();

        return parent::render($view_file, $variables);
    }*/
}
