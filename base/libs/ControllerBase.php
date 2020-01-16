<?php
namespace Vendimia;

use Vendimia\Http\Request;
use Vendimia\Http\Response;

class ControllerBase
{
    protected $response;
    protected $request;
    protected $view_name = null;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Forces the usage of a specific view file
     */
    protected function setView($view_name)
    {
        $this->view_name = $view_name;
    }

    /**
     * Returns the view name
     */
    public function getView()
    {
        return $this->view_name;
    }
}
