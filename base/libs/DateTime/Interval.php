<?php
namespace Vendimia\DateTime;

class Interval extends DatePartsAbstract
{
    /**
     *
     */
    public function __construct($parts = [])
    {
        if ($parts) {
            // No usamos setPart(), para evitar reconstruir el timestamp cada
            // vez que cambiemos una parte.
            foreach ($parts as $part => $value) {
                if (!in_array($part, static::PARTS)) {
                    throw new \InvalidArgumentException("'$part' is not a valid DateTime part.");
                }

                $this->$part = $value;
            }
        }
    }

    /**
     * Returns an Interval built from DateTime::diff().
     *
     * Useful for processing Timestamp arithmetics
     */
     public static function fromDiff($seconds, $months)
     {
         $parts = [
             'second' => $seconds % 60,
             'minute' => floor($seconds / 60) % 60,
             'hour' => floor($seconds / 3600) % 24,
             'day' => floor($seconds / 86400),
             'month' => $months % 12,
             'year' => floor($months / 12),
         ];
         return new static($parts);
     }

     /**
      * Returns the Timestamp-like value for this Interval
      */
    public function getTimestamp()
    {
        return $this->day * 86400
            + $this->hour * 3600
            + $this->minute * 60
            + $this->second;
    }

    /**
     * Creates a Year interval
     */
    public static function year($year)
    {
        return new static(['year' => $year]);
    }

    /**
     * Alias of static::year()
     */
    public static function years($year)
    {
        return static::year($year);
    }

    /**
     * Creates a Month interval
     */
    public static function month($month)
    {
        return new static(['month' => $month]);
    }

    /**
     * Alias of static::month()
     */
    public static function months($month)
    {
        return static::month($month);
    }

    /**
     * Creates a Day interval
     */
    public static function day($day)
    {
        return new static(['day' => $day]);
    }

    /**
     * Alias of static::day()
     */
    public static function days($day)
    {
        return static::day($day);
    }

    /**
     * Creates an Hour interval
     */
    public static function hour($hour)
    {
        return new static(['hour' => $hour]);
    }

    /**
     * Alias of static::hour()
     */
    public static function hours($hour)
    {
        return static::hour($hour);
    }

}
