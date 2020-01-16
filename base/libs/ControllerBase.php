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
}
