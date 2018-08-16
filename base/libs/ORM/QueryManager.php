<?php
namespace Vendimia\ORM;

/**
 * Methods to manipulate SQL queries
 */
trait QueryManager
{
    /** True when the data is already obtain from the db */
    private $record_retrieved = false;

    /** WHERE block buiider */
    private $whereBuilder;

    /** Query parameters for this object */
    private $query = [
        'fields' => [],
        'table' => null,
        'where' => [],
        'limit' => null,
        'offset' => null,
        'order' => [],
        'group' => [],
        'having' => []
    ];

    // Boolean join for the next WHERE
    protected $query_boolean_join = 'AND';

    // Boolean negation for the next WHERE
    protected $query_boolean_not = false;

    /**
     * Retrieves a single object
     */
    public static function get($where = null)
    {
        static::configureStatic();

        $object = new static;
        $object->setQueryMode($where);
        return $object;
    }

    /**
     * Retrieves several objects
     */
    public static function find($where = null)
    {
        static::configureStatic();
        $object = new EntitySet(
            static::class, 
            static::$database_table, 
            static::$database_connector
        );

        if ($where) {
            $object->where($where);
        }

        return $object;
    }

    /**
     * Creates a constrained EntitySet, for relationship
     */
    public static function newConstrained($constrains)
    {
        return static::find($constrains)->setConstrains($constrains);
    }


    /**
     * Alias de find()
     */
    public static function all()
    {
        return static::find();
    }

    /**
     * Adds the WHERE block to the query
     */
    private function addWhere($where)
    {
        $this->query['where'][] = [
            $this->query_boolean_join,
            $this->query_boolean_not,
            $where,
        ];

        // Reseteamos los boolean joins
        $this->query_boolean_join = 'AND';
        $this->query_boolean_not = false;
    }

    /**
     * Adds a WHERE block to the query
     */
    public function where($where)
     {
        $wb = $this->whereBuilder->from($where)->build();

        // Si solo hay una consulta, sacamos solo la condición.
        if (count($wb) == 1) {
            $wb = $wb[0][2];
        }

        $this->addWhere($wb);

        return $this;
    }

    /**
     * Adds a raw WHERE block.
     *
     * @param string $where Where string, with variable inside braces {} 
     * @param mixed $args Arguments to the where variables
     */
    public function rawWhere($where, ...$args)
    {
        if (isset($args[0]) && is_array($args[0])) {
            $where = $this->whereBuilder->from($where)->buildRawAssoc($args[0]);
        } else {
            $where = $this->whereBuilder->from($where)->buildRaw($args);
        }

        $this->addWhere($where);
        
        return $this;
    }

    /**
     * Syntax sugar for where 
     *
     * @see static::where()
     */ 
    public function and($where = null)
    {
        if ($where) {
            return $this->where($where);
        }
        return $this;
    }

    /**
     * Joins the next where() with an OR
     */
    public function or($where = null)
    {
        $this->query_boolean_join ='OR';
        if ($where) { 
            return $this->where($where);
        }
        return $this;
    }

    /**
     * Negates the next where()
     */
    public function not($where = null) {
        $this->query_boolean_not ='NOT';
        if ($where) {
            return $this->where($where);
        }
        return $this;
    }  

    /**
     * Sets the query order
     */
    public function order(...$params)
    {
        $this->query['order'] = array_merge($this->query['order'], $params);
        return $this;
    }

    /**
     * Adds a LIMIT clausule
     */
    public function limit($limit)
    {
        $this->query['limit'] = $limit;
        return $this;
    }

    /**
     * Adds an LIMIT offset portion
     */
    public function offset($offset)
    {
        $this->query['offset'] = $offset;
        return $this;
    }

    /**
     * Changes this entity into a query entity
     */
    protected function setQueryMode($where = null)
    {
        $where = $this->whereBuilder->from($where)->build();
        $this->query['where'] = $where;
        $this->is_new = false;
        $this->record_retrieved = false;
    }

    /**
     * Executes the query in the query entities
     */
    private function executeQuery()
    {
        static::configureStatic();

        if (!$this->query['table']) {
            $this->query['table'] = $this->db_table;
        }

        return $this->db_connector->buildAndExecuteQuery($this->query);
    }
    
}
