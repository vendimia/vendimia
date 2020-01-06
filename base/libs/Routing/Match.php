<?php
namespace Vendimia\Routing;

use Vendimia\ControllerBase;

/**
 * Matches a set of rules against a Request
 */
class Match
{
    private $data = [];

    public function __construct(Rules $rules)
    {
        $this->data = $rules;
    }

    /**
     * Compares two arrays for a match. Sets variables if needed
     *
     * @param array $rule Segments to expect. Variables can be definde
     *      preceding them with a colon.
     * @param array $target Segments to compare.
     * @param bool $forward Direction to traverse the array. False will be from last to first.
     */
    private function matchAndExtract(array $rule, array $target, $forward = true)
    {
        $match = false;
        $variables = [];

        if ($forward) {
            $extract_function = 'array_shift';
        } else {
            $extract_function = 'array_pop';
        }

        while (true) {
            $rule_seg = $extract_function($rule);
            $target_seg = $extract_function($target);

            // Si ambos son null, la ruta es válida
            if (is_null($rule_seg) && is_null($target_seg)) {
                $match = true;
                break;
            }

            // Si la regla es una variable, la grabamos
            if ($rule_seg{0} == ':') {
                $varname = substr($rule_seg, 1);
                if (is_null($target_seg)) {
                    break;
                }
                if ($varname == '*') {
                    continue;
                }
                $variables[$varname] = $target_seg;
            } else {
                if ($rule_seg != $target_seg) {
                    break;
                }
            }
        }

        return compact('match', 'variables');
    }

    /**
     * Replace variables with its values in array targets
     */
    private function replaceVariables($target, $variables)
    {
        if (is_array($target)) {

            // Armamos una lista de variables para usar strtr
            $tr = [];
            foreach($variables as $var => $val) {
                $tr['{' . $var . '}'] = $val;
            }

            $new_target = [];
            foreach ($target as $t) {
                if (!is_string($t)) {
                    continue;
                }
                $new_target[] = strtr($t, $tr);

            }
            $target = $new_target;
        }

        return $target;
    }

    /**
     * Match an URL using the "simple" method.
     */
    public function urlMatchSimple(array $mapdata, $url)
    {
        $urldata = array_filter(explode('/', $url));

        return $this->matchAndExtract($mapdata, $urldata);
    }

    /**
     * Match the first 1 or 2 components to [app, controller]
     */
    public function urlMatchApp($mapdata, $url)
    {
        $urldata = array_filter(explode('/', $url));

        $match = false;
        $variables = []; // Solo para retornar un array completo
        $target = [];

        $app = array_shift($urldata);
        if ($app == $mapdata) {
            // So simple
            $match = true;
            $controller = array_shift($urldata) ?? 'default';

            $target = [$app, $controller];
        }

        return compact('match', 'variables', 'target');
    }

    /**
     * Perform the rule match against the request
     */
    public function against($request): MatchedRule
    {
        // Primero, lo básico
        $httpmethod = $request->getMethod();
        $host = $request->hasHeader('Host') ? $request->getHeaderLine('Host') : false;
        $ajax = $request->hasHeader('X-Requested-With') ? $request->getHeaderLine('X-Requested-With') : false;
        $urlpath = $request->getRequestTarget();

        $matched_rule = false;

        // Si la URL es vacía, probamos el defecto.
        if (trim($urlpath, ' /') == "") {
            if ($this->data->default_route) {
                $rule = $this->data->default_route;

                $matched_rule = new MatchedRule([
                    'rule' => $rule,
                    'args' => $rule['args'],
                    'target' => $this->replaceVariables(
                        $rule['target'], $rule['args']
                    ),
                ]);

                foreach (['name', 'app', 'type', 'resources'] as $name) {
                    $field = 'target_' . $name;
                    $matched_rule->$field = $rule[$field];
                }


                // Reemplazamos las variables en el target
                $matched_rule->target_type = $rule['target_type'];
                $matched_rule->target = $this->replaceVariables(
                    $rule['target'], $matched_rule->args
                );
                return $matched_rule;

            } else {

                // Retornamos el bienvenido por defecto de Vendimia
                return new MatchedRule([
                    'target_name' => ['welcome', 'default', null],
                    'target_type' => 'view',
                    'target' => "welcome:default",
                ]);
            }
        }

        $matched_rule = new MatchedRule(['matched' => false]);
        foreach ($this->data->rules as $rule) {
            if (($rule['methods'] ?? false) && !in_array($httpmethod, $rule['methods'])) {
                continue;
            }
            if (($rule['ajax'] ?? false) && !$ajax) {
                continue;
            }
            if ($rule['hostname'] ?? false) {
                $matched = $this->matchAndExtract(
                    explode('.', $rule['hostname']),
                    explode('.', $host),
                    false
                );
                if (!$matched['match']) {
                    continue;
                }
            }

            // Usamos un método que corresponde al tipo de regla
            $method = 'urlMatch' . $rule['type'];

            $status = $this->$method($rule['mapping_data'], $urlpath);

            if ($status['match']) {
                // Perfecto. Actualizamos ciertas cosas dentro de la regla.
                $matched_rule = new MatchedRule([
                    'rule' => $rule,
                    'args' => $rule['args'] ?? [],
                ]);


                // Le añadimos las variables del target
                $matched_rule->args = array_merge(
                    $matched_rule->args, $status['variables']
                );

                foreach (['name', 'app', 'type'] as $name) {
                    $field = 'target_' . $name;
                    $matched_rule->$field = $rule[$field];
                }

                // Reemplazamos las variables en el target
                $matched_rule->target = $this->replaceVariables(
                    $rule['target'], $matched_rule->args
                );

                // Y en los target_resources, por si acaso
                $matched_rule->target_resources = $this->replaceVariables(
                    $rule['target_resources'], $matched_rule->args
                );

                //$rule['args'] = $args;

                /*if ($status['target'] ?? null) {
                    $rule['target'] = $status['target'];

                    // Sólo modificamos el target en una regla tipo 'app'. Y esa
                    // regla solo acepta [$app, $controller]
                    $rule['callable'] = false;
                } else {
                    // Solo puede haber dos tipos de target: array y string.
                    $rule['target'] = $this->replaceVariables($rule['target'], $args);
                }*/

                //$rule['urlpath'] = $urlpath;

                //$matched_rule = $rule;
                break;
            }
        }

        // Si no hay una regla, y enforce_rules == false, usamos los dos primeros
        // componentes de la URL para armar
        if (!$matched_rule->matched && !$this->data->properties['enforce_rules']) {
            $parts = array_filter(explode('/', $urlpath));

            $controller_class = "{$parts[0]}\Controller\DefaultController";

            if (is_subclass_of($controller_class, ControllerBase::class)) {
                // El nombre del método, que será usado para sacar otros ficheros
                // relacionados, como la vista o los ficheros CSS y JS, puede
                // colisionar con el mismo método en otros controladores.

                $method_name = $parts[1] ?? 'default';
                $matched_rule = new MatchedRule([
                    'target_name' => $parts[0] . '\\DefaultController::' . $method_name,
                    'target' => [$controller_class, $method_name],
                    'target_app' => $parts[0],
                    'target_type' => 'class',
                    'target_resources' => ['DefaultController_' . $method_name, $method_name],
                ]);
            } else {
                $controller_name = $parts[1] ?? 'default';
                $matched_rule = new MatchedRule([
                    'target_name' => $parts[0] . ':' . $controller_name,
                    'target' => [$parts[0], $controller_name, null],
                    'target_app' => $parts[0],
                    'target_type' => 'legacy',
                    'target_resources' => [$controller_name],
                ]);
            }

        }
        return $matched_rule;
    }

    public function getRoutingData()
    {
        return $this->data;
    }

}
