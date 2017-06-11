<?php
namespace Vendimia\ORM;

use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\ValueInterface;

/**
 * Builds WHERE structures
 */
class Where
{
    private $base_string;
    private $args = [];
    private $connector;
    private $pk_field;

    /**
     * Sets the base data for WHERE
     */
    public function from(...$base)
    {
        if (count($base) == 1) {
            $this->base_string = $base[0];
        } else {
            $this->base_string = $base;
        }
        return $this;
    }

    /**
     * Sets the arguments for variable-replacement
     */
    public function setArguments(array $args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * Obtains data from the related Entity
     */
    public function setEntity(Entity $entity)
    {
        $this->connector = $entity->getDatabaseConnector();
        $this->pk_field = $this->connector->escapeIdentifier($entity->getPrimaryKeyField());
        return $this;
    }

    public function setConnector(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    public function setPrimaryKeyField($primary_key)
    {
        $this->pk_field = $this->connector->escapeIdentifier($primary_key);
    }

    /**
     * Returns one or many WHERE structures built from an array
     */
    private function buildFromArray(array $base)
    {
        $where = [];
        // Determinamos si es asociativo o no
        if ($base === array_values($base)) {
            // Si. Buscamos el primary key
            $w = $this->pk_field . ' IN (' . 
                join(', ', $this->connector->escape($base)) . ')';

            $where[] = ['AND', false, $w];
        } else {
            // No. Es un array asociativo
            $where = [];
            foreach ($this->base_string as $key => $value){
                $w = $this->connector->escapeIdentifier($key);

                if (is_array($value)) {
                    $w .= ' IN (' . join(', ', 
                        $this->connector->escape($value)) . ')';
                } elseif (is_object($value)) {
                    if ($value instanceof Comparison) {
                        $w .= $value->getValue($this->connector);
                    } elseif ($value instanceof ValueInterface) {
                        $w .= '=' . $value->getDatabaseValue($this->connector);
                    } else {
                        throw new \RuntimeException("'$key' value object (".get_class($value) . ") must be an instance of Vendimia\\ActiveRecord\\Comparison or implements interface Vendimia\\Database\\ValueInterface.");
                    }
                } else {
                    // Si no es un array, o un objeto, lo pasamos
                    $w .= '=' . $this->connector->valueFromPHP($value);
                }
                $where[] = ['AND', false, $w];
            }

            return $where;
        }
    }

    /**
     * Returns an array of WHEREs built from the base argument
     *
     * The query is built depending on the $args type:
     * - If it's an integer value, search for this primary key.
     * - If it's an array, and only have numeric indexes, the primary key
     *   will be search with an IN.
     * - If it's an associative array, multiple WHERE will be created with
     *   each key EQUALed to its value, joined with ANDs.
     * - 
     *
     * @return array Array of several [glue, not, where] arrays
     */
    public function build()
    {
        $where = [];

        if (is_array($this->base_string)) {
            $where = $this->buildFromArray($this->base_string);
        } elseif (is_numeric($this->base_string)) {
            $w = $this->pk_field . '=' . intval($this->base_string);
            $where[] = ['AND', false, $w];
        } elseif (is_object($this->base_string)) {
            if (!$this->base_string instanceof ValueInterface) {
                throw new \InvalidArgumentException("'" . get_class($this->base_string)  . "' must implements Vendimia\\Database\\ValueInterface to be used here.");
            }

            $where[] = ['AND', false, $this->base_string->getDatabaseValue($this->connector)];
        } elseif(is_string($this->base_string)) {
            // Asumimos que los strings ya son segmentos de un WHERE
            // y están escpados.
            $where[] = ['AND', false, $this->base_string];
        } else {
            // HMmm
            throw new \Exception("BUG! " . gettype($this->base_string) . ' type is not expected here!');
        }

        return $where;
    }

    /**
     * Replaces braces variables from a non-associative array
     */
    public function buildRaw(array $args)
    {
        $where = $this->base;

        while (($pos = strpos($where, '{}')) !== false) {
            $value = current($args);

            if ($value === false) {
                throw new \RuntimeException('Not enough parameters for raw WHERE.');
            }
            $value = $this->connector->escape($value);

            $where = substr($where, 0, $pos) . $value . 
                substr($where, $pos + 2);

            next($args);
        }

        return $where;
    }

    /**
     * Replaces braces variables from a associative array
     */
    public function buildRawAssoc(array $args)
    {
        $where = $this->base;
        $parsed_args = [];
        foreach ($args as $key => $arg) {
            $parsed_args['{' . $this->connector->escape($key, '') . '}'] = 
                $this->connector->escape($arg);
        }

        return strtr($where, $parsed_args);
    }
}