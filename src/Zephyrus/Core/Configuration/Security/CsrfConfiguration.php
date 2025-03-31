<?php namespace Zephyrus\Core\Configuration\Security;

class CsrfConfiguration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'enabled' => true // Activate the CSRF
    ];

    private array $configurations;
    private bool $enabled;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeEnabled();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializeEnabled(): void
    {
        $this->enabled = (bool) ((isset($this->configurations['enabled']))
            ? $this->configurations['enabled']
            : self::DEFAULT_CONFIGURATIONS['enabled']);
    }
}
