<?php namespace Zephyrus\Application;

class LocalizationConfiguration
{
    public const DEFAULT_CONFIGURATIONS = [
        'locale' => 'fr_CA', // Default language
        'charset' => 'utf8', // Default charset
        'timezone' => 'America/Montreal' // Default timezone
    ];

    private array $configurations;
    private string $locale;
    private string $charset;
    private string $timezone;

    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeLocale();
        $this->initializeCharset();
        $this->initializeTimezone();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializeLocale(): void
    {
        $this->locale = $this->configurations['locale']
            ?? self::DEFAULT_CONFIGURATIONS['locale'];
    }

    private function initializeCharset(): void
    {
        $this->charset = $this->configurations['charset']
            ?? self::DEFAULT_CONFIGURATIONS['charset'];
    }

    private function initializeTimezone(): void
    {
        $this->timezone = $this->configurations['timezone']
            ?? self::DEFAULT_CONFIGURATIONS['timezone'];
    }
}
