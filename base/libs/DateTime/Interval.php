<?php
namespace Vendimia\DateTime;

class Interval extends DateTimeAbstract
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
     * Returns an Interval built from seconds.
     *
     * Useful for processing Timestamp arithmetics
     */
     public static function fromSeconds($seconds)
     {
         $parts = [
             'second' => $seconds % 60,
             'minute' => floor($seconds / 60) % 60,
             'hour' => floor($seconds / 3600) % 24,
             'day' => floor($seconds / 86400),
         ];
         return new static($parts);
     }

     /**
      * Returns the Timestamp-like value for this Interval
      */
      public function asSeconds()
      {
          return $this->days * 86400
            + $this->hours * 3600
            + $this->minutes * 60
            + $this->seconds;
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
    public static function months($year)
    {
        return static::month($year);
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
        return static::days($day);
    }

    /**
     * Creates a Hour interval
     */
    public static function hour($hour)
    {
        return new static(['hour' => $hour]);
    }

    /**
     * Alias of static::day()
     */
    public static function hours($hour)
    {
        return static::hour($hour);
    }

}
