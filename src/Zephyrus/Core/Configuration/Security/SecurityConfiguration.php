<?php namespace Zephyrus\Core\Configuration\Security;

use Zephyrus\Core\Configuration\Configuration;

class SecurityConfiguration extends Configuration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'password' => PasswordConfiguration::DEFAULT_CONFIGURATIONS,
        'encryption' => EncryptionConfiguration::DEFAULT_CONFIGURATIONS,
        'csrf' => CsrfConfiguration::DEFAULT_CONFIGURATIONS,
        'ids' => IdsConfiguration::DEFAULT_CONFIGURATIONS
    ];

    private PasswordConfiguration $passwordConfiguration;
    private EncryptionConfiguration $encryptionConfiguration;
    private CsrfConfiguration $csrfConfiguration;
    private IdsConfiguration $idsConfiguration;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        parent::__construct($configurations);
        $this->initializePasswordConfigurations();
        $this->initializeEncryptionConfigurations();
        $this->initializeCsrfConfigurations();
        $this->initializeIdsConfigurations();
    }

    public function getPasswordConfiguration(): PasswordConfiguration
    {
        return $this->passwordConfiguration;
    }

    public function getEncryptionConfiguration(): EncryptionConfiguration
    {
        return $this->encryptionConfiguration;
    }

    public function getCsrfConfiguration(): CsrfConfiguration
    {
        return $this->csrfConfiguration;
    }

    public function getIdsConfiguration(): IdsConfiguration
    {
        return $this->idsConfiguration;
    }

    private function initializePasswordConfigurations(): void
    {
        $this->passwordConfiguration = new PasswordConfiguration($this->configurations['password']
                ?? self::DEFAULT_CONFIGURATIONS['password']);
    }

    private function initializeEncryptionConfigurations(): void
    {
        $this->encryptionConfiguration = new EncryptionConfiguration($this->configurations['encryption']
                ?? self::DEFAULT_CONFIGURATIONS['encryption']);
    }

    private function initializeCsrfConfigurations(): void
    {
        $this->csrfConfiguration = new CsrfConfiguration($this->configurations['csrf']
                ?? self::DEFAULT_CONFIGURATIONS['csrf']);
    }

    private function initializeIdsConfigurations(): void
    {
        $this->idsConfiguration = new IdsConfiguration($this->configurations['ids']
                ?? self::DEFAULT_CONFIGURATIONS['ids']);
    }
}
