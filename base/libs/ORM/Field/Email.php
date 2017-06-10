<?php
namespace Vendimia\ORM\Field;

/**
 * Email field. It's a char with default length of 254.
 */
class Email extends Char
{
    public static function validateProperties($field_name, array $properties)
    {
        $length_in_index_0 = key_exists(0, $properties);
        $length_as_property = key_exists('length', $properties);

        if (!$length_in_index_0 && !$length_as_property) {
            $length = 254;
        }

        if ($length_in_index_0) {
            $properties['length'] = $properties[0];
        }

        if (!key_exists('length', $properties)) {
            $properties['length'] = $length;
        }

        return $properties;
    }
}