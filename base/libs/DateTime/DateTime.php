<?php
namespace Vendimia\DateTime;

use Vendimia\Database\ValueInterface;
use Vendimia\Database\ConnectorInterface;

/**
 * Date/time manupulation class.
 */
class DateTime extends DatePartsAbstract implements ValueInterface
{
    /**
     * Constructor.
     * @param mixed $source Source string for date/time, a numeric Timestamp, a
     *      Vendimia DateTime  object, or a PHP DateTime object. Null uses
     *      the actual date/time.
     */
    public function __construct($source = null)
    {
        if (is_numeric($source)) {
            $this->timestamp = $source;
        } elseif (is_string($source)) {
            $this->timestamp = strtotime($source);

            // Si no puede interpretar $source, entonces lo convertimos a null
            if ($this->timestamp === false) {
                $this->timestamp = null;
            }
        } elseif ($source instanceof DateTime || $source instanceof \DateTime) {
            $this->timestamp = $source->getTimestamp();
        }

        if (!is_null($this->timestamp)) {
            $this->setPartsFromTimestamp($this->timestamp);
        }
    }

    public function buildTimestampFromParts()
    {
        parent::buildTimestampFromParts();

        // Si el timestamp ha sido construido de partes inválidas (pero
        // aceptables), esto reconstruye las partes
        $this->setPartsFromTimestamp($this->timestamp);
    }

    /**
     * Adds or substracts an interval from this DateTime
     *
     * @param Interval $interval Interval to be added or substracted
     * @param int $sign Multiplier for add or substract
     */
    public function add(Interval $interval, $sign = 1)
    {
        foreach (static::PARTS as $part) {
            $this->$part += $interval->$part * $sign;
        }
        $this->buildTimestampFromParts();

        return $this;
    }

    /**
     * Substracts an interval from this DateTime
     * @param Interval $interval Interval to be substracted
     *
     */
     public function sub(Interval $interval)
     {
         return $this->add($interval, -1);
     }

    /**
     * Returns an interval between two dates, substracting $this from $target.
     *
     * If $target is after $this, interval will be positive, otherwise
     * it will return a negative interval.
     *
     * Due the varying nature of the month's days number, the resulting inteval
     * will have the days and months "disconnected", that is, days can be
     * greater than 31. E.g. two dates with 3 month different will result
     * in a interval with days = 90 and months = 3;
     *
     * @return Interval Interval between the two DateTime;
     */
    public function diff(DateTime $target)
    {
        // Usamos 2 elementos: Primero, los segundos de diferencia
        $seconds = $target->getTimestamp() - $this->getTimestamp();

        // Y los meses de diferencia
        $months = ($target->getYears() * 12 + $target->getMonths()) -
                ($this->getYears() * 12 + $this->getMonths());

        return Interval::fromDiff($seconds, $months);
    }

    /**
     * Returns true if $this is before $target
     */
    public function isBefore(DateTime $target)
    {
        return $this->diff($target)->getTimestamp() > 0;
    }

     /**
      * Returns true if $this is before or equals to $target
      */
    public function isBeforeOrEqualsTo(DateTime $target)
    {
        return $this->diff($target)->getTimestamp() >= 0;
    }

     /**
      * Returns true if $this is after $target
      */
     public function isAfter(DateTime $target)
     {
         return $this->diff($target)->getTimestamp() < 0;
     }

    /**
     * Returns true if $this is after or equals to $target
     */
    public function isAfterOrEqualsTo(DateTime $target)
    {
        return $this->diff($target)->getTimestamp() <= 0;
    }
    /**
     * Returns the timestamp
     */
     public function getTimestamp()
     {
         return $this->timestamp;
     }

    /**
     * Returns a formatted date string representation.
     *
     * If a '%' sign is found, then the strftime() PHP function will be used,
     * otherwise the date() function will be used.
     *
     * @param string $format Template for date/time formatting.
     */
    public function format($format = 'Y-m-d H:i:s')
    {
        // Si no hay información de la fecha/hora, retornamos una cadena vacía
        if (is_null($this->timestamp)) {
            return null;
        }

        // Si hay un %, usamos strftime()
        if (strpos($format, '%') !== false) {
            return strftime($format, $this->timestamp);
        }

        // De lo contrario, usamos date()
        return date($format, $this->timestamp);

    }

    /**
     * Returns true when this DateTime instance have no value.
     */
    public function isNull()
    {
        return is_null($this->timestamp);
    }

    /**
     * Syntax sugar for !self::isNull()
     */
    public function notNull()
    {
        return !$this->isNull();
    }

    /**
     * Returns the most common date-time value for databases
     */
    public function getDatabaseValue(ConnectorInterface $connector)
    {
        return $connector->escape($this->format('Y-m-d H:i:s'));
    }

    /**
     * Creates a DateTime object with the actual date and time.
     */
    public static function now()
    {
        return new static(time());
    }

    /**
     * Converts this object to a string
     */
    public function __toString()
    {
        return $this->format();
    }
}
