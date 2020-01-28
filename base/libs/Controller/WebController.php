<?php
namespace Vendimia\Controller;

use Vendimia;
use Vendimia\Http\Request;
use Vendimia\Http\Response;

class WebController extends ControllerAbstract
{
    private $view_name = null;

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
    protected function getView()
    {
        return $this->view_name;
    }

    public function parseReturn($return): Response
    {
        $view_variables = [];
        if (is_array($return)) {
            $view_variables = $return;
        }

        $view = new Vendimia\View();
        $view->setApplication($this->routing_rule->target_app);

        if ($this->view_name) {
            $view->setFile($this->view_name);
        } else {
            foreach($this->routing_rule->target_resources as $view_file) {
                try {
                    $view->setFile($view_file);
                } catch (Vendimia\Exception $e) {
                    continue;
                }
            }
        }

        // Si no hay fichero, fallamos
        if (!$view->getFile())  {
            throw new Vendimia\Exception(
                "View file cannot be found for controller {$this->routing_rule->target_name}",
            [
                'Rule matched' => $this->routing_rule->rule,
                'Searched view names' => $this->routing_rule->target_resources,
            ]);
        }

        // Si no tiene un layout por defecto, buscamos uno.
        if (!$view->getLayout()) {
            $view->setLayout('default');
        }


        // Insertamos la vista, con las variables, dentro del response.
        $view->addVariables($view_variables);

        $body = new Vendimia\Http\Stream('php://temp');
        $body->write($view->renderToString());
        $this->response->setBody($body);
        $size = $this->response->getBody()->getSize();
        if ($size) {
            $this->response->setHeader('Content-Length', $size);
        }

        return $this->response;
    }
}
