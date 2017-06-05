<?php
namespace Vendimia\ORM;

use Vendimia\Database;

/**
 * Sets the base parameters for this class and objects
 */
trait Configure
{

    /** Is this class configured? */
    protected static $configuredStatic = false;

    /** Field objects and properties */
    protected static $field_data = [];

    /** Is this object configured? */
    protected $configured = false;

    /** Configurable table name for this object. Defaults to 
     *  {self::$namespace}_{self::$name}
     */
    protected static $database_table;

    /** Configurable database connector name */
    protected static $database_connection = 'default';
    
    /** Database connector */
    protected static $database_connector;

    /** Primary key field */
    protected static $primary_key = 'id';

    /**
     * Sets default parameters for the class
     */
    private static function configureStatic()
    {
        if (static::$configuredStatic) {
            return;
        }

        if (!static::$database_table) {
            // Armamos el nombre de tabla, si no está definida
            $parts = explode('\\', strtolower(static::class));

            // Hack: removemos el segmento 'orm' del nombre
            $parts = array_filter($parts, function($v) {
                if ($v !== 'orm') {
                    return true;
                }
            });
            $database_table = join('_', $parts);
            static::$database_table = &$database_table;
        }

        $database_connector = Database\Database::getConnector(static::$database_connection);
        static::$database_connector = &$database_connector;

        
        $configuredStatic = true;
        static::$configuredStatic = &$configuredStatic;

    }

    /**
     * Creates the field objects
     */
    private function configure()
    {
        if ($this->configured) {
            return;
        }
        static::configureStatic();

        static::$field_data = (new Configure\Configure($this))->getFields();

        // Si no existe el campo definido en static::$primary_key, 
        // Lo creamos.
        if (!key_exists(static::$primary_key, static::$field_data)) {
            static::$field_data[static::$primary_key] = [
                Field\Integer::class,
                [
                    'primary_key' => true,
                ]
            ];
        }

        $this->configured = true;
    }

    /**
     * Returns this class connector
     */
    public function getDatabaseConnector()
    {
        return static::$database_connector;
    }

    /**
     * Return the primary key field
     */
    public function getPrimaryKeyField()
    {
        return static::$primary_key;
    }
}
