<?php
namespace Vendimia\DateTime;

abstract class DateTimeAbstract
{
    const PARTS = ['year', 'month', 'day', 'hour', 'minute', 'second'];

    protected $year = 0;
    protected $month = 0;
    protected $day = 0;
    protected $hour = 0;
    protected $minute = 0;
    protected $second = 0;

    protected $yearday = 0;
    protected $weekday = 0;

    protected $timestamp = null;

    /**
     * Updates the DateTime parts from a timestamp.
     */
    protected function setPartsFromTimestamp($timestamp)
    {
        $p = getdate($timestamp);

        $this->year = $p['year'];
        $this->month = $p['mon'];
        $this->day = $p['mday'];

        $this->hour = $p['hours'];
        $this->minute = $p['minutes'];
        $this->second = $p['seconds'];

        $this->weekday = $p['wday'];
        $this->yearday = $p['yday'];
    }

    /**
     * Generates the timestamp from this DateTime parts
     */
     protected function buildTimestampFromParts()
     {
         $this->timestamp = mktime (
             $this->hour,
             $this->minute,
             $this->second,

             $this->month,
             $this->day,
             $this->year
         );
     }


    /**
     * Sets any part of this DateTime
     */
    public function setPart($part, $value)
    {
        if (!in_array($part, static::PARTS)) {
            throw new \InvalidArgumentException("'$part' is not a valid DateTime part.");
        }

        $this->$part = $value;
        $this->buildTimestampFromParts();
    }

    /**
     * Returns this DateTime parts as an array
     */
    public function getParts()
    {
        $parts = [];
        foreach (static::PARTS as $part) {
            $parts[$part] = $this->$part;
        }

        return $parts;
    }

    /**
     * Returns the year part
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Alias of getYear()
     */
     public function getYears()
     {
         return $this->getYear();
     }

    /**
     * Sets the year part
     */
    public function setYear($year)
    {
        $this->setPart('year', $year);
        return $this;

    }
    /**
     * Returns the month part
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Alias of getMonth()
     */
     public function getMonths()
     {
         return $this->getMonth();
     }


    /**
     * Sets the month part
     */
    public function setMonth($month)
    {
        $this->setPart('month', $month);
        return $this;
    }

    /**
     * Returns the day part
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Alias of getDay()
     */
     public function getDays()
     {
         return $this->getDay();
     }

    /**
     * Sets the day part
     */
    public function setDay($day)
    {
        $this->setPart('day', $day);
        return $this;
    }

    /**
     * Returns the hour part
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * Alias of getHour()
     */
     public function getHours()
     {
         return $this->getHour();
     }


    /**
     * Sets the hour part
     */
    public function setHour($hour)
    {
        $this->setPart('hour', $hour);
        return $this;
    }

    /**
     * Returns the minute part
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Alias of getMinute()
     */
     public function getMinutes()
     {
         return $this->getMinute();
     }

    /**
     * Sets the minute part
     */
    public function setMinute($minute)
    {
        $this->setPart('minute', $minute);
        return $this;
    }

    /**
     * Returns the second part
     */
    public function getSecond()
    {
        return $this->second;
    }

    /**
     * Alias of getSecond()
     */
    public function getSeconds()
    {
        return $this->getSecond();
    }

    /**
     * Sets the minute part
     */
    public function setSecond($second)
    {
        $this->setPart('second', $second);
        return $this;
    }

    /**
     * Converts this object to a sting
     */
     public function __toString()
     {
         if (is_null($this->timestamp)) {
             return '';
         } else {
             return $this->format();
         }
     }
}
