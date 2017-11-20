<?php
namespace Vendimia\ORM;

/**
 * Aggregate functions
 */
trait AggregateFunctions
{
    /**
     * Executes a single SQL aggregate function on a field
     *
     * @param $string $function SQL function
     * @param $string $field Table field to apply the function
     * @return mixed SQL function result
     */
    private function executeSQLFunction($function, $field)
    {
        // Ejecutamos la función en un objeto distinto
        $target = clone $this;

        $target->query['fields'] = [
            "{$function}({$field})" => "__vendimia_function_result"
        ];
        $c = $target->executeQuery();

        $data = $this->db_connector->fetchOne($c);

        return $data['__vendimia_function_result'];
    }

    /**
     * Returns the registry count
     */
    public function count()
    {
        return intval($this->executeSQLFunction('count', '*'));
    }

    /**
     * Return the largest value for this field in this EntitySet
     * @param string $field Field to apply the MAX() function
     * @return mixed result of the MAX() function
     */
    public function max($field)
    {
        return $this->executeSQLFunction('max', $field);
    }

}
