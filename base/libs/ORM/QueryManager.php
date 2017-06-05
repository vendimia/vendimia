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
     * Joins the next where() with an OR
     */
    public function or()
    {
        $this->query_boolean_join ='OR';
        return $this;
    }

    /**
     * Negates the next where()
     */
    public function not() {
        $this->query_boolean_not ='NOT';
        return $this;
    }    

    /**
     * Changes this entity into a query entity
     */
    protected function setQueryMode($where)
    {
        $where = $this->whereBuilder->from($where)->build();
        $this->query['where'] = $where;
        $this->is_new = false;
        $this->record_retrieved = false;
    }

    /**
     * Saves this entity to the database
     */
    public function save()
    {
        // Ejecutamos un beforeSave();
        if (method_exists($this, 'beforeSave')) {
            $this->beforeSave();
        }

        // Solo grabamos los campos modificados, si existe
        if ($this->modified_fields) {
            $fields = array_keys($this->modified_fields);
        } else {
            $fields = array_keys($this->fields);
        }

        // Si no hay campos por guardar, salismos
        if (!$fields) {
            return $this;
        }

        // Obtenemos la información de todos los campos, formateados para la
        // base de datos
        $data = [];
        foreach ($fields as $field) {
            if (!$this->fields[$field]->isDatabaseField()) {
                continue;
            }
            // Cada objeto devolverá su versión escapada
            $data[$field] = $this->fields[$field];
        }


        // Primero intentamos actualizar el registro. Si el registro no
        // existe, insertamos
        $id = $this->pk();
        if (is_null($id)) {
            $action = 'INSERT';
        } else {
            $action = 'UPDATE';
        }

        while (true) {
            if ($action == 'INSERT') {
                $id = static::$database_connector->insert(
                    static::$database_table,
                    $data
                );
                $this->pk($id);

                break;
            } else {
                $where = static::$database_connector->fieldValue(static::$primary_key, $id);

                $affected = static::$database_connector->update(
                    static::$database_table,
                    $data,
                    $where
                );

                if ($affected) {
                    // Hubo una actualización;
                    break;
                } else {
                    // Insertamos
                    $action = 'INSERT';
                }
            }
        }
        return $this;
    }

    /**
     * Executes the query in the query entities
     */
    private function executeQuery()
    {
        static::configureStatic();

        if (!$this->query['table']) {
            $this->query['table'] = static::$database_table;
        }

        return static::$database_connector->buildAndExecuteQuery($this->query);
    }
}