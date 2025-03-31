<?php namespace Zephyrus\Core\Configuration\Security;

class PasswordConfiguration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'pepper' => 'basic_pepper' // Random string which should be unique by server
    ];

    private array $configurations;
    private string $pepper;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializePepper();
    }

    public function getPepper(): string
    {
        return $this->pepper;
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializePepper(): void
    {
        $this->pepper = $this->configurations['pepper']
            ?? self::DEFAULT_CONFIGURATIONS['pepper'];
    }
}
