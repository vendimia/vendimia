<?php
namespace Vendimia\ORM;

use Vendimia\Database;

/**
 * Class to create more elaborated SQL comparisons.
 */
class Comparison
{
    private $fuction;
    private $params;
    private $connection;

    private $not = false;

    private $comparisons = [
        'ne' => '!=',
        'lt' => '<',
        'lte' => '<=',
        'gt' => '>',
        'gte' => '>=',
        'like' => 'LIKE'
    ];

    /**
     * Alias to LIKE 'data%'
     */
    private function comparisonStartsWith($params) {
        $sql = ' ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= 'LIKE ' . $this->connection->valueFromPHP($params[0] . '%');

        return $sql;
    }

    /**
     * Alias to LIKE '%data'
     */
    private function comparisonEndsWith($params) {
        $sql = ' ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= 'LIKE ' . $this->connection->valueFromPHP('%' . $params[0]);

        return $sql;
    }

    /**
     * Alias to LIKE '%data%'
     */
    private function comparisonContains($params) {
        $sql = ' ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= 'LIKE ' . $this->connection->valueFromPHP('%' . $params[0] . '%');

        return $sql;
    }

    /**
     * Used for comparing with booleans
     */
    private function comparisonIs($params)
    {

        $sql = ' IS ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= $this->connection->valueFromPHP($params[0]);

        return $sql;
    }

    /**
     * Use the IN SQL keyword
     */
    private function comparisonIn($params)
    {
        $sql = ' ';
        if ($this->not) {
            $sql .= 'NOT ';
        }

        $sql .= 'IN (' . join(', ', $this->connection->valueFromPHP($params)) . ')';

        return $sql;
    }

    /**
     * Use the BETWEEN SQL keyword
     */
    private function comparisonBetween($params)
    {
        $sql = '';
        if ($this->not) {
            $sql = ' NOT';
        }

        $sql .= ' BETWEEN ' . $this->connection->valueFromPHP($params[0]);
        $sql .= ' AND '. $this->connection->valueFromPHP($params[1]);

        return $sql;
    }

    /**
     *
     */


    public function __construct($function, $params, $not) {
        $this->function = $function;
        $this->params = $params;
        $this->not = $not;
        $this->connection = Database\Database::getConnector();
    }

    /**
     * Magic static method for create instances of this object
     */
    public static function __callStatic($method, $args) {
        // Estamos negando?
        $not = false;

        $method = strtolower($method);
        if (substr($method,0, 3) == 'not') {
            $not = true;
            $method = substr($method, 3);

            if ($method == '') {
                $not = false;
                $method = 'ne';
            }
        }

        return new self($method, $args, $not);
    }

    /**
     * Returns the escaped value
     */
    public function getValue(Database\ConnectorInterface $connector)
    {
        if (array_key_exists($this->function, $this->comparisons)) {
            return $this->comparisons[$this->function] .
                $this->connection->valueFromPHP($this->params[0]);
        } else {
            $method = 'comparison' . $this->function;
            if(method_exists($this, $method)) {
                return $this->$method($this->params);
            } else {
                throw new \BadMethodCallException("'{$this->function}' is not a valid comparison method.");
            }
        }
    }
}
