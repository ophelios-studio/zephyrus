<?php namespace Zephyrus\Core\Configuration\Security;

use Zephyrus\Exceptions\Configuration\IdsThresholdInvalidException;

class IdsConfiguration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'enabled' => true, // Activate the IDS checks
        'impact_threshold' => 50 // Calculated impact before triggering the exception
    ];

    private array $configurations;
    private bool $enabled;
    private int $impactThreshold;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeEnabled();
        $this->initializeImpactThreshold();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getImpactThreshold(): int
    {
        return $this->impactThreshold;
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

    private function initializeImpactThreshold(): void
    {
        $impact = $this->configurations['impact_threshold'] ?? self::DEFAULT_CONFIGURATIONS['impact_threshold'];
        if (!is_numeric($impact)) {
            throw new IdsThresholdInvalidException();
        }
        $this->impactThreshold = $impact;
    }
}
