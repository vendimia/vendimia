<?php
namespace Vendimia\Route;

use Vendimia\Http\Request;

/**
 * URL Route definition class.
 */
class Rule
{
    /** Default parameters for a rule */
    private static $base_rule = [
        // Request method. Default any.
        'method' => null,

        // Request hostname. Default any.
        'hostname' => null,

        // URL mapping method
        'type' => 'simple',
        
        // Name of the route.
        'name' => null,

        // URL map, or array with property => value
        'data' => null,     

        // Target controller/callable
        'target' => null,

        // Es un callable?
        'is_callable' => false,

        // Extra arguments to the controller/callable
        'args' => [],
    ];

    /** Actual route definition */
    private $rule = [];

    /** true when $rule contains an array of routes */
    private $nested = false;

    public function __construct() 
    {
        $this->rule = self::$base_rule;
    }

    /**
     * Matches a GET method. This is the default
     */
    public function get()
    {
        return $this->method('GET');
    }

    /**
     * Matches a POST method.
     */
    public function post()
    {
        return $this->method('POST');
    }

    /**
     * Matches a PUT method.
     */
    public function put()
    {
        return $this->method('PUT');
    }

    /**
     * Matches a DELETE method.
     */
    public function delete()
    {
        return $this->method('DELETE');
    }

    /**
     * Matches a HEAD method.
     */
    public function head()
    {
        return $this->method('HEAD');
    }

    /**
     * Matches a arbitrary HTTP method
     */
    public function method($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Matches a simple URL mapping
     */
    public function url($map)
    {
        // El IF es para usar esta función desde el shortcut con Route::add()
        if ($map) {
            $this->type = 'url';
            $this->data = $map;
        }
        return $this;
    }

    /**
     * Matches a simple application
     */
    public function app(...$app)
    {
        $this->type = 'app';
        if (!is_array($this->data)) {
            $this->data = [];
        }
        $this->data = array_merge($this->data, $app);
        return $this;
    }

    /**
     * Matches a regular expression
     */
    public function regexp($regexp)
    {

    }

    /**
     * Sets a default rule
     */
    public function default($rule)
    {
        $this->type = 'property';
        $this->data = ['default_target' => $rule];
        return $this;
    }

    /**
     * Disable the default route-from-url process, and force route usage.
     */
    public function force()
    {
        $this->type = 'property';
        $this->data = ['force_routes' => true];
        return $this;
    }

    /**
     * Adds a controller target.
     */
    public function target($target)
    {
        $this->target = $target;
        $this->is_callable = false;
        return $this;
    }

    /**
     * Adds a callable target
     */
    public function callable($callable)
    {
        $this->target = $callable;
        $this->is_callable = true;
        return $this;
    }

    /**
     * Adds arguments for the target.
     */
    public function args(...$args) 
    {
        // Si el primer elemento es un array, lo usamos
        if (is_array($args[0])) {
            $args = $args[0];
        }
        $this->args = array_merge($this->args, $args);
        return $this;
    }

    /**
     * Returns this route array.
     */
    public function asArray()
    {
        return $this->rule;
    }

    /**
     * Match a simple application rule
     */
    public function matchApp($url)
    {
        $parts = explode('/', $url);
        $app = array_shift($parts);

        foreach ($this->data as $rule_app) {
            if ($app == $rule_app) {
                $controller = array_shift ($parts);
            
                // Si no hay un elemento extra para el controlador, es default
                if (!$controller) {
                    $controller = 'default';
                }

                return $this->found($parts, [
                    'try_default_controller' => true,
                    'target' => [$app, $controller]
                ]);
            }
        }
    }

    /**
     * Match an URL rule. Variables are defined with a "$" sufix.
     */
    public function matchUrl($url)
    {
        // Dividimos la ruta y la URL por sus slashes. Eliminamos las partes
        // vacías
        $route_parts = array_filter(explode('/', $this->data));
        $url_parts = array_filter(explode('/', $url));

        $variables = [];
        $route_valid = false;

        while (true){
            $route_part = array_shift($route_parts);
            $url_part = array_shift($url_parts);

            // Si ya no hay partes de la URL ni de la ruta, tudo bem.
            if (is_null($route_part) && is_null($url_part)) {
                $route_valid = true;
                break;
            }

            // Si es una variable
            if ($route_part{0} == '$') {
                if (is_null($url_part)) {
                    break;
                }
                $variables[substr($route_part, 1)] = $url_part;
            } else {

                // No es una variable. Deben ser iguales
                if ($route_part != $url_part) {
                    break;
                }
            }
        }

        if ($route_valid) {
            // Agregamos las variables que hemos encontrado
            $args = array_merge($this->args, $variables);

            return $this->found($args);
        }

    }

    /**
     * Match this rule agains a request
     */
    public function match(Request $request)
    {
        if ($this->method) {
            if ($request->getMethod() != $this->method) {
                return false;
            }
        }
        if ($this->hostname && $request->hasHeader('host')) {
            if ($request->getHeader('hostname') != $this->host) {
                return false;
            }
        }

        // Ejecutamos la función de validación
        $function = [$this, 'match' . $this->type];

        $url = $request->getRequestTarget();
        $result = $function($url);

        if ($result) {
            // Encontramos un match. Añadimos los args del request
            $result['args'] = array_merge($this->args, 
                $result['args']);

            return $result;
        }


        // Not valid
        return false;
    }

    /**
     * Returns this rule target, replacing args if needed
     */
    private function processTarget(array $args = []) 
    {
        if (is_array($this->target)) {
            $callable = [];
            foreach ($this->target as $c) {
                // Solo trabajamos con strings
                if (!is_string($c)) {
                    $callable[] = $c;
                    continue;
                }
                $value = $c;
                if ($c{0} == '$') {
                    $varname = substr($c, 1);

                    if (key_exists($varname, $args)) {
                        $value = $args[$varname];
                    }
                }

                $callable[] = $value;
            }

            return $callable;
        }

        return $this->target;
    }

    /**
     * Builds a 'found' array for match returning 
     */
    private function found($args, $extra = []) 
    {
        $data = [
            'status' => 'found',
            'target' => $this->processTarget($args),
            'alt_target' => null,       // Debe ser sobreescrito
            'is_callable' => $this->is_callable,
            'args' => $args,
        ];

        return array_merge($data, $extra);
    }

    public function __get($variable)
    {
        if (key_exists($variable, $this->rule)) {
            return $this->rule[$variable];
        } else {
            return null;    
        }
    }

    public function __set($variable, $value)
    {
        $this->rule[$variable] = $value;
    }

}
