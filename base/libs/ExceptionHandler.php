<?php
namespace Vendimia;

use Vendimia;

class ExceptionHandler
{
    /**
     * Selects the proper method according the execution type
     */
    public static function handler($exception)
    {
        // Logueamos el evento.
        $line = get_class($exception);

        $message = $exception->getMessage();
        if ($message) {
            $line .= ': ' . $message;
        }

        $line .=  ' on ' . $exception->getFile() .
            ':' . $exception->getLine();

        Vendimia::$logger->alert($line);


        // El nombre del método es el tipo de ejecución
        $type = strtolower(Vendimia::$execution_type);
        $callable = ['self', "handle$type"];

        call_user_func($callable, $exception);
    }

    /**
     * Register the exception handler.
     */
    public static function register()
    {
        // No registramos en CLI, por que eso se registra antes, 
        // y en otro momento
        if (Vendimia::$execution_type != 'cli') {
            set_exception_handler ([__CLASS__, 'handler']);        
        }
    }

    /**
     * Shows a view with the exception informacion.
     */
    public static function handleWeb($exception)
    {

        // Borramos cualquier salida, solo mostramos la excepción
        if (ob_get_length()) { 
            ob_clean();
        }

        if (Vendimia::$debug) {
            $view = View::render('vendimia_exception_handler', [
                'class' => get_class($exception),
                'E' => $exception,
            ]);
        } else {
            $view = View::render('http_500')
                ->setStatus(500, 'Error while executing Vendimia project');
        }
        $view->send();
    }

    /**
     * Returns an Ajax::EXCEPTION
     */
    public static function handleAjax($exception)
    {
        Ajax::send(Ajax::EXCEPTION, [
            'name' => get_class($exception),
            'message' => $exception->getMessage(), 
        ]);
    }
}
