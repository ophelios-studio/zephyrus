<?php namespace Zephyrus\Core\Configuration\Security;

class EncryptionConfiguration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'key' => 'default_key' // Encryption key (should be unique by project and environnement)
    ];

    private array $configurations;
    private string $key;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeKey();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializeKey(): void
    {
        $this->key = $this->configurations['key']
            ?? self::DEFAULT_CONFIGURATIONS['key'];
    }
}
