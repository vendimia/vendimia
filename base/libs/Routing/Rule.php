<?php
namespace Vendimia\Routing;

use Vendimia\Path\FileSearch;
use Vendimia\Controller\ControllerAbstract;

/**
 * URL Routing rule definition.
 */
class Rule
{
    private $rule = [
        // Allowed request methods. Default any.
        'methods' => [],

        // Request hostname. Default any.
        'hostname' => null,

        // Should be an AJAX request? true = yes, false = no, null = don't care
        'ajax' => null,

        // Mapping data to match against url path, according 'type'
        'mapping_data' => [],

        // URL mapping method: simple, app, regexp, property (not a rule per se)
        'type' => 'simple',

        // Name of the route.
        'name' => null,

        // Target controller
        'target' => null,

        // Target string description
        'target_name' => '',

        // Target type: view, callable, class or legacy
        'target_type' => 'class',

        // Target application
        'target_app' => false,

        // Target default resource names
        'target_resources' => [],

        // True if this rule should be use in case URL is empty
        'default' => false,

        // Extra arguments to the controller/callable
        'args' => [],
    ];

    // Include()d rules
    private $included_rules = [];

    /**
     * Creates a HTTP GET rule
     */
    public static function get($path = null)
    {
        return self::fromMethodAndPath('GET', $path);
    }

    /**
     * Creates a HTTP POST rule
     */
    public static function post($path = null)
    {
        return self::fromMethodAndPath('POST', $path);
    }

    /**
     * Creates a HTTP PUT rule
     */
    public static function put($path = null)
    {
        return self::fromMethodAndPath('PUT', $path);
    }

    /**
     * Creates a HTTP PATCH rule
     */
    public static function patch($path = null)
    {
        return self::fromMethodAndPath('PATCH', $path);
    }

    /**
     * Creates a HTTP DELETE rule
     */
    public static function delete($path = null)
    {
        return self::fromMethodAndPath('DELETE', $path);
    }

    /**
     * Creates a generic HTTP rule
     */
    public static function path($path)
    {
        return self::fromMethodAndPath(null, $path);
    }

    /**
     * Creates a default rule
     */
    public static function default()
    {
        return (new self)->setDefault();
    }

    /**
     * Creates an empty rule
     */
    public static function new()
    {
        return new self;
    }

    /**
     * Sets the allowed HTTP methods for this rule
     */
    public function method(...$methods)
    {
        $this->rule['methods'] = $methods;
        return $this;
    }

    /**
     * Alias of self::method
     */
    public function methods(...$methods)
    {
        $this->method(...$methods);
        return $this;
    }

    /**
     * Matches an AJAX connection
     */
    public function ajax()
    {
        $this->rule['ajax'] = true;

        return $this;
    }

    /**
     * Sets this rule name
     */
    public function name($name)
    {
        $this->rule['name'] = $name;
        return $this;
    }

    /**
     * Sets a target for this rule
     */
    public function run($class, $method = 'default')
    {
        // Es una clase?
        if (is_subclass_of($class, ControllerAbstract::class)) {

            // Esto es para el recurso, solo usamos el nombre de la clase, sin
            // namespace.
            $class_name_parts = explode('\\', $class);
            $class_name = end($class_name_parts);

            $this->rule['target_type'] = 'class';
            $this->rule['target'] = [$class, $method];
            $this->rule['target_name'] = "{$class}::{$method}";
            $this->rule['target_resources'] = ["{$class_name}_{$method}", $method];

            // Si no hay definido una app para este target, usamos el 1er
            // namespace
            if (!$this->rule['target_app']) {
                $this->rule['target_app'] = explode('\\', $class)[0];
            }

            return $this;
        }

        // Es un callable?
        $callable_name = '';
        if (is_callable($class, false, $callable_name)) {
            $this->rule['target_type'] = 'callable';
            $this->rule['target'] = $class;
            $this->rule['target_name'] = 'callable:' . $callable_name;
            $this->rule['target_resources'] = [$callable_name];
            return $this;
        }

        // Por defecto, es un controller legacy. $class es la app
        $this->rule['target_type'] = 'legacy';
        $this->rule['target'] = [$class, $method];
        $this->rule['target_app'] = $class;
        $this->rule['target_name'] = $class . ":" . $method;
        $this->rule['target_resources'] = [$method];
        return $this;
    }

    /**
     * Renders a view
     */
    public function view($view_file)
    {
        $this->rule['target_type'] = 'view';
        $this->rule['target_name'] = $view_file;
        $this->rule['target'] = $view_file;
        return $this;
    }

    /**
     * Enforce the project rule set, disabling default app routing
     */
    public static function enforce()
    {
        return (new self)->setProperty('enforce_rules', true);
    }

    /**
     * Include another set of rules, with a precedenting path
     */
    public function include($include_data)
    {
        if (is_string($include_data)) {
            $include_data = require (new FileSearch($include_data))->get();
        }

        foreach ($include_data as $rule) {
            $this->included_rules = array_merge(
                $this->included_rules,
                $rule->getProcessedData($this)
            );
        }

        return $this;
    }

    /**
     * Sets this rule as a default, if the path is empty
     */
    public function setDefault()
    {
        $this->rule['default'] = true;

        return $this;
    }

    /**
     * Sets this rule to be a 'simple' URL map
     */
    public function setSimpleMapping($path)
    {
        $this->rule['type'] = 'simple';
        $this->rule['mapping_data'] = array_filter(explode('/', $path));
        return $this;
    }

    /**
     * Force an application name for this rule
     */
    public function setApplication($app)
    {
        $this->rule['target_app'] = $app;
        return $this;
    }

    /**
     * Changes this rule into a property, and sets its name/value
     */
    public function setProperty($property, $value)
    {
        $this->rule['type'] = 'property';

        if (!is_array($this->rule['mapping_data'])) {
            $this->rule['mapping_data'] = [];
        }

        $this->rule['mapping_data'][$property] = $value;

        return $this;
    }

    /**
     * Creates a rule with certain method and optional path
     */
    private static function fromMethodAndPath($method = null, $path = null)
    {
        $rule = new self;
        if ($method) {
            $rule->method($method);
        }

        if ($path) {
            $rule->setSimpleMapping($path);
        }

        return $rule;
    }

    /**
     * Returns the raw rule data
     */
    public function getData()
    {
        return $this->rule;
    }

    /**
     * Process and return this rule data.
     */
    public function getProcessedData(Rule $base_rule = null): array
    {
        if ($this->included_rules) {
            return $this->included_rules;
        }

        $rule = $this->rule;

        $rules = [];

        if ($base_rule) {
            $br = $base_rule->getData();

            // Las reglas 'default' se convierten en 'url' con la URL base
            // de la regla base.
            if (key_exists('default', $rule)) {
                $rule['default'] = false;
                $rule['type'] = 'simple';
            }


            // Algunos elementos los copiamos del base si no existen en el actual
            /*foreach (['hostname', 'ajax', 'type', 'name', 'target_type', 'target_name', 'target', 'callable', 'method'] as $e) {
                if (!key_exists($e, $rule) && key_exists($e, $br)) {
                    $rule[$e] = $br[$e];
                }
            }*/

            // El mapdata lo preponemos, si type == simple
            if ($rule['type'] == 'simple') {
                $rule['mapping_data'] = array_merge($br['mapping_data'], $rule['mapping_data']);
            }

            // Añadimos los argumentos, de haber
            //if (key_exists('args', $br)) {
            $rule['args'] = array_merge($rule['args'], $br['args']);
            //}

        }

        $rules[] = $rule;
        return $rules;
    }
}
