<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ValueInterface;
use Vendimia\Database\ConnectorInterface;

abstract class FieldBase implements ValueInterface
{
    /** This field value */
    protected $value = null;

    /** Field name */
    protected $field_name;

    /** Field properties */
    protected $properties;

    /** Parent entity */
    protected $entity;

    /** Does this field return a database value? */
    protected static $is_database_field = true;

    /**
     * Default constructor
     *
     * @param Entity $entity Parent entity
     * @param string $fieldName This field name
     * @param array $properties This field properties
     */
    public function __construct(Entity $entity, $field_name, array $properties)
    {
        $this->field_name = $field_name;
        $this->properties = static::validateProperties($entity, $field_name, $properties, $entity);
        $this->entity = $entity;
    }

    /**
     *  Default setter
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Default getter
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns a property
     */
    public function getProperty($property_name, $default_value = null)
    {
        if (isset($this->properties[$property_name])) {
            return $this->properties[$property_name];
        } else {
            return $default_value;
        }
    }

    /**
     * Returns this field name
     */
    public function getFieldName()
    {
        return $this->field_name;
    }

    /**
     * Returns the database field name
     */
    public function getDatabaseField()
    {
        if (isset($this->properties['database_field'])) {
            return $this->properties['database_field'];
        } else {
            return $this->field_name;
        }
    }

    /**
     * Returns whether this field is mapped to a database field
     */
    public static function isDatabaseField()
    {
        return static::$is_database_field;
    }

    /**
     * Validates the properties for this Field
     *
     * @param string $field_name Field name used by this class instance
     * @param array $properties Properties to analyze
     * @param Entity $Entity Optional related entity
     * @return mixed Array of validated properties, or false on error
     */
    public static function validateProperties(Entity $entity = null, $field_name, array $properties)
    {
        return $properties;
    }

    /**
     * ValueInterface default implementation.
     */
    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return $connector->valueFromPHP($this->value);
    }

    /**
     * Sets this field value with data from the database.
     */
    public function setValueFromDatabase($value)
    {
        $this->setValue($value);
    }

    /**
     * Returns true when this field value depends on the owner.
     *
     * Used on deleting, to update the dependant Entities on this field value,
     * like on an OneToMany field.
     *
     */
    public function isDependant()
    {
        return false;
    }

    /**
     * Returns the Database\Field const value for this field
     */
    abstract public static function getDatabaseFieldType();
}
