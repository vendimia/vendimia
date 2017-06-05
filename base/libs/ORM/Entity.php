<?php
namespace Vendimia\ORM;

use Vendimia\AsArrayInterface;

/**
 * Relationship class with a database table
 */
abstract class Entity implements AsArrayInterface
{
    use Configure, QueryManager;

    /** This entity fields */
    private $fields = [];

    /** Optional foreign key fields*/
    private $key_fields = [];

    /** Fields modified, used for save() method */
    private $modified_fields = [];

    /** True on newly created objects */
    private $is_new = false;

    /** True when a database query returns no result */
    private $is_empty = true;

    /**
     * Sets up a new entity.
     */
    public function __construct($fields = null)
    {
        static::configure();

        // Construimos los objetos de esta entidad
        foreach (static::$field_data as $field => $data) {
            $class = $data[0];
            if (!class_exists($class)) {
                throw new \InvalidArgumentException("Class '$class' for field '$field' doesn't exists.");
            }
            $this->fields[$field] = new $class ($this, $field, $data[1]);
            if ($kf = $this->fields[$field]->getKeyField()) {
                $this->key_fields[$kf] = $this->fields[$field];
            }
        }

        if ($field instanceof AsArrayInterface) {
            $fields = $fields->asArray();
        }

        if ($fields) {
            foreach($fields as $field => $value) {
                $this->$field = $value;
            }
        }

        $this->whereBuilder = new Where;
        $this->whereBuilder->setEntity($this);
    }

    /**
     * Returns if this entity is new
     */
    public function isNew()
    {
        return $this->is_new;
    }
    
    /**
     * Syntactic sugar for !isNew()
     */
    public function notNew() 
    {
        return !$this->is_new;
    }

    /**
     * Returns if this entity is empty
     */
    public function isEmpty()
    {
        $this->retrieveRecord();
        return $this->is_empty;
    }

    /**
     * Syntatic sugar for !isEmpty()
     */
    public function notEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * Sets or returns the primary key value
     */
    public function pk($value = null)
    {
        if (is_null($value)) {
            return $this->getValue(static::$primary_key);
        } else {
            return $this->setValue(static::$primary_key, $value);
        }
    }

    /**
     * Sets a field value
     */
    public function setValue($field, $value)
    {
        if (!key_exists($field, $this->fields)) {
            throw new \InvalidArgumentException("Field '$field' unknow in this entity");
        }

        $this->fields[$field]->setValue($value);
        $this->modified_fields[$field] = true;
    }

    /**
     * Gets a field value
     */
    public function getValue($field)
    {
        $this->retrieveRecord();

        if (!key_exists($field, $this->fields)) {
            throw new \InvalidArgumentException("Field '$field' unknow in this entity");
        }

        return $this->fields[$field]->getValue();
    }

    /**
     * Magic setter
     */
    public function __set($field, $value)
    {
        $this->setValue($field, $value);
    }

    /**
     * Magic getter
     */
    public function __get($field)
    {
        return $this->getValue($field);
    }

    /**
     * Retrieves a record from the database
     */
    private function retrieveRecord()
    {
        if ($this->record_retrieved) {
            return false;
        }

        if ($this->is_new) {
            return false;
        }

        $c = $this->executeQuery();
        $data = static::$database_connector->fetchOne($c);

        $this->record_retrieved = true;

        if (!$data) {
            $this->is_empty = true;
            return false;
        } else {
            $this->is_empty = false;
        }

        foreach ($data as $field => $val) {

            // Nos fijamos si $field es una llava
            if (key_exists($field, $this->key_fields)) {
                // Okey
            } else {
                $this->setValue($field, $val);
            }
        }
    }    

    /**
     * AsArrayInterface implementation
     */
    public function AsArray($fields = null)
    {
        // $this->retrieveRecord();
        if (is_null($fields))
        {

        }
    }
}
