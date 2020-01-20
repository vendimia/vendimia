<?php
namespace Vendimia\Controller;

use Vendimia\Http\Request;
use Vendimia\Http\Response;
use Vendimia\Routing\MatchedRule;

abstract class ControllerAbstract
{
    protected $response;
    protected $request;
    protected $routing_rule = null;
    protected $args = [];

    public function __construct(
        Request $request,
        Response $response,
        MatchedRule $routing_rule
    )
    {
        $this->request = $request;
        $this->response = $response;
        $this->routing_rule = $routing_rule;
        $this->args = $routing_rule->args;
    }

    /**
     * Executes a method, processing its input to create a Response
     */
    public abstract function executeMethod($method_output): Response;
}
