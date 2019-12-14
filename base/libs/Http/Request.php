<?php
namespace Vendimia\Http;

use Vendimia;
use Vendimia\Collection;
use Psr\Http\Message\ServerRequest;

class Request extends ServerRequest
{
    /** Vendimia\Collection object to GET values */
    public $get;

    /** Vendimia\Collection object to POST values */
    public $post;

    /**
     * Returns if this request method is POST.
     */
    public function isGet()
    {
        return $this->getMethod() == "GET";
    }

    /**
     * Syntax sugar for !$this->isGet()
     */
    public function notGet()
    {
        return $this->getMethod() != "GET";
    }

    /**
     * Returns if this request method is POST.
     */
    public function isPost()
    {
        return $this->getMethod() == "POST";
    }

    /**
     * Syntax sugar for !$this->isPost()
     */
    public function notPost()
    {
        return $this->getMethod() != "POST";
    }

    /**
     * Creates a Request using the PHP super globals $_GET, et al.
     *
     * @return self
     */
    static public function fromSuperGlobals() {

        // Determinamos el request target

        // Si hay q, entonces obtenemos la info de ahi.
        if ( isset ( $_GET['q'] ) ) {
            $request_target = trim( $_GET['q'], '/');
            unset ($_GET['q']);

        // Si no hay ahi, buscamos el PATH_INFO
        } elseif ( isset ( $_SERVER['PATH_INFO']) ) {
            $request_target = trim( $_SERVER['PATH_INFO'], '/');

        // Si no hay ahi, REQUEST_URI
        } elseif ( isset ( $_SERVER['REQUEST_URI']) ) {
            $request_target = trim( $_SERVER['REQUEST_URI'], '/');
        }

        // Si no hay ahi...
        // ...no tengo idea qué hacer...

        $request = (new static)
            -> setMethod($_SERVER['REQUEST_METHOD'])
            -> setQueryParams($_GET)
            -> setParsedBody($_POST)
            -> setRequestTarget($request_target)
        ;

        // Colocamos sólo las cabeceras reales, en su formato original
        foreach($_SERVER as $var => $val) {
            if (substr($var, 0, 5) == 'HTTP_') {
                $var = strtr(strtolower(substr($var, 5)), '_', '-');
                $request -> setHeader($var, $val);
            }
        }

        $request->get = new Collection($_GET);
        $request->post = new Collection($_POST);

        return $request;
    }
}
