<?php namespace Zephyrus\Core\Mailer;

use PHPMailer\PHPMailer\PHPMailer;
use Zephyrus\Exceptions\Mailer\MailerException;

interface MailerService
{
    public function initialize(PHPMailer $phpMailer): void;

    /**
     * @throws MailerException
     */
    public function send(PHPMailer $phpMailer, bool $asHtml = true): string;

    public function getName(): string;
}
