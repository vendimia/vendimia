<?php
namespace Vendimia\Controller;

use Vendimia\Http\Request;
use Vendimia\Http\Response;
use Vendimia\Http\Stream;

/**
 *
 */
class JsonController extends ControllerAbstract
{
    public function parseReturn($return): Response
    {
        if (!is_array($return)) {
            throw new \UnexpectedValueException('A JsonController method must return an Vendimia\Http\Response object, or an array. Got ' . gettype($return) . ' instead.');
        }

        // Añadimos el json al response
        $body = new Stream('php://temp');
        $body->write(json_encode($return));

        $this->response->setBody($body);
        $this->response->setHeader('Content-Type', 'application/json');
        $size = $this->response->getBody()->getSize();
        if ($size) {
           $this->response->setHeader('Content-Length', $size);
        }

        return $this->response;
    }
}
