<?php
namespace Vendimia\Http;

use Vendimia;
use Vendimia\View;
use Vendimia\AsArrayInterface;
use Psr\Http\Message\Response as PsrResponse;

/**
 * Vendimia implementation of a HTTP Response based on PSR-7
 */
class Response extends PsrResponse
{
    protected $protocolVersion = '1.1';
    protected $statusCode = 200;
    protected $reasonPhrase = 'OK';

    public function __construct($body = null, $contentType = 'text/html') {
        if ($body) {
            $stream = new Stream('php://temp');
            $stream->write($body);

            $this->setBody($stream);

            $size = $this->getBody()->getSize();
            if ($size) {
                $this->setHeader('Content-Length', $size);
            }
        }

        $this->setHeader('Content-Type', $contentType);
    }

    /**
     * Creates a response from a string
     */
    static public function fromString($data, $content_type = 'text/plain')
    {
        $response = new static;

        $stream = new Stream('php://temp');
        $stream->write($data);

        $response->setBody($stream);

        $size = $stream->getSize();
        if ($size) {
            $response->setHeader('Content-Length', $size);
        }
        $response->setHeader('Content-Type', $contentType);

        return $response;
    }

    /**
     * Creates a response from a file
     */
    static public function fromFile($file, $mime = null)
    {
        if (!$mime ) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        }
        $size = filesize($file);
        $last_modified = filemtime($file);
        $stream = new Stream($file, 'r');

        return (new static)
            ->setBody($stream)
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', $size)
            ->setHeader('Last-Modified',  gmdate('r', $last_modified))
        ;
    }

    /**
     *  Creates a JSON response
     */
    public static function json($payload): self
    {
        if ($payload instanceof AsArrayInterface) {
            $payload = $payload->asArray();
        }
        if (!is_array($payload)) {
            throw new InvalidArgumentException('Argument must be an array or a object implementing Vendimia\\AsArrayInterface.');
        }
        return new self(json_encode($payload), 'application/json');
    }

    /**
     * Creates a Vendimia\Http\Response with a error code
     */
    public static function httpError($code, $reason) {
        return (new static)
            ->setStatus($code, $reason);
    }
    /**
     * Create a 404 response with a view, and sends it to the browser
     */
    public static function notFound($message = null, $extra = [])
    {
        if (is_null($message)) {
            $message = "<p>The page <tt>/".Vendimia::$request->getRequestTarget()."</tt> doesn't exists.</p>";
        }
        $extra = [
            'message' => $message,
            'url' => Vendimia::$request->getRequestTarget(),
        ];
        View::render('http_404', $extra)
            ->setStatus(404, 'Resource not found')
            ->send();
    }

    /**
     * Creates a 500 response with a view, and sends it to the browser
     */
    public static function serverError($reason, $code = 500)
    {
        View::render('http_500')
            ->setStatus($code, 'Server error')
            ->send();
    }

    /**
     * Creates a 302 Redirect
     */
    public static function redirect(...$url_parts)
    {
        $url = new Vendimia\Url(...$url_parts);

        $response = (new static)
            ->setStatus(302, 'Redirect')
            ->setHeader('Location', $url->get())
            ->send();
    }

    /**
     * Sends this response to the browser
     */
    public function send() {

        // Protocolo, version y codigo
        $pvc = 'HTTP/' . $this->getProtocolVersion() . ' ' . $this->getStatusCode() . ' ' . $this->getReasonPhrase();
        header($pvc);

        foreach ($this->getHeaders() as $name => $value) {
            $value = join(', ', $value);
            header("$name: $value");
        }

        // Si estamos en modo debug, logueamos algunas cosas
        /*if (Vendimia::$debug) {
            Vendimia\Logger::info(Vendimia::$request->getMethod());
        }*/

        // Si hay un cuerpo, lo enviamos
        $body = $this->getBody();

        if ($body) {
            $body->passthru();
        }
        exit;
    }

}
