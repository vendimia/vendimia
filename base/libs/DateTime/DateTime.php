<?php
namespace Vendimia\DateTime;

use Vendimia\Database\ValueInterface;
use Vendimia\Database\ConnectorInterface;

/**
 * Date/time manupulation class.
 */
class DateTime extends DateTimeAbstract implements ValueInterface
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
     * @return Interval Interval between the two DateTime;
     */
    public function diff(DateTime $target)
    {
        $interval  = $target->getTimestamp() - $this->getTimestamp();

        return Interval::fromSeconds($interval);
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
        if (strpos($format, '%') !== false) {
            return strftime($format, $this->timestamp);
        } else {
            return date($format, $this->timestamp);
        }
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
}
