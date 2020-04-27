<?php
namespace Vendimia\ORM;

use Vendimia\AsArrayInterface;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\ValueInterface;

/**
 * Relationship class with a database table
 */
abstract class Entity implements AsArrayInterface, ValueInterface
{
    use Configure, QueryManager;

    /** This entity fields */
    private $fields = [];

    /** The database field names, mapped to $fields */
    private $database_fields = [];

    /** Fields modified, used for save() method */
    private $modified_fields = [];

    /** True on newly created objects */
    private $is_new = true;

    /** True when a database query returns no result */
    private $is_empty = true;

    /** This FQCN **/
    private $base_class;

    /** Database table, copied from static property */
    private $db_table;

    /** Database connector, copied from static property */
    private $db_connector;

    /**
     * Sets up a new entity.
     *
     * @param array $fields Associative array with this entity fields
     *      value
     * @param boolean $from_entityset Used when fetching entities from an
     *      EntitySet.
     */
    public function __construct($fields = null, $from_entityset = false)
    {
        static::configure();

        $this->base_class = static::class;
        $this->db_table = static::getDatabaseTable();
        $this->db_connector = static::getDatabaseConnector();

        // Construimos los objetos de esta entidad
        foreach (static::$field_data as $field_name => $data) {
            $class = $data[0];
            if (!class_exists($class)) {
                throw new \InvalidArgumentException("Class '$class' for field '$field_name' doesn't exists.");
            }

            $field = $this->fields[$field_name] = new $class($this, $field_name, $data[1]);

            // Obtenemos el nombre de su campo de la base datos
            $db_field = $field->getDatabaseField();

            $this->database_fields[$db_field] = $field;

        }

        if ($fields instanceof AsArrayInterface) {
            $fields = $fields->asArray();
        }

        // si $from_entityset es true, entonces el valor viene de la base
        // de datos. Lo usamos como flag de setValue()
        if ($fields) {
            foreach($fields as $field => $value) {
                $this->setValue($field, $value, $from_entityset);
            }
        }

        $this->whereBuilder = new Where;
        $this->whereBuilder->setEntity($this);

        if ($from_entityset) {
            $this->is_new = false;
            $this->record_retrieved = true;
        }
    }

    /**
     * Creates a new entity, and immedieately saves it.
     */
    public static function create($fields = [])
    {
        return (new static($fields))->save();
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
     * Sets the fields value from an array or an AsArrayInterface object
     */
    public function setValues($data)
    {
        if ($data instanceof AsArrayInterface) {
            $data = $data->asArray();
        }
        foreach ($data as $field => $value) {
            $this->setValue($field, $value);
        }
        $this->is_empty = false;
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

        // Si no hay campos por guardar, salimos
        if (!$fields) {
            return $this;
        }

        // Obtenemos la información de todos los campos, formateados para la
        // base de datos
        $data = [];
        foreach ($fields as $field_name) {
            $field = $this->fields[$field_name];

            $field_name = $field->getProperty('database_field', $field_name);

            if (!$field->isDatabaseField()) {
                continue;
            }

            // Cada objeto devolverá su versión escapada
            $data[$field_name] = $field;
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
                $where = $this->db_connector->fieldValue(static::$primary_key, $id);

                $affected = $this->db_connector->update(
                    $this->db_table,
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
     * Updates this record
     */
    public function update($values)
    {
        $this->setValues($values);
        return $this->save();
    }

    /**
     * Delete this record
     */
    public function delete()
    {
        if ($this->isEmpty()) {
            return false;
        }

        if ($this->isNew()) {
            return false;
        }

        // Ejecutamos el beforeDelete()
        if (method_exists($this, 'beforeDelete')) {
            $this->beforeDelete();
        }

        // TODO: Verificar los registros relacionados, en especial OneToMany
        foreach ($this->fields as $field) {
            if ($field->isDependant()) {
                $ondelete = $field->getProperty('on_delete', 'cascade');

                if ($ondelete == 'cascade') {
                    // Borramos el registro
                    $field->getValue()->delete();
                } elseif ($ondelete == 'null') {
                    $field->getValue()->update([
                        $field->getProperty('foreing_key') => null
                    ]);
                }
            }
        }

        $where = static::$database_connector->fieldValue(
            static::$primary_key, $this->pk()
        );

        return static::$database_connector->delete(
            static::$database_table,
            $where
        );
    }

    /**
     * Sets a field value. Most used inside the same Entity object.
     *
     * @var string $field Field name.
     * @var mixed $value Value for this field.
     * @var boolean $from_database When true, the method Field::setValueFromDatabase()
     *      fromis used instead Field::setValue()
     */
    public function setValue($field, $value, $from_database = false)
    {
        $this->retrieveRecord();

        if ($from_database) {
            $setValueMethod = 'setValueFromDatabase';
        } else {
            $setValueMethod = 'setValue';
        }

        if (isset($this->fields[$field])) {
            $this->fields[$field]->$setValueMethod($value);
            $this->modified_fields[$field] = true;

            $this->is_empty = false;
        } elseif (isset($this->database_fields[$field])) {
            $this->database_fields[$field]->$setValueMethod($value);
            $this->modified_fields[$this->database_fields[$field]->getFieldName()] = true;

            $this->is_empty = false;
        } else {
            throw new \InvalidArgumentException("Field '$field' unknow in this entity");
        }
    }

    /**
    * Gets a field value. Most used inside the same Entity object.
     */
    public function getValue($field)
    {
        $this->retrieveRecord();

        if (isset($this->fields[$field])) {
            return $this->fields[$field]->getValue();
        } elseif (isset($this->database_fields[$field])) {
            return $this->database_fields[$field];
        } else {
            throw new \InvalidArgumentException("Trying to retrieve value from an unknow field '$field' in this entity");
        }

        return $this->fields[$field]->getValue();
    }

    /**
     * Sytactic sugar for setValue() and getValue().
     *
     * Most used inside same Entity methods.
     *
     * @param  string $field Field to retrieve or update
     * @param  mixed $value If not null, value to assign to this field.
     * @return mixed If $value is null, return $field value.
     */
    public function _($field, $value = null)
    {
        if (is_null($value))
        {
            return $this->getValue($field);
        } else {
            $this->setValue($field, $value);
        }
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
        $data = $this->db_connector->fetchOne($c);

        $this->record_retrieved = true;

        if (!$data) {
            $this->is_empty = true;
            return false;
        }

        foreach ($data as $fieldname => $val) {
            $field = $this->database_fields[$fieldname];
            $field->setValueFromDatabase($val);
        }
        $this->is_empty = false;
    }

    /**
     * Returns this entity base class
     *
     * @param bool $only_name returns the last part of the FQCN
     */
    public function getClass($only_name = false)
    {
        if ($only_name) {
            $class = '\\' . $this->base_class;
            $slashpos = strrpos($class, '\\');
            return substr($class, $slashpos + 1);
        }

        return $this->base_class;
    }

    /**
     * AsArrayInterface implementation
     */
    public function asArray($fields = null): array
    {
        $this->retrieveRecord();
        $result = [];

        if (is_null($fields)) {
            $fields = array_keys($this->fields);
        }
        foreach ($fields as $field) {
            $result[$field] = $this->fields[$field]->getValue();
        }

        return $result;
    }

    /**
     * ValueInterface implementation
     */
    public function getDatabaseValue(ConnectorInterface $connector)
    {
        // Es posible que el primary key no sea un entero...
        // mejor lo pasamos por ValueFromPHP para que lo escape
        // correctamente.
        return $connector->ValueFromPHP($this->pk());
    }

    function __toString()
    {
        return '<Entity of ' . get_class($this) . '(' . $this->pk() . ')>';
    }
}
