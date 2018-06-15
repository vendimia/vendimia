<?php
namespace Vendimia\Logger\Target;

/**
 * Writes the log to a PHP Stream, like a file or stdout.
 */
class Stream extends TargetBase implements TargetInterface
{
    private $stream;
    private $mode;

    public function __construct($stream, $mode = 'a')
    {
        $this->stream = $stream;
        $this->mode = $mode;

        parent::__construct();
    }

    public function write($message, array $context) {
        $f = fopen($this->stream, $this->mode);
        fwrite($f, $this->formatter->format($message, $context) . PHP_EOL);
        fclose($f);
    }
}
