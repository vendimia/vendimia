<?php
namespace Vendimia\Controller;

use Vendimia\Http\Request;
use Vendimia\Http\Response;
use Vendimia\Routing\MatchedRule;

use InvalidArgumentException;
use LogicException;

use ReflectionMethod;
use ReflectionException;

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
     * Executes a method. Injects argument as needed. Calls extended self::parseReturn()
     */
    public function executeMethod($method_name): Response
    {
        // Si requiere argumentos, se los pasamos
        $args = [];

        $refl = new ReflectionMethod(static::class, $method_name);
        foreach ($refl->getParameters() as $param) {

            // Verificamos si queremos inyectar una clase
            try {
                $requested_class = $param->getClass();
            } catch(ReflectionException $e) {
                // Relanzamos la excepción, pero más bonita.
                throw new LogicException("Parameter '{$param->getName()}' in " . static::class . "::{$method_name} requests an object from a non-existent class.");
            }

            if ($requested_class) {
                // ok, lo inyectamos.
                $class_name = $requested_class->getName();
                $args[] = new $class_name();
                continue;
            }



            $value = null;
            if (key_exists($param->getName(), $this->args)) {
                $value = $this->args[$param->getName()];
            }

            // Si el parámetro no es opcional, y no hay un valor, fallamos
            if (!$param->isOptional() && !$value) {
                throw new InvalidArgumentException(
                    static::class . "::{$method_name} requires a non-existent route variable '{$param->getName()}'."
                );
            }

            $args[] = $value;
        }

        $return = $this->$method_name(...$args);

        if ($return instanceof Response) {
            return $return;
        }

        return $this->parseReturn($return);
    }

    /**
     * Processes a method return value
     */
    public abstract function parseReturn($return): Response;
}
