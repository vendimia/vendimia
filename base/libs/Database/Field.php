<?php
namespace Vendimia\Database;

/**
 * Vendimia database field types representation 
 */
class Field
{
    // Integers
    const Bool = 1;
    const Boolean = 1;
    
    const Byte = 2;
    const SmallInt = 3;
    const Integer = 4;
    const BigInt = 5;
    
    // Decimals
    const Float = 6;
    const Double = 7;
    const Decimal = 8;

    // Strings
    const Char = 9;
    const FixChar = 10;
    const Text = 11;
    const Blob = 12;

    // Date/Time
    const Date = 13;
    const Time = 14;
    const DateTime = 15;

    // ForeignKey
    const ForeignKey = 16;

    const NEED_LENGTH = [
        self::Char, self::FixChar, self::Decimal
    ];

    /**
     * Returns if a fields needs length
     */
    static public function needLength($field)
    {
        return in_array($field, self::NEED_LENGTH);
    }
}
