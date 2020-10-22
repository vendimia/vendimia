<?php
/**
 * AJAX libraries
 */
namespace Vendimia;

use Vendimia;

class Ajax
{
    /** Status messages */
    const OK = 0;
    const REDIRECT = -1;
    const MESSAGE = -255;
    const EXCEPTION = -65535;

    /**
     * Verifies if this call is a valid AJAX call
     */
    public static function verify()
    {
        if (Vendimia::$request->getHeaderLine('X-Requested-With')
            == 'XMLHttpRequest'
            &&
            Vendimia::$request->getHeaderLine('X-Csrf-Token')
            == Vendimia::$csrf->getToken()){

            return true;
        }
        return false;
    }

    /**
     * Creates a JSON Request and sends to the browser
     */
    public static function send($code, $data = [])
    {
        $data['__CODE'] = $code;
        $json_data = json_encode($data);

        $response = new Http\Response($json_data, 'application/json');
        $response->send();
    }

    /**
     * Return a system messagve
     */
    public static function message($message, $title = '')
    {
        self::send(self::MESSAGE, [
            '__MESSAGE' => $message,
            '__TITLE' => $title,
        ]);
    }

    /**
     * Runs a callback only if the Request is a valid AJAX call.
     */
    public static function process($method = null, $callback = null)
    {
        // Chequeamos si $method es un callback. Si lo es, $method == "post"
        if (is_callable ($method)) {
            $callback = $method;
            $method = 'POST';
        }

        // Si el callbackn no es callable, fallamos.
        if (!is_callable($callback)) {
            throw new ExtendedException('Callable callback missing.');
        }

        Vendimia::$execution_type = 'ajax';

        if (self::verify() && Vendimia::$request->getMethod() == $method) {

            // We're in!
            $return = $callback();

            // Si retornamos solo un string, la colocamos en la variable
            // __MESSAGE

            if (is_string ($return)) {
                $return = ['__MESSAGE' => $return];
            }
            if ($return instanceof Vendimia\AsArrayInterface) {
                $return = $return->asArray();
            }

            // Si existe un elemento con indice '0' en return, lo
            // convertimos en un código de retorno, en el campo __CODE
            $return_code = self::OK;
            if (!empty ($return[0])) {
                $return_code = $return[0];
                unset ($return[0]);
            }

            self::send($return_code, $return);

            // Aquí finaliza la ejecución
            exit;
        }

    }
}
