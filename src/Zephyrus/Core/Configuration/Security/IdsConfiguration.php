<?php namespace Zephyrus\Core\Configuration\Security;

use Zephyrus\Core\Configuration\Configuration;
use Zephyrus\Exceptions\Configuration\IdsThresholdInvalidException;

class IdsConfiguration extends Configuration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'enabled' => false, // Enable the intrusion detection feature
        'custom_file' => null, // Change the default rule file
        'impact_threshold' => 0, // Minimum impact to be considered to throw an exception (default is any detection)
        'monitor_cookies' => true, // Verifies the content of request cookies
        'monitor_url' => true, // Verifies the content of the request URL
        'exceptions' => [] // List of request parameters to be exempt of detection (e.g. '__utmz')
    ];

    private bool $enabled;
    private int $impactThreshold;
    private array $exceptions;
    private bool $includeCookiesMonitoring;
    private bool $includeUrlMonitoring;
    private ?string $customFile;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        parent::__construct($configurations);
        $this->initializeEnabled();
        $this->initializeImpactThreshold();
        $this->initializeExceptions();
        $this->initializeCookieMonitoring();
        $this->initializeUrlMonitoring();
        $this->initializeCustomFile();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getImpactThreshold(): int
    {
        return $this->impactThreshold;
    }

    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    public function isCookieMonitoring(): bool
    {
        return $this->includeCookiesMonitoring;
    }

    public function isUrlMonitoring(): bool
    {
        return $this->includeUrlMonitoring;
    }

    public function getCustomFile(): ?string
    {
        return $this->customFile;
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

    private function initializeExceptions(): void
    {
        $this->exceptions = $this->configurations['exceptions']
            ?? self::DEFAULT_CONFIGURATIONS['exceptions'];
    }

    private function initializeCustomFile(): void
    {
        $this->customFile = $this->configurations['custom_file']
            ?? self::DEFAULT_CONFIGURATIONS['custom_file'];
    }

    private function initializeCookieMonitoring(): void
    {
        $this->includeCookiesMonitoring = (bool) ((isset($this->configurations['monitor_cookies']))
            ? $this->configurations['monitor_cookies']
            : self::DEFAULT_CONFIGURATIONS['monitor_cookies']);
    }

    private function initializeUrlMonitoring(): void
    {
        $this->includeUrlMonitoring = (bool) ((isset($this->configurations['monitor_url']))
            ? $this->configurations['monitor_url']
            : self::DEFAULT_CONFIGURATIONS['monitor_url']);
    }
}
