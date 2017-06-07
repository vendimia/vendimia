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
    protected $fieldName;

    /** Field properties */
    protected $properties;

    /** Parent entity */
    protected $entity;

    /** Does this field return a database value? */
    protected $is_database_field = true;


    /**
     * Default constructor
     * 
     * @param Entity $entity Parent entity
     * @param string $fieldName This field name
     * @param array $properties This field properties
     */
    public function __construct(Entity $entity, $fieldName, array $properties)
    {
        $this->fieldName = $fieldName;
        $this->properties = $properties;
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
     * Returns the database field where the foreign key is
     */
    public function getKeyField()
    {
        return null;
    }

    /**
     * Sets the foreing key value, if needed
     */
    public function setKeyValue($value)
    {
        // Por defecto, no hace nada.
    }

    /**
     * Returns whether this field is mapped to a database field
     */
    public function isDatabaseField()
    {
        return $this->is_database_field;
    }

    /**
     * Validates the properties for this Field
     *
     * @param array $properties Properties to analyze
     * @return mixed Array of validated properties, or false on error
     */
    public static function validateProperties(array $properties)
    {
        return $properties;
    }

    /**
     * Returns the Database\Field const value for this field
     */
    abstract public static function getDatabaseFieldType();

    /**
     * ValueInterface implementation
     */
    abstract public function getDatabaseValue(ConnectorInterface $connector);
}