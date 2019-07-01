<?php
namespace Vendimia\Routing;

/**
 * URL Routing rule definition.
 */
class Rule
{
    private $default_rule = [
        // Allowed request methods. Default any.
        'method' => [],

        // Request hostname. Default any.
        'hostname' => null,

        // Should be an AJAX request? true = yes, false = no, null = don't care
        'ajax' => null,

        // Mapping data. Could be an URL, an app name
        'mapdata' => null,

        // URL mapping method: simple, app, regexp, property (not a rule per se)
        'type' => 'simple',

        // Name of the route.
        'name' => null,

        // Target controller/callable
        'target' => null,

        // True if 'taget' is a callable
        'callable' => false,

        // True if this rule should be use in case URL is empty
        'default' => false,

        // Extra arguments to the controller/callable
        'args' => [],
    ];

    private $rule = [
        'mapdata' => [],
    ];

    // Include()d rules
    private $included_rules = [];

    /**
     * Matches an URL using the 'simple' method
     */
    public static function url($url)
    {
        return (new self)->setMapping('simple', array_filter(explode('/', $url)));
    }

    /**
     * Matches an URL against a app name. All its controller are allowed
     */
    public static function app($app)
    {
        return (new self)->setMapping('app', $app);
    }

    /**
     * Syntax sugar for new Rule
     */
    public static function add()
    {
        return new self;
    }

    /**
     * Sets this rule as default, when URL is empty
     */
    public static function default()
    {
        return (new self)->setDefault();
    }

    /**
     * Enforce the project rule set, disabling default app routing
     */
    public static function enforce()
    {
        return (new self)->setProperty('enforce_rules', true);
    }

    /**
     * Matches HTTP GET method
     */
    public function get()
    {
        $this->method('GET');

        return $this;
    }

    /**
     * Matches HTTP POST method
     */
    public function post()
    {
        $this->method('POST');

        return $this;
    }

    /**
     * Matches HTTP PUT method
     */
    public function put()
    {
        $this->method('PUT');

        return $this;
    }

    /**
     * Matches HTTP DELETE method
     */
    public function delete()
    {
        $this->method('DELETE');

        return $this;
    }

    /**
     * Matches HTTP PATCH method
     */
    public function patch()
    {
        $this->method('PATCH');

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
     * Matches a hostname
     */
    public function hostname($hostname)
    {
        $this->rule['hostname'] = $hostname;

        return $this;
    }

    /**
     * Match the HTTP method
     */
    public function method(...$method)
    {
        $this->rule['method'] = $method;

        return $this;
    }

    /**
     * Adds extra arguments to V::$ARGS
     */
    public function args(array $args) {
        if (!key_exists('args', $this->rule)) {
            $this->rule['args'] = [];
        }

        $this->rule['args'] = array_merge($this->rule['args'], $args);

        return $this;
    }

    /**
     * Use a [app, controller] array as this rule target.
     */
    public function target($application, $controller = 'default', $args = [])
    {
        if (is_array($application)) {
            $target = $application;
        } else {
            $target = [$application, $controller, $args];
        }

        $this->rule['target'] = $target;
        $this->rule['callable'] = false;

        return $this;
    }

    /**
     * Use a callable as this rule target.
     */
     public function callable($callable)
    {
        $this->rule['target'] = $callable;
        $this->rule['callable'] = true;

        return $this;
    }

    /**
     * Include a new set of rules
     *
     * @param string|array $rules Rules to include. If is a string, it should
     *      be a Vendima Path for a rule file (it should return an array)
     */
    public function include($rules)
    {
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $this->included_rules = array_merge(
                    $this->included_rules,
                    $rule->getProcessedData($this)
                );
            }
        }

        return $this;
    }


    /**
     * Sets this rule mapping
     */
    public function setMapping($type, $mapdata)
    {
        $this->rule['type'] = $type;
        $this->rule['mapdata'] = $mapdata;

        return $this;
    }

    /**
     * Sets this rule as a defautl, if the URL is empty
     */
    public function setDefault()
    {
        $this->rule['default'] = true;

        return $this;
    }

    /**
     * Changes this rule into a property, and sets its name/value
     */
    public function setProperty($property, $value)
    {
        $this->rule['type'] = 'property';

        if (!key_exists('mapdata', $this->rule) || !is_array($this->rule['mapdata'])) {
            $this->rule['mapdata'] = [];
        }

        $this->rule['mapdata'][$property] = $value;

        return $this;
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
        $rules = [];
        if ($this->included_rules) {
            $rules = $this->included_rules;
        }

        $rule = $this->rule;
        if ($base_rule) {
            $br = $base_rule->getData();

            // Algunos elementos los copiamos del base si no existen en el actual
            foreach (['hostname', 'ajax', 'type', 'name', 'target', 'callable', 'method'] as $e) {
                if (!key_exists($e, $rule) && key_exists($e, $br)) {
                    $rule[$e] = $br[$e];
                }
            }

            // El mapdata lo añadimos, si type == simple
            if (in_array($br['type'], ['simple'])) {
                $rule['mapdata'] = array_merge($br['mapdata'], $rule['mapdata']);
            }

            if (key_exists('args', $br)) {
                $rule['args'] = array_merge($rule['args'], $br);
            }

        }

        $rules[] = $rule;
        return $rules;
    }
}
