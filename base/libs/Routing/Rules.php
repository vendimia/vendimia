<?php
namespace Vendimia\Routing;

/**
 * Class for process and  manage the project rules
 */
class Rules
{
    public $rules;
    public $properties;
    public $default_route;

    public function __construct(array $raw_rules)
    {
        //$this->rules = $rules;
        $rules = [];
        $default_route = [];
        $properties = [
            'enforce_rules' => false,
        ];

        foreach ($raw_rules as $raw_rule) {
            foreach ($raw_rule->getProcessedData() as $rule) {

                if ($rule['default']) {
                    $default_route = $rule;
                    continue;
                }
                if ($rule['type'] == 'property') {
                    $properties = array_merge($properties, $rule['mapping_data']);
                    continue;
                }
                $rules[] = $rule;
            }
        }

        //$this->data = (object)compact('rules', 'default_route', 'properties');
        $this->rules = $rules;
        $this->properties = $properties;
        $this->default_route = $default_route;


    }

    /**
     * Returns an array with human-readable rules list
     */
    public function getHumanList(): array
    {
        $return = [];

        foreach($this->rules as $rule) {

            if ($rule['type'] == 'simple') {
                $path = '/' . join('/', $rule['mapping_data']);
            } else {
                $path = '?' . $rule['type'];
            }

            if (!$rule['methods']) {
                $rule['methods'] = ['ANY'];
            }
            $line = [
                join(',' , $rule['methods']),
                $path,
                '->',
                '(' . $rule['target_type'] . ')' . $rule['target_name'],
            ];
            $return[] = join(' ', $line);
        }

        foreach ($this->properties as $variable => $value) {
            $return[] = "PROPERTY {$variable} = " . json_encode($value);
        }

        if ($this->default_route) {
            $return[] = "DEFAULT " . $this->default_route['target_name'];
        }

        return $return;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
