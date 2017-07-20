<?php
namespace Vendimia\ORM;

use Vendimia\AsArrayInterface;

/**
 * Collection of entities
 */
class EntitySet implements \Iterator
{
    use QueryManager;

    /** This FQCN **/
    private $base_class;

    /** Database table, copied from static property */
    private $db_table;

    /** Database connector, copied from static property */
    private $db_connector;

    /** Cursor returned by the database connector */
    private $cursor;

    /** Last retrieved entity */
    private $last_entity;

    /** Iterator index */
    private $iterator_index;

    /** Is this set already retrieved? */
    private $set_retrieved = false;

    /** Constrains for new records */
    private $constrains = [];

    /**
     * Dummy function. Is not neede here, but trait QueryManager needs it
     */
    private static function configureStatic()
    {
    }

    /**
     * Sets up a new Query EntitySet 
     */
    public function __construct($base_class, $db_table, $db_connector)
    {
        $this->base_class = $base_class;
        $this->db_table = $db_table;
        $this->db_connector = $db_connector;
        $this->whereBuilder = new Where;

        $this->whereBuilder->setConnector($db_connector);
        $this->whereBuilder->setPrimaryKeyField($base_class::getPrimaryKeyField());
    }

    /**
     * Sets the constrains to this EntitySet
     */
    public function setConstrains($constrains)
    {
        $this->constrains = $constrains;
        return $this;
    }

    /**
     * Executes the query
     */
    public function retrieveSet($force = false)
    {
        if ($this->set_retrieved && !$force) {
            return false;
        }
        $this->cursor = $this->executeQuery();
        $this->set_retrieved = true;
    }

    /**
     * Returns the next record in this set
     */
    public function fetch() 
    {
        $this->retrieveSet();

        $data = $this->db_connector->fetchOne($this->cursor);
        if (!$data) {
            $this->is_empty = true;
            return null;
        }

        $class = $this->base_class;

        return new $class($data, true); // $not_new = true
    }

    /**
     * Returns the registry count
     */
    public function count()
    {
        return intval($this->executeSQLFunction('count', '*'));
    }

    /**
     * Adds a Entity to this set, if this is a constrained set
     */
    public function add(Entity $entity)
    {
        if (!$this->constrains) {
            throw new \InvalidArgumentException("Can't add elements to this non-constrained EntitySet");
        }
        $entity->update($this->constrains);
    }

    /**
     * Deletes all the Entities in this recordset
     */
    public function delete()
    {
        $where = $this->whereBuilder->from($this->constrains)->build();

        return $this->db_connector->delete($this->db_table, $where);
    }

    /**
     * Executes a single SQL function on a field
     */
    private function executeSQLFunction($function, $field)
    {
        // Ejecutamos la función en un objeto distinto
        $target = clone $this;
        
        $target->query['fields'] = ["{$function}({$field})" => "__vendimia_function_result"];
        $c = $target->executeQuery();

        $data = $this->db_connector->fetchOne($c);

        return $data['__vendimia_function_result'];
    }


    /**
     * {@inherit}
     */
    public function current()
    {
        return $this->last_entity;
    }

    /**
     * {@inherit}
     */
    public function key()
    {
        return $this->iterator_index;
    }

    /**
     * {@inherit}
     */
    public function next()
    {

    }

    /**
     * {@inherit}
     */
    public function rewind()
    {
        $this->retrieveSet(true);
        $this->iterator_index = 0;        
    }

    /**
     * {@inherit}
     */
    public function valid()
    {
        $entity = $this->fetch();

        if (is_null($entity)) {
            $this->is_empty = true;
            return false;
        }

        $this->last_entity = $entity;
        $this->iterator_index++;
        return true;
    }

    function __toString()
    {
        return '<EntitySet of ' . $this->base_class . ')>';
    }

}