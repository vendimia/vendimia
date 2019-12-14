<?php
namespace Vendimia\Routing;

/**
 * Matches a set of rules against a Request
 */
class Match
{
    private $data = [];

    public function __construct(array $rawrules)
    {
        // TODO: Caching

        $rules = [];
        $default_route = [];
        $properties = [
            'enforce_rules' => false,
        ];

        foreach ($rawrules as $rawrule) {
            foreach ($rawrule->getProcessedData() as $rule) {

                if (key_exists('default', $rule) && $rule['default']) {
                    $default_route = $rule;
                    continue;
                }
                if ($rule['type'] == 'property') {
                    $properties = array_merge($properties, $rule['mapdata']);
                    continue;
                }
                $rules[] = $rule;
            }
        }

        $this->data = (object)compact('rules', 'default_route', 'properties');
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
            $nt = [];
            foreach ($target as $t) {
                if (!is_string($t)) {
                    continue;
                }
                if ($t{0} == ':') {
                    $var = substr($t, 1);
                    $nt[] = $variables[$var] ?? null;
                } else {
                    $nt[] = $t;
                }
            }

            $target = $nt;
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
    public function against($request)
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
                return $this->data->default_route;
            } else {

                // Retornamos el bienvenido por defecto de Vendimia
                return [
                    'urlpath' => $urlpath,
                    'target' => ['welcome', 'default'], // Obsoleto, pero necesario.
                    'fallback_target' => ['welcome', 'default'],
                    'callable' => false,
                ];
            }
        }

        $matched_rule = [];

        foreach ($this->data->rules as $rule) {
            if (($rule['method'] ?? false) && !in_array($httpmethod, $rule['method'])) {
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

            $status = $this->$method($rule['mapdata'], $urlpath);

            if ($status['match']) {
                // Perfecto. Actualizamos ciertas cosas dentro de la regla.

                $args = [];
                if ($rule['args'] ?? false) {
                    $args = array_merge($args, $rule['args']);
                }
                // Le añadimos las variables del target
                $args = array_merge($args, $status['variables']);

                $rule['args'] = $args;

                if ($status['target'] ?? null) {
                    $rule['target'] = $status['target'];

                    // Sólo modificamos el target en una regla tipo 'app'. Y esa
                    // regla solo acepta [$app, $controller]
                    $rule['callable'] = false;
                } else {
                    // Solo puede haber dos tipos de target: array y string.
                    $rule['target'] = $this->replaceVariables($rule['target'], $args);
                }
                $rule['urlpath'] = $urlpath;

                $matched_rule = $rule;
                break;
            }
        }


        // Si no hay una regla, y no forzamos las reglas, usamos los 2 1ros
        // componentes de la URL como [app, controller]
        if (!$matched_rule && !$this->data->properties['enforce_rules']) {
            $parts = array_filter(explode('/', $urlpath));

            $controller_class = ["{$parts[0]}\Controller", $parts[1] ?? 'default'];

            $matched_rule = [
                'urlpath' => $urlpath,
                'target' => $controller_class,
                'application' => $parts[0],
                'fallback_target' => [$parts[0], $parts[1] ?? 'default'],
                'callable' => false,
                'args' => [],
            ];
        }

        return $matched_rule;
    }

    public function getRoutingData()
    {
        return $this->data;
    }

}
