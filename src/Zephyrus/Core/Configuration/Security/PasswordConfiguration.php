<?php namespace Zephyrus\Core\Configuration\Security;

use Zephyrus\Core\Configuration\Configuration;

class PasswordConfiguration extends Configuration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'pepper' => 'basic_pepper' // Random string which should be unique by server
    ];

    private string $pepper;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        parent::__construct($configurations);
        $this->initializePepper();
    }

    public function getPepper(): string
    {
        return $this->pepper;
    }

    private function initializePepper(): void
    {
        $this->pepper = $this->configurations['pepper']
            ?? self::DEFAULT_CONFIGURATIONS['pepper'];
    }
}
