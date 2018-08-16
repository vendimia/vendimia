<?php
namespace Vendimia;

/**
 * Class for date operations.
 */
class DateTime implements Database\ValueInterface
{
    /** Date parts, used in shortcut functions */
    private $parts = [
        'year',
        'month',
        'day',

        'hour',
        'minute',
        'second',

        'week', // Esto solo se usa en intervalos. Equivale a 7 días
    ];
    private $parts_data = [];

    /** Timestamp of this date */
    private $timestamp = null;

    // Esto se calcula de las partes
    public $weekday;
    public $yearday;

    public $is_interval = false;
    public $interval = 0;

    // true cuando no se ha modificado ningun valor
    // de la fecha/hora. Devuelve ahora.
    private $is_now = true;


    private function buildTimestamp()
    {
        if ($this->is_interval) {
            // En intervalos, sólo obtiene segundos, minutos, horas, y días.
            $this->interval = $this->day * 86400 +
                $this->hour * 360 +
                $this->minute * 60 +
                $this->second;
        }
        else {
            // En las fechas, lo obtenemos de todas las partes.

            if ($this->is_now) {
                $this->timestamp = time();
            } else {
                $this->updateTimestampFromParts();
            }
        }
    }

    /**
     * Helper for rounding half down numbers
     */
    private static function round($number)
    {
        //return $number > 0 ? floor ( $number ) : ceil ( $number );
        return round($number, 0, PHP_ROUND_HALF_DOWN);
    }

    /**
     * Updates parts from timestamp
     */
    private function updatePartsFromTimestamp()
    {
        $p = getdate($this->timestamp);

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
     * Updates timestamp from parts
     */
    private function updateTimestampFromParts()
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
     * Constructor.
     *
     * @param string $str Date/time string
     * @param bool $interval True if this instance is an interval
     */
    public function __construct ($str = null, $interval = false)
    {
        throw new \Exception('Deprecated');
        $this->parts_data = array_fill_keys($this->parts, 0);

        if (!is_null($str)) {
            if ($interval) {
                $this->interval = $str;
                $this->is_interval = true;
            } else {
                $this->timestamp = strtotime($str);
            }
        }
        else {
            if ($interval) {
                $this->is_interval = true;
                $this->is_now = false;
            } else {
                $this->timestamp = time();
            }
        }

        // Deconstruimos la fecha

        // Timestamp
        if ($this->timestamp) {
            $this->updatePartsFromTimestamp();

            $this->is_now = false;
        } elseif ($this->interval) {
            $this->second = $this->interval % 60;
            $this->minute = static::round($this->interval / 60) % 60;
            $this->hour = static::round($this->interval / 3600) % 24;
            $this->day = static::round($this->interval / 86400);
        }
    }

    /**
     * Returns a new DateTime object with tomorrow date
     */
    static function tomorrow()
    {
        return (new static)->add(static::day(1));
    }

    /**
     * Returns a new DateTime object with yesterday date
     */
    static function yesterday()
    {
        return (new static)->sub(static::day(1));
    }

    /**
     * Returns a new DateTime object with now date/time.
     *
     * Same result will be obtain just instanciating this class without
     * parameters.
     */
    static function now()
    {
        return new static;
    }

    /**
     * Syntax sugar for avoiding the PHP 'new' low precedence
     */
    static function create($date_str = null)
    {
        return new static($date_str);
    }

    /**
     * Sets the time part to 00:00:00 without altering the date part.
     */
    function onlyDate()
    {
        $this->hour = 0;
        $this->minute = 0;
        $this->second = 0;

        $this->updateTimestampFromParts();

        return $this;
    }

    /**
     * Sets the date part to 0/0/0 without altering the time part.
     */
    function onlyTime()
    {
        $this->day = 0;
        $this->month = 0;
        $this->year = 0;

        $this->updateTimestampFromParts();

        return $this;
    }

    /**
     * Magic function for update any date/time part
     */
    function __call($var, $val) {
        // Removemos el plural
        if (substr ( $var, -1 ) == "s" ) {
            $var = substr ( $var, 0, -1 );
        }

        // Solo permitimos algunos
        if (in_array($var,$this->parts)) {
            if (isset($val[0])) {
                // setter

                $value = $val[0];
                // Funciones especiales
                if ( $var == "week") {
                    $var = "day";
                    $value *= 7;
                }

                // Ahora tiene un valor
                $this->is_now = false;
                $this->parts_data[$var] = floatval($value);
                return $this;
            }
            else {
                // Getter
                return $this->$var;
            }
        }
        else {
            // Fallamos
            throw new \BadMethodCallException("'$var' is not a DateTime part.");
        }
    }

    /**
     * Magic function to create intervals using the part name as static method.
     */
    static function __callStatic($var, $val)
    {
        return (new static(false, true))->$var($val[0]);
    }

    /**
     * Returns the UNIX timestamp
     */
    function getTimestamp()
    {
        $this->buildTimestamp();
        return $this->timestamp;
    }

    /**
     * Returns a formatted string representation.
     *
     * If a '%' sign is found, then the strftime() PHP function will be used,
     * otherwise the date() function will be used.
     */
    function format($format = null)
    {

        // Un intervalo tiene otra forma
        if ( $this->is_interval ) {
            if (is_null($format)) {
                $format = '%d day%D, %h hour%H, %i minute%I, %s second%S';
            }
            $repl = [
/*                '%y' => $this->year,
                '%m' => $this->month,*/
                '%d' => $this->day,

                '%h' => $this->hour,
                '%i' => $this->minute,
                '%s' => $this->second,
            ];

            // Colocamos su plural, de haber
            foreach ( $repl as $key => $value ) {
                $plural = '';

                if ( $value <> 1 ) {
                    $plural = 's';
                }

                $repl [strtoupper($key)] = $plural;
            }

            // Reemplazamos
            return strtr($format, $repl);
        }
        else {
            $this->buildTimestamp();

            if ( is_null($format) ) {
                // Formato por defecto
                $format = 'Y-m-d H:i:s';
            }
            // Si hay '%' en format, entonces es para sfttimre
            if ( strpos ( $format, '%') !== false ) {
                return strftime($format, $this->timestamp);
            }
            else {
                return date($format, $this->timestamp);
            }
        }
    }

    /**
     * Adds a interval to this object.
     *
     * @return object This DateTime object.
     */
    function add(DateTime $interval, $substract = false)
    {
        // Esta funcion también resta
        $sign = $substract ? -1 : 1;

        // Añadimos todos los valores
        foreach ($this->parts as $part) {
            $this->$part += $interval->$part * $sign;
        }

        // Reconstruimos
        $this->buildTimestamp();

        return $this;
    }

    /**
     * Substracts a interval from this object.
     *
     * @return object This DateTime object.
     */
    function sub(DateTime $interval)
    {
        return $this->add($interval, true);
    }

    /**
     * Similar to add(), but returns a new object.
     */
    function plus(DateTime $interval)
    {
        $target = clone $this;
        return $target->add($interval);
    }

    /**
     * Similar to sub(), but returns a new object.
     */
    function minus(DateTime $interval)
    {
        $target = clone $this;
        return $target->add($interval, true);
    }

    /**
     * Returns an interval, substracting $this from $target
     *
     * If $target is after $this, interval will be positive, otherwise
     * it will return a negative interval.
     */
    function diff(DateTime $target)
    {
        // $target no debe ser un intervalo
        if ($target->isInterval()) {
            throw new Exception ('diff() only works between two dates.');
        }

        // El intervalo
        $interval  = $target->getTimestamp() - $this->getTimestamp();

        return new static($interval, true);
    }

    /**
     * Returns whether $this is before $target
     */
    public function isBefore(DateTime $target)
    {
        return $this->diff($target) < 0;
    }

    /**
     * Returns whether this instance is an interval, or not.
     */
    public function isInterval()
    {
        return $this->is_interval;
    }

    /**
     * Returns the date part.
     */
    public function getDate($format = 'Y-m-d')
    {
        return $this->format($format);
    }

    /**
     * Returns the time part.
     */
    public function getTime($format = 'H:i:s')
    {
        return $this->format($format);
    }

    function __get ($var)
    {
        // Removemos el plural
        if (substr ( $var, -1 ) == "s" ) {
            $var = substr ( $var, 0, -1 );
        }

        if ( in_array ( $var,  $this->parts ) ) {
            return $this->parts_data [ $var ];
        }
    }

    function __set ( $var, $val ) {
        if (substr ( $var, -1 ) == "s" ) {
            $var = substr ( $var, 0, -1 );
        }

        if ( in_array ( $var,  $this->parts ) ) {
            $this->parts_data [ $var ] = $val;
        }
    }

    function __toString() {
        // Retornamos el formato por defecto
        return $this->format();
    }

    /**
     * Returns the most common date-time value for databases
     */
    public function getDatabaseValue(\Vendimia\Database\ConnectorInterface $connector)
    {
        return $connector->escape($this->format('Y-m-d H:i:s'));
    }
}
