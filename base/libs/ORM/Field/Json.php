<?php
namespace Vendimia\ORM\Field;

use Vendimia\ORM\Entity;
use Vendimia\Database\ConnectorInterface;
use Vendimia\Database\Field as DBField;

/**
 * Array field formatted as Json
 */
class Json extends FieldBase
{
    public function setValue($value)
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException ("Array field requires an array value, not " . gettype($value) . ".");
        }
        $this->value = $value;
    }

    public static function getDatabaseFieldType()
    {
        return DBField::JSON;
    }

    public function getDatabaseValue(ConnectorInterface $connector)
    {
        $value = json_encode($this->value);
        return $connector->valueFromPHP($value);
    }

    /**
     * Converts the JSON-formatted data from the db into an array
     */
    public function setValueFromDatabase($value)
    {
        $this->value = json_decode($value, true);
    }
}
