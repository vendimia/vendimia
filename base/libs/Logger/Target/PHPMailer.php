<?php
namespace Vendimia\Logger\Target;

use Vendimia\Logger;
use Vendimia\Logger\Formatter;
use PHPMailer\PHPMailer\PHPMailer as PM;

/**
 * Sends an email using PHPMailer
 */
class PHPMailer extends TargetBase implements TargetInterface
{
    private $addresses;
    private $mailer;

    /**
     * @param object $mailer A PHPMailer preconfigured instance.
     */
    public function __construct(PM $mailer)
    {
        $this->mailer = $mailer;
        $this->formatter = new Formatter\Html;
    }

    public function write($message, array $context)
    {

        $body = $this->formatter->format($message, $context);

        // $message debe ser un string, siempre.
        $this->mailer->Subject = (string)$message;
        $this->mailer->Body = $body;
        $this->mailer->AltBody = strip_tags($body);
        var_dump($this->mailer->send());

    }
}
