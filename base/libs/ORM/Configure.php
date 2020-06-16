<?php
namespace Vendimia\ORM;

use Vendimia\Database;

/**
 * Sets the base parameters for this class and objects
 */
trait Configure
{
    /** Is this class configured? */
    //protected static $configuredStatic = false;

    /** Field objects and properties */
    protected static $field_data = [];

    /** Is this object configured? */
    protected $configured = false;

    /** Primary key field */
    protected static $primary_key = 'id';

    /** FQCN Builder */
    protected static $fqcn;

    /**
     * Creates the field objects
     */
    private function configure()
    {
        if ($this->configured) {
            return;
        }

        $ep = new Parser\EntityParser($this);
        static::$field_data = $ep->getFields();
        static::$fqcn = $ep->getFQCNBuilder();

        $this->configured = true;
    }

    /**
     * Returns this class connector
     */
    public static function getDatabaseConnector()
    {
        return Database\Database::getConnector(
            static::$database_connection ?? 'default'
        );
    }

    /**
      * Returns this class database table name
      */
    public static function getDatabaseTable()
    {
        if (isset(static::$database_table)) {
            return static::$database_table;
        }

        $parts = array_filter(explode('\\', strtolower(static::class)),
            function($e) { return $e !== 'orm' && $e !== 'entity'; });

        return join('_', $parts);
    }

    /**
     * Returns the primary key field
     */
    public static function getPrimaryKeyField()
    {
        return static::$primary_key;
    }

    /**
     * Returns the FQCN builder
     */
    public static function getFQCNBuilder()
    {
        return static::$fqcn;
    }
}
