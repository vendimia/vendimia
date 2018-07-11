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

        $subject = '';
        if (key_exists('_logger_name', $context)) {
            $subject = "[{$context['_logger_name']}] ";
            unset ($context['_logger_name']);
        }

        $subject .= strtoupper($context['_level']) . ': ' . (string)$message;

        // $message debe ser un string, siempre.
        $this->mailer->Subject = $subject;
        $this->mailer->Body = $body;
        $this->mailer->AltBody = strip_tags($body);
        $this->mailer->send();
    }
}
