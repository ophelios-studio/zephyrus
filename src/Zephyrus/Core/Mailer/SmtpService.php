<?php namespace Zephyrus\Core\Mailer;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Zephyrus\Core\Configuration\Mailer\MailerSmtpConfiguration;
use Zephyrus\Exceptions\Mailer\MailerException;

class SmtpService implements MailerService
{
    public const string NAME = 'smtp';
    private ?MailerSmtpConfiguration $smtpConfiguration;

    public function __construct(MailerSmtpConfiguration $configuration)
    {
        $this->smtpConfiguration = $configuration;
    }

    /**
     * @throws MailerException
     */
    public function send(PHPMailer $phpMailer, bool $asHtml = true): string
    {
        $phpMailer->IsHTML($asHtml);
        try {
            $phpMailer->preSend();
            $phpMailer->postSend();
        } catch (Exception $exception) {
            throw new MailerException($exception->getMessage());
        }
        return $phpMailer->getSentMIMEMessage();
    }

    public function initialize(PHPMailer $phpMailer): void
    {
        $phpMailer->isSMTP();
        $phpMailer->Host = $this->smtpConfiguration->getHost();
        $phpMailer->Port = $this->smtpConfiguration->getPort();
        $phpMailer->SMTPAuth = $this->smtpConfiguration->hasAuthentication();
        $phpMailer->Username = $this->smtpConfiguration->getUsername();
        $phpMailer->Password = $this->smtpConfiguration->getPassword();
        $phpMailer->SMTPOptions = $this->smtpConfiguration->getSslOptions();
        if ($this->smtpConfiguration->getEncryption() != "none") {
            $phpMailer->SMTPSecure = $this->smtpConfiguration->getEncryption();
        }
        if ($this->smtpConfiguration->isDebug()) {
            $phpMailer->SMTPDebug = 2;
        }
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
