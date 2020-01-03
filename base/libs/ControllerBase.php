<?php
namespace Vendimia;

use Vendimia\Http\Request;
use Vendimia\Http\Response;

class ControllerBase
{
    private $response;
    private $request;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
