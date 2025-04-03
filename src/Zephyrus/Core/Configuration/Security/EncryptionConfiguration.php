<?php namespace Zephyrus\Core\Configuration\Security;

use Zephyrus\Core\Configuration\Configuration;

class EncryptionConfiguration extends Configuration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'key' => 'default_key' // Encryption key (should be unique by project and environnement)
    ];

    private string $key;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        parent::__construct($configurations);
        $this->initializeKey();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    private function initializeKey(): void
    {
        $this->key = $this->configurations['key']
            ?? self::DEFAULT_CONFIGURATIONS['key'];
    }
}
