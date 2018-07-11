<?php
namespace Vendimia\Logger\Formatter;

/**
 * Writes
 */
class PsrOneLiner implements FormatterInterface
{
    private $prepend = '';
    private $append = '';

    /**
     * Replaces {variables} with values in $context
     */
    private function interpolate($message, array $context = [])
    {
        $replace = [];
        foreach ($context as $key => $val)  {
          if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
              $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Prepends a text at the beginning of the message
     */
    public function setPrepend($prepend)
    {
        $this->prepend = $prepend;
    }

    /**
     * Appends a text at the endi of the message
     */
    public function setAppend($append)
    {
        $this->append = $append;
    }


    public function format($message, array $context = [])
    {
        return $this->prepend .
            $this->interpolate($message, $context) .
            $this->append;
    }

}
