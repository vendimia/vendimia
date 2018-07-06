<?php
namespace Vendimia\Logger\Target;

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

        parent::__construct();
    }

    public function write($message, array $context)
    {
        $message = $this->formatter->format($message, $context);

        $this->mailer->Body = $message;
        $this->mailer->AltBody = strip_tags($message);
        $this->mailer->send();

    }
}
