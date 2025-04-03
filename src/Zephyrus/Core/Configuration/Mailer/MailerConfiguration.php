<?php namespace Zephyrus\Core\Configuration\Mailer;

use Zephyrus\Core\Configuration\Configuration;
use Zephyrus\Core\Mailer\MailerService;
use Zephyrus\Core\Mailer\SmtpService;
use Zephyrus\Exceptions\Mailer\MailerSmtpEncryptionException;
use Zephyrus\Exceptions\Mailer\MailerSmtpPortException;

class MailerConfiguration extends Configuration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'transport' => 'smtp',
        'default_from_address' => null, // Address FROM which the emails are sent
        'default_from_name' => null, // Name associated with the FROM address
        'smtp' => MailerSmtpConfiguration::DEFAULT_CONFIGURATIONS,
    ];

    private string $transport;
    private string $fromAddress;
    private string $fromName;
    private ?MailerSmtpConfiguration $smtpConfiguration = null;

    /**
     * @throws MailerSmtpPortException
     * @throws MailerSmtpEncryptionException
     */
    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        parent::__construct($configurations);
        $this->initializeTransport();
        $this->initializeFrom();
        $this->initializeSmtpConfigurations();
    }

    public function buildService(): ?MailerService
    {
        if ($this->transport == "smtp") {
            return new SmtpService($this->smtpConfiguration);
        }
        return null;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function getDefaultFromAddress(): ?string
    {
        return $this->fromAddress;
    }

    public function getDefaultFromName(): ?string
    {
        return $this->fromName;
    }

    public function getSmtpConfiguration(): ?MailerSmtpConfiguration
    {
        return $this->smtpConfiguration;
    }

    private function initializeTransport(): void
    {
        $this->transport = $this->configurations['transport']
            ?? self::DEFAULT_CONFIGURATIONS['transport'];
    }

    private function initializeFrom(): void
    {
        $this->fromAddress = $this->configurations['default_from_address']
            ?? self::DEFAULT_CONFIGURATIONS['default_from_address'];
        $this->fromName = $this->configurations['default_from_name']
            ?? self::DEFAULT_CONFIGURATIONS['default_from_name'];
    }

    /**
     * @throws MailerSmtpPortException
     * @throws MailerSmtpEncryptionException
     */
    private function initializeSmtpConfigurations(): void
    {
        if ($this->transport == "smtp") {
            $this->smtpConfiguration = new MailerSmtpConfiguration($this->configurations['smtp']
                ?? self::DEFAULT_CONFIGURATIONS['smtp']);
        }
    }
}
